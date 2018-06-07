<?php
/*
Plugin Name: UOE LTI
Description: Allows LMSs to connect to site and create blogs
Author: Richard Lawson (richard.lawson@ed.ac.uk)
Version: 1.0
*/

require_once ABSPATH . "wp-includes/pluggable.php";
require_once "vendor/autoload.php";
require_once "classes/EdToolProvider.php";
require_once "classes/user_lti_roles.php";
require_once "classes/blog_handler_factory.php";
require_once "classes/blog_handler.php";
require_once "classes/course_blog_handler.php";
require_once "classes/student_blog_handler.php";
require_once "ed-db-functions.php";
require_once "admin_pages.php";

use IMSGlobal\LTI\ToolProvider\DataConnector;
use IMSGlobal\LTI\ToolProvider;

//TODO remove set timezone on production
date_default_timezone_set('Europe/London');

function lti_get_db_connector() {
    global $wpdb;
    $db = new PDO( "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD );
    return DataConnector\DataConnector::getDataConnector( $wpdb->base_prefix, $db );
}

function lti_add_consumer() {
    $key = 'rlawson3';
    $consumer = new ToolProvider\ToolConsumer( $key, lti_get_db_connector() );
    $consumer->name = 'Richard Moodle Site';
    $consumer->secret = 'aberdeen31';
    $consumer->enabled = TRUE;
    $consumer->save();
}

add_action( 'parse_request', 'lti_do_launch' );

function lti_do_launch() {

    if ( lti_is_basic_lti_request() && is_main_site() ) {

        lti_destroy_session();

        $tool = new EdToolProvider( lti_get_db_connector() );
        $tool->handleRequest();

        if( ! isset( $_SESSION['lti_okay'] ) ) {
            wp_die( 'There is a problem with your lti connection.' );
        }

        $blog_type = isset( $_REQUEST['custom_blog_type'] ) ? $_REQUEST['custom_blog_type'] : '';

        if( is_student_blog_and_non_student( $blog_type, $tool ) ) {
            $course_id = $_REQUEST['lis_course_section_sourcedid'];
            $resource_link_id = $_REQUEST['resource_link_id'];
            lti_show_staff_student_blogs_for_course( $course_id, $resource_link_id, $tool );
            return;
        }

        $user = first_or_create_user( lti_get_user_data( $tool ) );

        $blog_handler = Blog_Handler_Factory::instance( $blog_type );
        $blog_handler->init( lti_get_site_data(), $user );
        $blog_id = $blog_handler->first_or_create_blog();

        $user_roles = new User_LTI_Roles( $tool->user->roles );
        $blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );

        lti_signin_user( $user, $blog_id );

    }
}

function lti_is_basic_lti_request() {

    $good_message_type = isset( $_REQUEST["lti_message_type"] ) ? $_REQUEST["lti_message_type"] == "basic-lti-launch-request" : false;
    $good_lti_version = isset( $_REQUEST["lti_version"] ) ? $_REQUEST["lti_version"] == "LTI-1p0" : false;
    $oauth_consumer_key = isset( $_REQUEST["oauth_consumer_key"] );
    $resource_link_id = isset( $_REQUEST["resource_link_id"] );

    if ( $good_message_type && $good_lti_version && $oauth_consumer_key && $resource_link_id ) {
        return true;
    }

    return false;
}

function lti_destroy_session() {
    wp_logout();
    wp_set_current_user(0);
    session_start();
    $_SESSION = array();
    session_destroy();
    session_start();
}

function is_student_blog_and_non_student( $type, EdToolProvider $tool ) {
    return ( $type == 'student' && ! $tool->user->isLearner() );
}

function lti_get_user_data( EdToolProvider $tool ) {

    //TODO Look at giving user random password.
    $user_data = array (
        'username' => $_REQUEST['ext_user_username'],
        'email' => $tool->user->email,
        'firstname' => $tool->user->firstname,
        'lastname' => $tool->user->lastname,
        'password' => 'changeme'
    );

    return $user_data;
}

function lti_get_site_data() {

    $site_data = array(
        'course_id' => $_REQUEST['lis_course_section_sourcedid'],
        'course_title' => $_REQUEST['context_title'],
        'domain' => get_current_site()->domain,
        'resource_link_id' => $_REQUEST["resource_link_id"],
        'username' => $_REQUEST['ext_user_username']
    );

    return $site_data;
}

function first_or_create_user( array $data ) {
    $user = get_user_by( 'login', $data['username'] );

    if ( ! $user ) {
        $user_id = wpmu_create_user( $data['username'], $data['password'], $data['email'] );
        //TODO handle error - if user has same email as existing user, wpmu_create_user will fail silently. Need proper handling
        if ( ! $user_id ) {
            wp_die('Email is already being used by another user');
        }

        $user = get_userdata( $user_id );

        // add name info to user
        $user->first_name = $data['firstname'];
        $user->last_name = $data['lastname'];

        wp_update_user( $user );
    }

    return $user;
}

