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
    if ( !empty( $_POST[ 'action' ] ) ) {
        check_admin_referer( 'lti' );
        $consumer_key = strtolower( $_POST[ 'consumer_key' ] );
        switch( $_POST[ 'action' ] ) {
            case "edit":
                $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->ltitable} WHERE consumer_key = %s", $consumer_key ) );
                if ( $row ) {
                    lti_edit( $row );
                    $is_editing = true;
                } else {
                    echo "<h3>" . __( 'Provider not found', 'wordpress-mu-lti' ) . "</h3>";
                }
                break;
            case "save":
                $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->ltitable} WHERE consumer_key = %s", $consumer_key ) );
                if ( $row ) {
                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->ltitable} SET  consumer_name = %s, name = %s, secret = %s, enabled = %d, lti_version = %s, custom_username_parameter = %s, has_custom_username_parameter = %d  WHERE consumer_key = %s", $_POST[ 'consumer_name' ], $_POST[ 'name' ], $_POST[ 'secret' ], $_POST[ 'enabled' ], $_POST[ 'lti_version' ], $_POST[ 'custom_username_parameter' ], $_POST[ 'has_custom_username_parameter' ], $consumer_key ) );
                    echo "<p><strong>" . __( 'Provider Updated', 'wordpress-mu-lti' ) . "</strong></p>";
                } else {
                    $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->ltitable} ( `consumer_name`, `name`, `consumer_key`, `secret`, `enabled`, `lti_version`, `custom_username_parameter`, `has_custom_username_parameter`) VALUES ( %s, %s, %s, %s, %d, %s, %s, %d)", $_POST[ 'consumer_name' ], $_POST[ 'name' ], $consumer_key, $_POST[ 'secret' ], $_POST[ 'enabled' ], $_POST[ 'lti_version' ], $_POST[ 'custom_username_parameter' ], $_POST[ 'has_custom_username_parameter' ] ) );
                    echo "<p><strong>" . __( 'Provider Added', 'wordpress-mu-lti' ) . "</strong></p>";
                }
                break;
            case "del":
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->ltitable} WHERE consumer_key = %s", $consumer_key ) );
                echo "<p><strong>" . __( 'Provider Deleted', 'wordpress-mu-lti' ) . "</strong></p>";
                break;
        }
    }

    if ( ! $is_editing ) {
        echo "<h3>" . __( 'Search', 'wordpress-mu-lti' ) . "</h3>";
        $escaped_search = addslashes($_POST['search_txt']);
        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->ltitable} WHERE consumer_key LIKE '%{$escaped_search}%' OR consumer_name LIKE '%{$escaped_search}%'" );
        lti_listing( $rows, sprintf( __( "Searching for %s", 'wordpress-mu-lti' ), esc_html(  $_POST[ 'search' ] ) ) );
        echo '<form method="POST">';
        wp_nonce_field( 'lti' );
        echo '<input type="hidden" name="action" value="search" />';
        echo '<p>';
        echo _e( "Search:", 'wordpress-mu-lti' );
        echo " <input type='text' name='search_txt' value='' /></p>";
        echo "<p><input type='submit' class='button-secondary' value='" . __( 'Search', 'wordpress-mu-lti' ) . "' /></p>";
        echo "</form><br />";
        lti_edit();
        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->ltitable} LIMIT 0,20" );
        lti_listing( $rows );
    }
}


