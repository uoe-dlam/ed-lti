<?php
/*
Plugin Name: UoE LTI
Description: Allows LMSs to connect to our website and create blogs.
Author: DLAM Applications Development Team
Version: 1.0
*/

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

//TODO Look at handling blog category.

function lti_get_db_connector() {
    global $wpdb;
    $db = new PDO( "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD );
    return DataConnector\DataConnector::getDataConnector( $wpdb->base_prefix, $db );
}

/* -------------------- HANDLE LTI LAUNCH -------------------- */

add_action( 'parse_request', 'lti_do_launch' );

function lti_do_launch() {

    if ( lti_is_basic_lti_request() && is_main_site() ) {

        lti_destroy_session();

        $tool = new EdToolProvider( lti_get_db_connector() );
        $tool->handleRequest();

        if( ! isset( $_SESSION['lti_okay'] ) ) {
            wp_die( 'There is a problem with your lti connection.', 200 );
        }

        $blog_type = isset( $_REQUEST['custom_blog_type'] ) ? $_REQUEST['custom_blog_type'] : '';

        if( is_student_blog_and_non_student( $blog_type, $tool ) ) {
            $course_id = $_REQUEST['context_label'];
            $resource_link_id = $tool->resourceLink->getId();
            lti_show_staff_student_blogs_for_course( $course_id, $resource_link_id, $tool );
            return;
        }

        $user = first_or_create_user( lti_get_user_data( $tool ) );

        $blog_handler = Blog_Handler_Factory::instance( $blog_type );
        $blog_handler->init( lti_get_site_data(), $user );
        $blog_id = $blog_handler->first_or_create_blog();

        $user_roles = new User_LTI_Roles( $tool->user->roles );
        $blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );
        $blog_handler->add_user_to_top_level_blog( $user );

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
    if ( session_status() == PHP_SESSION_NONE ) {
        session_start();
    }
    $_SESSION = array();
    session_destroy();
    session_start();
}

function is_student_blog_and_non_student( $type, EdToolProvider $tool ) {
    return ( $type == 'student' && ! $tool->user->isLearner() );
}

function lti_get_user_data( EdToolProvider $tool ) {
    // The LTI specs tell us that username should be set in the 'lis_person_sourcedid' param, but moodle doesn't do this. Moodle seems to use 'ext_user_username' instead
    $username = $_REQUEST['lis_person_sourcedid'] != '' ? $_REQUEST['lis_person_sourcedid'] : $_REQUEST['ext_user_username'];
    //TODO Look at giving user random password.
    $user_data = array (
        'username' => $username,
        'email' => $tool->user->email,
        'firstname' => $tool->user->firstname,
        'lastname' => $tool->user->lastname,
        'password' => 'changeme'
    );

    return $user_data;
}

function lti_get_site_data() {

    $site_category = isset( $_REQUEST['custom_site_category'] ) ? $_REQUEST['custom_site_category'] : 1;

    $username = $_REQUEST['lis_person_sourcedid'] != '' ? $_REQUEST['lis_person_sourcedid'] : $_REQUEST['ext_user_username'];

    $site_data = array(
        'course_id' => $_REQUEST['context_label'],
        'course_title' => $_REQUEST['context_title'],
        'domain' => get_current_site()->domain,
        'resource_link_id' => $_REQUEST["resource_link_id"],
        'username' => $username,
        'site_category' => $site_category,
        'source_id' => get_site_option( 'default_site_template_id' )
    );

    return $site_data;
}

function first_or_create_user( array $data ) {
    $user = get_user_by( 'login', $data['username'] );

    if ( ! $user ) {
        $user_id = wpmu_create_user( $data['username'], $data['password'], $data['email'] );

        if ( ! $user_id ) {
            wp_die( 'This Email address is already being used by another user. Please contact <a href="' . get_site_option( 'is_helpline_url' ) . '">IS Helpline</a> for assistance.', 200 );
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
    wp_set_auth_cookie( $user->ID, true, true );

    update_user_caches( $user );

    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url() );
        exit;
    }
}

/* -------------------- SHOW LIST OF STUDENT BLOGS TO STAFF -------------------- */

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
            "SELECT * FROM {$wpdb->base_prefix}blogs_meta INNER JOIN {$wpdb->base_prefix}blogs ON {$wpdb->base_prefix}blogs.blog_id = {$wpdb->base_prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
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
            <?php $blog_details = get_blog_details( $blog->blog_id ); ?>
            <li>
                <a href="index.php?lti_staff_view_blog=true&blog_id=<?php echo $blog->blog_id ?>"><?php echo $blog_details->blogname; ?></a>
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

        if ( session_status() == PHP_SESSION_NONE ) {
            session_start();
        }

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
    exit;
}

/* -------------------- DO PLUGIN INSTALL STUFF BELOW -------------------- */

function lti_do_setup() {
    lti_maybe_create_db();
    lti_maybe_create_site_blogs_meta_table();
}

// do plugin setup
register_activation_hook( __FILE__, 'lti_do_setup' );