function lti_signin_user( $user, $blog_id ) {

    switch_to_blog( $blog_id );

    clean_user_cache( $user->ID );
    wp_clear_auth_cookie();
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true, false );

    update_user_caches( $user );

    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url() );
    }
}

function lti_show_staff_student_blogs_for_course( $course_id, $resource_link_id, EdToolProvider $tool ) {
    lti_add_staff_info_to_session( lti_get_user_data( $tool ), new User_LTI_Roles( $tool->user->roles ), $course_id, $resource_link_id );
    lti_render_student_blogs_list_view( $course_id, $resource_link_id );
}

function lti_add_staff_info_to_session( array $user_data, User_LTI_Roles $user_roles, $course_id, $resource_link_id ) {
    $_SESSION['lti_staff'] = true;
    $_SESSION['lti_user_roles'] = $user_roles;
    $_SESSION['lti_staff_user_data'] = $user_data;
    $_SESSION['lti_staff_course_id'] = $course_id;
    $_SESSION['lti_resource_link_id'] = $resource_link_id;
}

function lti_render_student_blogs_list_view( $course_id, $resource_link_id ) {
    global $wpdb;

    $blog_type = 'student';

    $blogs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blogs_meta INNER JOIN {$wpdb->prefix}blogs ON {$wpdb->prefix}blogs.blog_id = {$wpdb->prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
            $course_id,
            $resource_link_id,
            $blog_type

        )
    );

    get_template_part('header');

    ?>
        <div style="width:80%; margin: 0 auto">
    <?php

    if( empty( $blogs ) ) {
        ?>

        <p>No Student Blogs have been created for this course.</p>

        <?php
    } else {
        ?>

        <h2>Student Blogs For Course</h2>
        <ul>
        <?php foreach ( $blogs as $blog ): ?>
            <li>
                <a href="index.php?lti_staff_view_blog=true&blog_id=<?php echo $blog->blog_id ?>"><?php echo $blog->student_firstname ?> <?php echo $blog->student_lastname ?> Blog</a>
            </li>
        <?php endforeach ?>

        <?php

    }

    ?>
        <br><br>
        </div>
    <?php

    get_template_part('footer');

    exit;
}


add_action( 'parse_request', 'lti_add_staff_to_student_blog' );

function lti_add_staff_to_student_blog() {
    if( isset( $_REQUEST['lti_staff_view_blog'] ) && $_REQUEST['lti_staff_view_blog'] == 'true' ) {

        session_start();

        if( ! isset( $_SESSION['lti_staff'] ) ) {
            wp_die( 'You do not have permssion to view this page' );
            return;
        }

        $blog_id = $_REQUEST['blog_id'];
        $course_id = $_SESSION['lti_staff_course_id'];
        $user_roles = $_SESSION['lti_user_roles'];

        // If someone has been messing about with the blog id and the blog has nothing to do with the current course redirect them to the home page
        if( ! Blog_Handler::is_course_blog( $course_id, $blog_id ) ) {
            lti_redirect_user_to_blog_without_login( $blog_id );
            return;
        }

        $user = first_or_create_user( $_SESSION['lti_staff_user_data'] );

        $blog_handler = Blog_Handler_Factory::instance( 'student' );
        $blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );

        lti_signin_user( $user, $blog_id );
    }
}

function lti_redirect_user_to_blog_without_login( $blog_id ) {
    switch_to_blog( $blog_id );
    wp_safe_redirect( home_url() );
}

function lti_do_setup() {
    lti_maybe_create_db();
    lti_maybe_create_site_blogs_meta_table();
}

// do plugin setup
lti_do_setup();




/*
function lti_do_course_blog_launch ( EdToolProvider $tool ) {

    $user_data = lti_get_user_data( $tool );

    $site_data = array (
        // if not using ns cloner to create site, put slashes around path; e.g., /mynewsite/
        'path' => lti_get_friendly_path( $_REQUEST['lis_course_section_sourcedid'] ),
        'title' => $_REQUEST['context_title'],
        'domain' => get_current_site()->domain,
    );

    $site_meta = lti_get_site_meta( $tool );
    $site_meta['blog_type'] = 'course';

    $user = first_or_create_user( $user_data );
    $blog_id = first_or_create_course_blog( $site_data, $site_meta );

    if( ! is_user_member_of_blog( $user->ID, $blog_id )) {
        $wp_role = lti_get_wordpress_role_from_tool( $tool );
        add_user_to_blog( $blog_id, $user->ID, $wp_role );
    }

    lti_signin_user( $user, $blog_id );

}
*/


