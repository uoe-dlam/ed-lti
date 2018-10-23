<?php
/**
 * Handles LTI Config Settings.
 *
 * @author    Learning Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 */
class Ed_LTI_Config {

	public $updated;
	public $errors = [];

	public function __construct() {
		$this->initialize_options();

		// register page
		add_action( 'network_admin_menu', [ $this, 'create_setup_page' ] );

		// update settings
		add_action( 'network_admin_menu', [ $this, 'update' ] );
	}

	/**
	 * Initialize network options
	 *
	 * @return void
	 */
	public function initialize_options() {
		if ( ! get_site_option( 'lti_make_sites_private' ) ) {
			add_site_option( 'lti_make_sites_private', 0 );
		}

		if ( ! get_site_option( 'default_site_template_id' ) ) {
			add_site_option( 'default_site_template_id', 1 );
		}

		if ( ! get_site_option( 'default_site_template_slug' ) ) {
			add_site_option( 'default_site_template_slug', '' );
		}
	}

	/**
	 * Creates LTI Config settings page and menu item.
	 *
	 * @return Ed_LTI_Config
	 */
	public function create_setup_page() {
		add_submenu_page(
			'settings.php',
			__( 'LTI Settings', 'lti-config-group' ),
			__( 'LTI Settings' ),
			'manage_options',
			'lti-options',
			[ $this, 'show_page' ]
		);

		return $this;
	}

	/**
	 * Display settings page.
	 *
	 * @return void
	 */
	public function show_page() {
		?>

		<div class="wrap">

			<h2><?php _e( 'LTI Settings Admin', 'lti-config-group' ); ?></h2>

			<?php if ( $this->updated ) : ?>
				<div class="updated notice is-dismissible">
					<p><?php _e( 'Settings updated successfully!', 'lti-config-group' ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $this->errors ) ) : ?>
				<div class="notice notice-error">
					<?php foreach ( $this->errors as $error ) : ?>
					<p><?php _e( $error, 'lti-config-group' ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form method="post">

				<table class="form-table">
				<?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ) : ?>
					<tr>
						<th scope="row">
							<label for="lti_make_sites_private">
								<?php _e( 'Do you want to make sites private on creation?', 'lti-config-group' ); ?>
							</label>
						</th>
						<td>
							<input id="lti_make_sites_private" name="lti_make_sites_private" type="checkbox" value="1" <?php checked( '1', get_site_option( 'lti_make_sites_private' ) ); ?>>
						</td>
					</tr>
				<?php endif ?>

				<tr>
					<th scope="row">
						<label for="default_site_template_slug">
							<?php _e( 'Default Site Template URL', 'lti-config-group' ); ?>
						</label>
					</th>
					<td>
						<?php echo get_site_url(); ?>/<input type="text" id="default_site_template_slug" name="default_site_template_slug" value="<?php echo $this->get_saved_slug(); ?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php _e( 'NB: If you do not add a subsite ( i.e. enter a value in the above field ), this plugin will use the top level site as the template URL.' ); ?>
					</td>
				</tr>

					<tr>
					<th scope="row">
						<label for="is_helpline_url">
							<?php _e( 'Helpline URL', 'lti-config-group' ); ?>
						</label>
					</th>
					<td>
						<input type="text" id="is_helpline_url" name="is_helpline_url" value="<?php echo get_site_option( 'is_helpline_url' ); ?>">
					</td>
				</tr>
					<tr>
						<td colspan="2">
							<?php _e( 'NB: If you do not add a helpline URL, a helpline link will not be included in error messages.' ); ?>
						</td>
					</tr>

				</table>
				<?php wp_nonce_field( 'lti_config_nonce', 'lti_config_nonce' ); ?>
				<?php submit_button(); ?>

			</form>

		</div>

		<?php
	}

	/**
	 * Verify settings page and update settings.
	 *
	 * @return void
	 */
	public function update() {
		if ( isset( $_POST['submit'] ) ) {

			// verify authentication (nonce)
			if ( ! isset( $_POST['lti_config_nonce'] ) ) {
				return;
			}

			// verify authentication (nonce)
			if ( ! wp_verify_nonce( $_POST['lti_config_nonce'], 'lti_config_nonce' ) ) {
				return;
			}

			$this->update_settings();
		}
	}

	/**
	 * Update settings on form submission.
	 *
	 * @return void
	 */
	public function update_settings() {
		if ( isset( $_POST['default_site_template_slug'] ) ) {
			$slug = sanitize_text_field( $_POST['default_site_template_slug'] );
			$path = Ed_LTI::turn_slug_into_path( $slug );

			if ( ! domain_exists( get_current_site()->domain, $path ) ) {
				$this->errors[] = 'The URL that you entered does not exist.';
				return;
			}

			$blog_id = get_blog_id_from_url( get_current_site()->domain, $path );

			update_site_option( 'default_site_template_slug', $slug );
			update_site_option( 'default_site_template_id', $blog_id );
		}

		if ( isset( $_POST['is_helpline_url'] ) ) {
			update_site_option( 'is_helpline_url', $_POST['is_helpline_url'] );
		}

		if ( isset( $_POST['lti_make_sites_private'] ) && is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ) {
			update_site_option( 'lti_make_sites_private', 1 );
		} else {
			update_site_option( 'lti_make_sites_private', 0 );
		}
		$this->updated = true;
	}

	/*
	 *  Get slug from DB
	 *  Note: we get the slug from the blog itself, because it is possible that the template id has changed else where making the existing slug out of date.
	 *
	 * @return void
	 */
	protected function get_saved_slug() {
		$slashed_slug = get_blog_details( get_site_option( 'default_site_template_id' ) )->path;

		return str_replace( '/', '', $slashed_slug );
	}
}
