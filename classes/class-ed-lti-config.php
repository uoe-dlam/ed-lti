<?php
/**
 * Handles LTI Config Settings.
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
 */
class Ed_LTI_Config {

	public $updated;

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
	}

	/**
	 * Creates LTI Config settings page and menu item.
	 *
	 * @return void
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

			<form method="post">

				<table class="form-table">
				<?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ) : ?>
					<tr>
						<th scope="row"><label for="default_site_template_id"><?php _e( 'Do you want to make sites private on creation?', 'lti-config-group' ); ?></label></th>
						<td>
							<input name="lti_make_sites_private" type="checkbox" value="1" <?php checked( '1', get_site_option( 'lti_make_sites_private' ) ); ?>>
						</td>
					</tr>
				<?php endif ?>

				<tr>
					<th scope="row"><label for="default_site_template_id"><?php _e( 'Default Site Template ID', 'lti-config-group' ); ?></label></th>
					<td>
						<input type="number" min="0" id="default_site_template_id" name="default_site_template_id" value="<?php echo get_site_option( 'default_site_template_id' ); ?>" />
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
		$settings = array();

		if ( isset( $_POST['default_site_template_id'] ) && is_numeric( $_POST['default_site_template_id'] ) ) {
			$default_site_id = sanitize_text_field( $_POST['default_site_template_id'] );
			update_site_option( 'default_site_template_id', $default_site_id );
		}

		if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ) {

			if ( isset( $_POST['lti_make_sites_private'] ) ) {
				update_site_option( 'lti_make_sites_private', 1 );
			} else {
				update_site_option( 'lti_make_sites_private', 0 );
			}
		} else {
			update_site_option( 'lti_make_sites_private', 0 );
		}

		$this->updated = true;
	}

}