/*
function lti_do_student_blog_launch ( EdToolProvider $tool ) {

    // create blog for student or sign them in
    if( $tool->user->isLearner() ) {

        $user_data = lti_get_user_data( $tool );

        // remember to put slashes around path
        $site_data = array(
            // if not using ns cloner to create site, put slashes around path; e.g., /mynewsite/
            'path' => lti_get_friendly_path($_REQUEST['lis_course_section_sourcedid']) . '-' . $_REQUEST['ext_user_username'],
            'title' => $_REQUEST['context_title'],
            'domain' => get_current_site()->domain,
        );

        $site_meta = lti_get_site_meta( $tool );
        $site_meta['blog_type'] = 'student';

        $user = first_or_create_user($user_data);
        $blog_id = first_or_create_student_blog( $site_data, $site_meta, $user );

        lti_signin_user($user, $blog_id);

        return;
    }

    // set session vars
    lti_show_student_blogs_for_course( $_REQUEST['lis_course_section_sourcedid'] );
}

*/


/*
function lti_get_site_meta( EdToolProvider $tool ) {

    $site_meta = array (
        'course_id' => $_REQUEST['lis_course_section_sourcedid'],
        'resource_link_id' => $_REQUEST['resource_link_id'],
    );

    return $site_meta;
}
*/


/*
function lti_get_friendly_path( $string ) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-\_]/', '', $string); // Removes special chars.
    $string = strtolower($string); // Convert to lowercase
    return $string;
}
*/



/*
 * check if blog already exists. if it doesn't create one
 */
/*
function first_or_create_course_blog( array $data, array $meta ) {
    // WP expects path to start and end with slash; e.g., /mynewsite/
    $path = "/{$data['path']}/";
    if ( ! domain_exists( $data['domain'], $path ) ) {
        $blog_id = do_ns_cloner_create( $data );
        lti_add_blog_meta( $blog_id, $meta );
        // use wp create_blog if not using ns cloner to create site
        // $blog_id = create_blog( $data['domain'], $data['path'], $data['title'], get_network_root_user_id(), get_current_site()->id );
        return $blog_id;
    }
    return get_blog_id_from_url( $data['domain'], $path );
}
*/

/*
 * check if blog already exists. if it doesn't create one. Also add student to blog as admin
 */

/*
function first_or_create_student_blog( array $data, array $meta, $user ) {
    // WP expects path to start and end with slash; e.g., /mynewsite/
    $path = "/{$data['path']}/";
    if ( ! domain_exists( $data['domain'], $path ) ) {
        $blog_id = do_ns_cloner_create( $data );
        // use wp create_blog if not using ns cloner to create site
        // $blog_id = create_blog( $data['domain'], $data['path'], $data['title'], get_network_root_user_id(), get_current_site()->id );
        lti_add_blog_meta( $blog_id, $meta, $user );
        add_user_to_blog( $blog_id, $user->ID, 'administrator' );

        return $blog_id;
    }
    return get_blog_id_from_url( $data['domain'], $path );
}
*/


/*
function do_ns_cloner_create( array $data ) {

    $_POST['action'] = 'process';
    $_POST['clone_mode'] = 'core';
    // TODO Set this to template site id
    $_POST['source_id'] = 5;
    $_POST['target_name'] = $data['path'];
    $_POST['target_title'] = $data['title'];
    $_POST['disable_addons'] = true;
    $_POST['clone_nonce'] = wp_create_nonce('ns_cloner');

    // Setup clone process and run it.
    $ns_site_cloner = new ns_cloner();
    $ns_site_cloner->process();

    $site_id = $ns_site_cloner->target_id;
    $site_info = get_blog_details( $site_id );
    if ( $site_info ) {
        return $site_id;
        // Clone successful!
    }

    throw new Exception('NS CLoner did not create site');

    //TODO handle unsucessfull clone
}
*/

/*
function lti_add_blog_meta( $blog_id, array $meta,  $user = null) {
    global $wpdb;

    $firstname = '';
    $lastname = '';

    if( $user ) {
        $firstname = $user->first_name;
        $lastname = $user->last_name;
    }

    $wpdb->insert($wpdb->prefix . 'blogs_meta', array(
        'blog_id' => $blog_id,
        'course_id' => $meta['course_id'],
        'resource_link_id' => $meta['resource_link_id'],
        'blog_type' => $meta['blog_type'],
        'student_firstname' => $firstname,
        'student_lastname' => $lastname,
    ));
}
*/

/*
function get_network_root_user_id () {
    $username = get_super_admins()[0];
    $user = get_user_by( 'login' , $username );
    return $user->ID;
}
*/

/*
function create_blog ( $domain, $path, $title, $user_id, $current_site_id ) {
    return wpmu_create_blog($domain, $path, $title, $user_id, array('public' => 1), $current_site_id);
}
*/

/*
function lti_get_wordpress_role_from_tool( EdToolProvider $tool ) {
    if( $tool->user->isLearner() ) {
        return 'subscriber';
    }

    // else must be admin or staff
    return 'administrator';
}
*/


