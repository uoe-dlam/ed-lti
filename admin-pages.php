<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 06/06/2018
 * Time: 14:33
 */

function lti_consumer_keys_admin() {
	global $wpdb, $current_site;

	/*
	if ( false == lti_site_admin() ) {
		return false;
	}
	*/

	$is_editing = false;

	echo '<h2>' . __( 'LTI: Consumers Keys', 'wordpress-mu-lti' ) . '</h2>';
	if ( ! empty( $_POST['action'] ) ) {
		check_admin_referer( 'lti' );
		$consumer_key = strtolower( $_POST['consumer_key'] );

		switch ( $_POST['action'] ) {
			case 'edit':
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s", $consumer_key ) );
				if ( $row ) {
					lti_edit( $row );
					$is_editing = true;
				} else {
					echo '<h3>' . __( 'Provider not found', 'wordpress-mu-lti' ) . '</h3>';
				}
				break;
			case 'save':
				$errors = lti_do_validation();

				if ( ! empty( $errors ) ) {

					echo '<ul style="color:red">';

					foreach ( $errors as $error ) {
						echo '<li>' . $error . '</li>';
					}
					echo '</ul>';

					break;
				}

				$enabled = isset( $_POST['enabled'] ) ? 1 : 0;

				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s", $consumer_key ) );
				if ( $row ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}lti2_consumer SET  name = %s, secret = %s, enabled = %d, lti_version = %s  WHERE consumer_key256 = %s", $_POST['name'], $_POST['secret'], $enabled, $_POST['lti_version'], $consumer_key ) );
					echo '<p><strong>' . __( 'Provider Updated', 'wordpress-mu-lti' ) . '</strong></p>';
				} else {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}lti2_consumer ( `name`, `consumer_key256`, `secret`, `enabled`, `lti_version`) VALUES ( %s, %s, %s, %d, %s)", $_POST['name'], $_POST['consumer_key'], $_POST['secret'], $enabled, $_POST['lti_version'] ) );
					echo '<p><strong>' . __( 'Provider Added', 'wordpress-mu-lti' ) . '</strong></p>';
				}
				break;
			case 'del':
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s", $consumer_key ) );
				echo '<p><strong>' . __( 'Provider Deleted', 'wordpress-mu-lti' ) . '</strong></p>';
				break;
		}
	}

	if ( ! $is_editing ) {
		echo '<h3>' . __( 'Search', 'wordpress-mu-lti' ) . '</h3>';
		$escaped_search = '';
		if ( isset( $_POST['search_txt'] ) ) {
			$escaped_search = addslashes( $_POST['search_txt'] );

			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 LIKE '%{$escaped_search}%' OR name LIKE '%{$escaped_search}%'" );
			lti_listing( $rows, sprintf( __( 'Searching for %s', 'wordpress-mu-lti' ), esc_html( $_POST['search_txt'] ) ) );
		}
		echo '<form method="POST">';
		wp_nonce_field( 'lti' );
		echo '<input type="hidden" name="action" value="search" />';
		echo '<p>';
		echo _e( 'Search:', 'wordpress-mu-lti' );
		echo " <input type='text' name='search_txt' value='' /></p>";
		echo " <input type='hidden' name='consumer_key' value='' /></p>";
		echo "<p><input type='submit' class='button-secondary' value='" . __( 'Search', 'wordpress-mu-lti' ) . "' /></p>";
		echo '</form><br />';
		lti_edit();
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}lti2_consumer LIMIT 0,20" );
		lti_listing( $rows );
	}
}

function lti_do_validation() {

	$errors = array();

	if ( $_POST['name'] == '' ) {
		$errors[] = 'Name is required';
	}

	if ( $_POST['consumer_key'] == '' ) {
		$errors[] = 'Consumer key is required';
	}

	if ( $_POST['secret'] == '' ) {
		$errors[] = 'Secret is required';
	}

	return $errors;
}