function lti_edit( $row = false ) {
    $is_new = false;
    if ( is_object( $row ) ) {
        echo "<h3>" . __( 'Edit LTI', 'wordpress-mu-lti' ) . "</h3>";
    }  else {
        echo "<h3>" . __( 'New LTI', 'wordpress-mu-lti' ) . "</h3>";
        $row = new stdClass();
        $row->consumer_name = '';
        $row->name = '';
        $row->consumer_key = '';
        $row->lti_version = '';
        $row->secret = '';
        $row->enabled = 1;
        $row->has_custom_username_parameter = 0;
        $row->custom_username_parameter = '';
        $is_new = true;
    }

    echo "<form method='POST'><input type='hidden' name='action' value='save' />";
    wp_nonce_field( 'lti' );
    echo "<table class='form-table'>\n";
    echo "<tr><th>" . __( 'Name', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='name' value='{$row->name}' /></td></tr>\n";
    echo "<tr><th>" . __( 'Consumer name', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='consumer_name' value='{$row->consumer_name}' /></td></tr>\n";
    echo "<tr><th>" . __( 'Consumer key', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='consumer_key' value='{$row->consumer_key}' ".(!$is_new?'readonly="readonly"':'')."/></td></tr>\n";
    echo "<tr><th>" . __( 'Secret', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='secret' value='{$row->secret}' /></td></tr>\n";
    echo "<tr><th>" . __( 'LTI Version', 'wordpress-mu-lti' ) . "</th><td><select name='lti_version'><option value='LTI-1p0' ".($row->lti_version=='LTI-1p0'?'selected':'').">LTI-1p0</option><option value='LTI-2p0' ".($row->lti_version=='LTI-2p0'?'selected':'').">LTI-2p0</option></select></td></tr>\n";

    /*echo "<tr><th>" . __( 'Consumer guid', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='consumer_guid' value='{$row->consumer_guid}' /></td></tr>\n";
    */
    echo "<tr><th>" . __( 'Custom username parameter', 'wordpress-mu-lti' ) . "</th><td><input type='text' name='custom_username_parameter' value='{$row->custom_username_parameter}' /></td></tr>\n";


    echo "<tr><th>" . __( 'Has custom username', 'wordpress-mu-lti' ) . "</th><td><input type='checkbox' name='has_custom_username_parameter' value='1' ";

    echo $row->has_custom_username_parameter == 1 ? 'checked=1 ' : ' ';
    echo "/></td></tr>\n";
    echo "<tr><th>" . __( 'Enabled', 'wordpress-mu-lti' ) . "</th><td><input type='checkbox' name='enabled' value='1' ";
    echo $row->enabled == 1 ? 'checked=1 ' : ' ';
    echo "/></td></tr>\n";
    echo "</table>";
    echo "<p><input type='submit' class='button-primary' value='" .__( 'Save', 'wordpress-mu-lti' ). "' /></p></form><br /><br />";
}


function lti_network_warning() {
    echo "<div id='lti-warning' class='updated fade'><p><strong>".__( 'LTI Disabled.', 'lti_network_warning' )."</strong> ".sprintf(__('You must <a href="%1$s">create a network</a> for it to work.', 'wordpress-mu-lti' ), "http://codex.wordpress.org/Create_A_Network")."</p></div>";
}

function lti_listing( $rows, $heading = '' ) {
    if ( $rows ) {
        if ( file_exists( ABSPATH . 'wp-admin/network/site-info.php' ) ) {
            $edit_url = network_admin_url( 'site-info.php' );
        } elseif ( file_exists( ABSPATH . 'wp-admin/ms-sites.php' ) ) {
            $edit_url = admin_url( 'ms-sites.php' );
        } else {
            $edit_url = admin_url( 'wpmu-blogs.php' );
        }
        if ( $heading != '' )
            echo "<h3>$heading</h3>";
        echo '<table class="widefat" cellspacing="0"><thead><tr><th>'.__( 'Consumer name', 'wordpress-mu-lti' ).'</th><th>'.__( 'Consumer key', 'wordpress-mu-lti' ).'</th><th>'.__( 'LTI Version', 'wordpress-mu-lti' ).'</th><th>'.__( 'Enabled', 'wordpress-mu-lti' ).'</th><th>'.__( 'Edit', 'wordpress-mu-lti' ).'</th><th>'.__( 'Delete', 'wordpress-mu-lti' ).'</th></tr></thead><tbody>';
        foreach( $rows as $row ) {
            echo "<tr><td>{$row->consumer_name}</td>";
            echo "<td>{$row->consumer_key}</td>";
            //echo $row->has_custom_username_parameter == 1 ? __( 'Yes',  'wordpress-mu-lti' ) : __( 'No',  'wordpress-mu-lti' );
            echo "<td>";
            //echo $row->custom_username_parameter;
            echo $row->lti_version;
            echo "</td><td>";
            echo $row->enabled == 1 ? __( 'Yes',  'wordpress-mu-lti' ) : __( 'No',  'wordpress-mu-lti' );
            echo "</td><td><form method='POST'><input type='hidden' name='action' value='edit' /><input type='hidden' name='consumer_key' value='{$row->consumer_key}' />";
            wp_nonce_field( 'lti' );
            echo "<input type='submit' class='button-secondary' value='" .__( 'Edit', 'wordpress-mu-lti' ). "' /></form></td><td><form method='POST'><input type='hidden' name='action' value='del' /><input type='hidden' name='consumer_key' value='{$row->consumer_key}' />";
            wp_nonce_field( 'lti' );
            echo "<input type='submit' class='button-secondary' value='" .__( 'Del', 'wordpress-mu-lti' ). "' /></form>";
            echo "</td></tr>";
        }
        echo '</table>';
    }
}

function lti_network_pages() {
    add_submenu_page( 'settings.php', 'LTI Consumers Keys', 'LTI Consumers Keys', 'manage_options', 'lti_consumer_keys_admin', 'lti_consumer_keys_admin' );
}

add_action( 'network_admin_menu', 'lti_network_pages' );