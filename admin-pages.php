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

	echo '<h2>LTI: Consumers Keys</h2>';
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
					echo '<h3>Provider not found</h3>';
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
					echo '<p><strong>Provider Updated</strong></p>';
				} else {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}lti2_consumer ( `name`, `consumer_key256`, `secret`, `enabled`, `lti_version`) VALUES ( %s, %s, %s, %d, %s)", $_POST['name'], $_POST['consumer_key'], $_POST['secret'], $enabled, $_POST['lti_version'] ) );
					echo '<p><strong>Provider Added</strong></p>';
				}
				break;
			case 'del':
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 = %s", $consumer_key ) );
				echo '<p><strong>Provider Deleted</strong></p>';
				break;
		}
	}

	if ( ! $is_editing ) {
		echo '<h3>Search</h3>';
		$search = '';
		if ( isset( $_POST['search_txt'] ) ) {
			$search = '%' . $wpdb->esc_like( addslashes( $_POST['search_txt'] ) ) . '%';

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->base_prefix}lti2_consumer WHERE consumer_key256 LIKE %s OR name LIKE %s",
					[ $search, $search ]
				)
			);

			lti_listing( $rows, 'Searching for ' . esc_html( $_POST['search_txt'] ) ) );
		}
		echo '<form method="POST">';
		wp_nonce_field( 'lti' );
		echo '<input type="hidden" name="action" value="search" />';
		echo '<p>';
		echo 'Search:';
		echo ' <input type="text" name="search_txt" value=""></p>';
		echo ' <input type="hidden" name="consumer_key" value=""></p>';
		echo '<p><input type="submit" class="button-secondary" value="Search"></p>';
		echo '</form><br />';
		lti_edit();
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}lti2_consumer LIMIT 0,20" );
		lti_listing( $rows );
	}
}

function lti_do_validation() {

	$errors = array();

	if ( '' == $_POST['name'] ) {
		$errors[] = 'Name is required';
	}

	if ( '' == $_POST['consumer_key'] ) {
		$errors[] = 'Consumer key is required';
	}

	if ( '' == $_POST['secret'] ) {
		$errors[] = 'Secret is required';
	}

	return $errors;
}


function lti_edit( $row = false ) {
	$is_new = false;
	if ( is_object( $row ) ) {
		echo '<h3>Edit LTI</h3>';
	} else {
		echo '<h3>New LTI</h3>';

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
	echo '<tr><th>Name</th><td><input type='text' name='name' value='{$row->name}' required/></td></tr>\n";
	echo '<tr><th>Consumer key</th><td><input type='text' name='consumer_key' value='{$row->consumer_key256}' " . ( ! $is_new ? 'readonly="readonly"' : '' ) . " required/></td></tr>\n";
	echo '<tr><th>Secret</th><td><input type='text' name='secret' value='{$row->secret}' required/></td></tr>\n";
	echo '<tr><th>LTI Version</th><td><select name='lti_version'><option value='LTI-1p0' " . ( 'LTI-1p0' == $row->lti_version ? 'selected' : '' ) . ">LTI-1p0</option><option value='LTI-2p0' " . ( 'LTI-2p0' == $row->lti_version ? 'selected' : '' ) . ">LTI-2p0</option></select></td></tr>\n";
	echo '<tr><th>Enabled</th><td><input type='checkbox' name='enabled' value='1' ";
	echo 1 == $row->enabled ? 'checked=1 ' : ' ';
	echo "/></td></tr>\n";
	echo '</table>';
	echo '<p><input type="submit" class="button-primary" value="Save" ></p></form><br /><br />";
}


function lti_network_warning() {
	echo '<div id="lti-warning" class="updated fade"><p><strong>LTI Disabled</strong>You must <a href="http://codex.wordpress.org/Create_A_Network">create a network</a> for it to work.</p></div>';
}

function lti_listing( $rows, $heading = '' ) {
	if ( $rows ) {
		if ( '' != $heading ) {
			echo "<h3>$heading</h3>";
		}
		echo '<table class="widefat" cellspacing="0"><thead><tr><th>Consumer name</th><th>Consumer key</th><th>LTI Version</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr></thead><tbody>';

		foreach ( $rows as $row ) {
			echo "<tr><td>{$row->name}</td>";
			echo "<td>{$row->consumer_key256}</td>";
			echo '<td>';
			echo $row->lti_version;
			echo '</td><td>';
			echo 1 == $row->enabled ? 'Yes' : 'No';
			echo '</td><td><form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="consumer_key" value="' . $row->consumer_key256} . '">';
			wp_nonce_field( 'lti' );
			echo '<input type="submit" class="button-secondary" value="Edit"></form></td><td><form method="POST"><input type="hidden" name="action" value="del"><input type="hidden" name="consumer_key" value="' . $row->consumer_key256} . '">';
			wp_nonce_field( 'lti' );
			echo '<input type="submit" class="button-secondary" value="Del"></form>';
			echo '</td></tr>';
		}

		echo '</table>';
	}
}

function lti_network_pages() {
	add_submenu_page( 'settings.php', 'LTI Consumers Keys', 'LTI Consumers Keys', 'manage_options', 'lti_consumer_keys_admin', 'lti_consumer_keys_admin' );
}

add_action( 'network_admin_menu', 'lti_network_pages' );