function lti_edit( $row = false ) {
	$is_new = false;
	if ( is_object( $row ) ) {
		echo '<h3>' . __( 'Edit LTI', 'wordpress-mu-lti' ) . '</h3>';
	} else {
		echo '<h3>' . __( 'New LTI', 'wordpress-mu-lti' ) . '</h3>';
		$row                  = new stdClass();
		$row->name            = '';
		$row->consumer_key256 = '';
		$row->lti_version     = '';
		$row->secret          = '';
		$row->enabled         = 1;
		$is_new               = true;
	}

	echo "<form method='POST'><input type='hidden' name='action' value='save' />";
	wp_nonce_field( 'lti' );
	echo "<table class='form-table'>\n";
	echo '<tr><th>' . __( 'Name', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='name' value='{$row->name}' required/></td></tr>\n";
	echo '<tr><th>' . __( 'Consumer key', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='consumer_key' value='{$row->consumer_key256}' " . ( ! $is_new ? 'readonly="readonly"' : '' ) . " required/></td></tr>\n";
	echo '<tr><th>' . __( 'Secret', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='secret' value='{$row->secret}' required/></td></tr>\n";
	echo '<tr><th>' . __( 'LTI Version', 'wordpress-mu-lti' ) . "</th><td><select name='lti_version'><option value='LTI-1p0' " . ( $row->lti_version == 'LTI-1p0' ? 'selected' : '' ) . ">LTI-1p0</option><option value='LTI-2p0' " . ( $row->lti_version == 'LTI-2p0' ? 'selected' : '' ) . ">LTI-2p0</option></select></td></tr>\n";
	echo '<tr><th>' . __( 'Enabled', 'wordpress-mu-lti' ) . "</th><td><input type='checkbox' name='enabled' value='1' ";
	echo $row->enabled == 1 ? 'checked=1 ' : ' ';
	echo "/></td></tr>\n";
	echo '</table>';
	echo "<p><input type='submit' class='button-primary' value='" . __( 'Save', 'wordpress-mu-lti' ) . "' /></p></form><br /><br />";
}


function lti_network_warning() {
	echo "<div id='lti-warning' class='updated fade'><p><strong>" . __( 'LTI Disabled.', 'lti_network_warning' ) . '</strong> ' . sprintf( __( 'You must <a href="%1$s">create a network</a> for it to work.', 'wordpress-mu-lti' ), 'http://codex.wordpress.org/Create_A_Network' ) . '</p></div>';
}

function lti_listing( $rows, $heading = '' ) {
	if ( $rows ) {
		if ( $heading != '' ) {
			echo "<h3>$heading</h3>";
		}
		echo '<table class="widefat" cellspacing="0"><thead><tr><th>' . __( 'Consumer name', 'wordpress-mu-lti' ) . '</th><th>' . __( 'Consumer key', 'wordpress-mu-lti' ) . '</th><th>' . __( 'LTI Version', 'wordpress-mu-lti' ) . '</th><th>' . __( 'Enabled', 'wordpress-mu-lti' ) . '</th><th>' . __( 'Edit', 'wordpress-mu-lti' ) . '</th><th>' . __( 'Delete', 'wordpress-mu-lti' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo "<tr><td>{$row->name}</td>";
			echo "<td>{$row->consumer_key256}</td>";
			//echo $row->has_custom_username_parameter == 1 ? __( 'Yes',  'wordpress-mu-lti' ) : __( 'No',  'wordpress-mu-lti' );
			echo '<td>';
			//echo $row->custom_username_parameter;
			echo $row->lti_version;
			echo '</td><td>';
			echo $row->enabled == 1 ? __( 'Yes', 'wordpress-mu-lti' ) : __( 'No', 'wordpress-mu-lti' );
			echo "</td><td><form method='POST'><input type='hidden' name='action' value='edit' /><input type='hidden' name='consumer_key' value='{$row->consumer_key256}' />";
			wp_nonce_field( 'lti' );
			echo "<input type='submit' class='button-secondary' value='" . __( 'Edit', 'wordpress-mu-lti' ) . "' /></form></td><td><form method='POST'><input type='hidden' name='action' value='del' /><input type='hidden' name='consumer_key' value='{$row->consumer_key256}' />";
			wp_nonce_field( 'lti' );
			echo "<input type='submit' class='button-secondary' value='" . __( 'Del', 'wordpress-mu-lti' ) . "' /></form>";
			echo '</td></tr>';
		}
		echo '</table>';
	}
}

function lti_network_pages() {
	add_submenu_page( 'settings.php', 'LTI Consumers Keys', 'LTI Consumers Keys', 'manage_options', 'lti_consumer_keys_admin', 'lti_consumer_keys_admin' );
}

add_action( 'network_admin_menu', 'lti_network_pages' );
