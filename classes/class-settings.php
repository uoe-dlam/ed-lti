<?php

namespace EdLTI;

/**
 * Control the LTI settings for WordPress.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 */
class Settings {

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		add_action( 'network_admin_menu', [ $this, 'network_pages' ] );
	}

	/**
	 * Edit or save consumer keys
	 *
	 * @return void
	 */
	public function consumer_keys_admin() {
		$is_editing = false;

		echo '<h2>LTI: Consumers Keys</h2>';

		if ( ! empty( $_POST['action'] ) ) {
			check_admin_referer( 'lti' );

			$consumer_key = strtolower( $_POST['consumer_key'] );
			$query        = "SELECT * FROM {$this->wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s";

			switch ( $_POST['action'] ) {
				case 'edit':
                    // phpcs:disable
					$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $consumer_key ) );
                    // phpcs:enable

					if ( $row ) {
						$this->edit( $row );
						$is_editing = true;
					} else {
						echo '<h3>Provider not found</h3>';
					}

					break;
				case 'save':
					$errors = $this->do_validation();

					if ( ! empty( $errors ) ) {
						echo '<ul style="color:red">';

						foreach ( $errors as $error ) {
							echo '<li>' . esc_html( $error ) . '</li>';
						}

						echo '</ul>';

						break;
					}

					$enabled = isset( $_POST['enabled'] ) ? 1 : 0;

                    // phpcs:disable
					$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $consumer_key ) );
                    // phpcs:enable

					if ( $row ) {
						$update_query = "UPDATE {$this->wpdb->base_prefix}lti2_consumer "
							. 'SET  name = %s, secret = %s, enabled = %d, lti_version = %s '
							. 'WHERE consumer_key256 = %s';

                        // phpcs:disable
						$this->wpdb->query(
							$this->wpdb->prepare(
								$update_query,
								$_POST['name'],
								$_POST['secret'],
								$enabled,
								$_POST['lti_version'],
								$consumer_key
							)
						);
                        // phpcs:enable

						echo '<p><strong>Provider Updated</strong></p>';
					} else {
						$insert_query = "INSERT INTO {$this->wpdb->base_prefix}lti2_consumer "
							. '( `name`, `consumer_key256`, `secret`, `enabled`, `lti_version`) '
							. 'VALUES ( %s, %s, %s, %d, %s)';

                        // phpcs:disable
						$this->wpdb->query(
							$this->wpdb->prepare(
								$insert_query,
								$_POST['name'],
								$_POST['consumer_key'],
								$_POST['secret'],
								$enabled,
								$_POST['lti_version']
							)
						);
                        // phpcs:enable

						echo '<p><strong>Provider Added</strong></p>';
					}

					break;
				case 'del':
					$delete_query = "DELETE FROM {$this->wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s";

                    // phpcs:disable
					$this->wpdb->query( $this->wpdb->prepare( $delete_query, $consumer_key ) );
                    // phpcs:enable

					echo '<p><strong>Provider Deleted</strong></p>';

					break;
			}
		}

		if ( ! $is_editing ) {
			echo '<h3>Search</h3>';

			if ( isset( $_POST['search_txt'] ) ) {
				$search = '%' . $this->wpdb->esc_like( addslashes( $_POST['search_txt'] ) ) . '%';

				$search_query = "SELECT * FROM {$this->wpdb->base_prefix}lti2_consumer "
					. 'WHERE consumer_key256 LIKE %s '
					. 'OR name LIKE %s';

				$rows = $this->wpdb->get_results(
                    // phpcs:disable
					$this->wpdb->prepare(
						$search_query,
						[ $search, $search ]
					)
                    // phpcs:enable
				);

				$this->listing( $rows, 'Searching for ' . esc_html( $_POST['search_txt'] ) );
			}

			echo '<form method="POST">';

			wp_nonce_field( 'lti' );

			echo '<input type="hidden" name="action" value="search" />';
			echo '<p>Search: <input type="text" name="search_txt" value=""></p>';
			echo '<input type="hidden" name="consumer_key" value="">';
			echo '<p><input type="submit" class="button-secondary" value="Search"></p>';
			echo '</form><br>';

			$this->edit();

            // phpcs:disable
			$rows = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->base_prefix}lti2_consumer LIMIT 0,20" );
            // phpcs:enable

			$this->listing( $rows );
		}
	}

	/**
	 * Validate the consumer key form
	 *
	 * @return array
	 */
	private function do_validation() {
		$errors = [];

        // phpcs:disable
		if ( '' === $_POST['name'] ) {
			$errors[] = 'Name is required';
		}

		if ( '' === $_POST['consumer_key'] ) {
			$errors[] = 'Consumer key is required';
		}

		if ( '' === $_POST['secret'] ) {
			$errors[] = 'Secret is required';
		}
        // phpcs:enable

		return $errors;
	}

	/**
	 * Edit an existing LTI setting.
	 *
	 * @param mixed $row
	 *
	 * @return void
	 */
	private function edit( $row = false ) {
		$is_new = false;

		if ( is_object( $row ) ) {
			echo '<h3>Edit LTI</h3>';
		} else {
			echo '<h3>New LTI</h3>';

			$row                  = new \stdClass();
			$row->name            = '';
			$row->consumer_key256 = '';
			$row->lti_version     = '';
			$row->secret          = '';
			$row->enabled         = 1;
			$is_new               = true;
		}

		echo '<form method="POST"><input type="hidden" name="action" value="save">';

		wp_nonce_field( 'lti' );

		echo '<table class="form-table">';
		echo '<tr><th>Name</th><td><input type="text" name="name" value="' . esc_attr( $row->name )
			. '" required ></td></tr>';

        // phpcs:disable
		echo '<tr><th>Consumer key</th><td><input type="text" name="consumer_key" value="'
            . $row->consumer_key256 . '"' . ( ! $is_new ? 'readonly="readonly"' : '' ) . 'required ></td></tr>';

		echo '<tr><th>Secret</th><td><input type="text" name="secret" value="' . $row->secret
            . '" required ></td></tr>';
        // phpcs:enable

		echo '<tr><th>LTI Version</th><td><select name="lti_version"><option value="LTI - 1p0"'
			. ( 'LTI - 1p0' === $row->lti_version ? 'selected' : '' ) . '>LTI-1p0</option><option value="LTI - 2p0" '
			. ( 'LTI - 2p0' === $row->lti_version ? 'selected' : '' ) . '>LTI-2p0</option></select></td></tr>';

		echo '<tr><th>Enabled</th><td><input type="checkbox" name="enabled" value="1" '
			. ( '1' === $row->enabled ? 'checked' : '' ) . '></td></tr>';

		echo '</table>';
		echo '<p><input type="submit" class="button - primary" value="Save"></p></form><br><br>';
	}

	/**
	 * List all LTI configurations.
	 *
	 * @param array  $rows
	 * @param string $heading
	 *
	 * @return void
	 */
	private function listing( $rows, $heading = '' ) {
		if ( $rows ) {
			if ( '' !== $heading ) {
				echo '<h3>' . esc_html( $heading ) . '</h3>';
			}

			echo '<table class="widefat" cellspacing="0"><thead><tr><th>Consumer name</th><th>Consumer key</th>'
				. '<th>LTI Version</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr></thead><tbody>';

			foreach ( $rows as $row ) {
				echo '<tr><td>' . esc_html( $row->name ) . '</td>';
				echo '<td>' . esc_html( $row->consumer_key256 ) . '</td>';
				echo '<td>';
				echo esc_html( $row->lti_version );
				echo '</td><td>';
				echo '1' === $row->enabled ? 'Yes' : 'No';

				echo '</td><td><form method="POST"><input type="hidden" name="action" value="edit">'
					. '<input type="hidden" name="consumer_key" value="' . esc_attr( $row->consumer_key256 ) . '">';

				wp_nonce_field( 'lti' );

				echo '<input type="submit" class="button-secondary" value="Edit"></form></td><td><form method="POST">'
					. '<input type="hidden" name="action" value="del"><input type="hidden" name="consumer_key" '
					. 'value="' . esc_attr( $row->consumer_key256 ) . '">';

				wp_nonce_field( 'lti' );

				echo '<input type="submit" class="button-secondary" value="Del"></form>';
				echo '</td></tr>';
			}

			echo '</table>';
		}
	}

	/**
	 * Add an LTI consumer key submenu
	 *
	 * @return void
	 */
	public function network_pages() {
		add_submenu_page(
			'settings.php',
			'LTI Consumers Keys',
			'LTI Consumers Keys',
			'manage_options',
			'consumer_keys_admin',
			[ $this, 'consumer_keys_admin' ]
		);
	}
}
