<?php
/**
 * Class for coordinating main LTI functions.
 *
 * @author    Learning Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once 'class-ed-tool-provider.php';
require_once 'class-user-lti-roles.php';
require_once 'class-blog-handler-factory.php';
require_once 'class-blog-handler.php';
require_once 'class-course-blog-handler.php';
require_once 'class-student-blog-handler.php';
require_once 'class-blog-creator-factory.php';
require_once 'interface-blog-creator.php';
require_once 'class-ns-cloner-blog-creator.php';
require_once 'class-wp-blog-creator.php';
require_once 'class-ed-lti-data.php';
require_once 'class-ed-lti-settings.php';
require_once 'class-ed-lti-config.php';

use IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector;

class Ed_LTI {

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		add_action( 'parse_request', [ $this, 'lti_do_launch' ] );
		add_action( 'parse_request', [ $this, 'lti_add_staff_to_student_blog' ] );

		new Ed_LTI_Settings();
		new Ed_LTI_Config();
	}

	/**
	 * Activate the plugin
	 *
	 * @return void
	 */
	public static function activate() {
		$lti_data = new Ed_LTI_Data();
		$lti_data->lti_maybe_create_db();
		$lti_data->lti_maybe_create_site_blogs_meta_table();
	}

	// TODO Look at handling blog category.

	/**
	 * Get a DB connector for the LTI connection package
	 *
	 * @return DataConnector
	 */
	private function lti_get_db_connector() {
		// phpcs:disable
		return DataConnector::getDataConnector(
			$this->wpdb->base_prefix,
			new PDO( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD )
		);
		// phpcs:enable
	}

	/**
	 * TODO: Need to figure out what this function does and write a description
	 *
	 * @return void
	 */
	public function lti_do_launch() {
		if ( $this->lti_is_basic_lti_request() && is_main_site() ) {
			$this->lti_destroy_session();

			$tool = new Ed_Tool_Provider( $this->lti_get_db_connector() );

			$tool->handleRequest();

			if ( ! isset( $_SESSION['lti_okay'] ) ) {
				wp_die( 'There is a problem with your lti connection.', 200 );
			}

            // phpcs:disable
			$blog_type = $_REQUEST['custom_blog_type'] ?? '';
            // phpcs:enable

			if ( $this->is_student_blog_and_non_student( $blog_type, $tool ) ) {
                // phpcs:disable
				$course_id = $_REQUEST['context_label'];
                // phpcs:enable

				// phpcs:disable
				$resource_link_id = $tool->resourceLink->getId();
				// phpcs:enable

				$this->lti_show_staff_student_blogs_for_course( $course_id, $resource_link_id, $tool );

				return;
			}

			$user = $this->first_or_create_user( $this->lti_get_user_data( $tool ) );
			$this->set_user_name_temporarily_to_vle_name( $user, $tool );

			$blog_handler = Blog_Handler_Factory::instance( $blog_type );
			$blog_handler->init( $this->lti_get_site_data(), $user );

			$make_private = get_site_option( 'lti_make_sites_private' ) ? true : false;
			$blog_id      = $blog_handler->first_or_create_blog( $make_private );

			$user_roles = new User_LTI_Roles( $tool->user->roles );
			$blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );
			$blog_handler->add_user_to_top_level_blog( $user );

			$this->lti_signin_user( $user, $blog_id );
		}
	}

	/**
	 * Check that the LTI request being received is a basic LTI request
	 *
	 * @return bool
	 */
	private function lti_is_basic_lti_request() {
        // phpcs:disable
		$good_message_type = isset( $_REQUEST['lti_message_type'] )
							? 'basic-lti-launch-request' === $_REQUEST['lti_message_type']
							: false;

		$good_lti_version   = isset( $_REQUEST['lti_version'] ) ? 'LTI-1p0' === $_REQUEST['lti_version'] : false;
		$oauth_consumer_key = isset( $_REQUEST['oauth_consumer_key'] );
		$resource_link_id   = isset( $_REQUEST['resource_link_id'] );
        // phpcs:enable

		return $good_message_type && $good_lti_version && $oauth_consumer_key && $resource_link_id;
	}

	/**
	 * Destroy the LTI session
	 *
	 * @return void
	 */
	private function lti_destroy_session() {
		wp_logout();
		wp_set_current_user( 0 );

		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		$_SESSION = [];

		session_destroy();
		session_start();
	}

	/**
	 * Determine if a non-student user is accessing a student blog
	 *
	 * @param string           $blog_type
	 * @param Ed_Tool_Provider $tool
	 *
	 * @return bool
	 */
	private function is_student_blog_and_non_student( $blog_type, Ed_Tool_Provider $tool ) {
		return ( 'student' === $blog_type && ! $tool->user->isLearner() );
	}

	/**
	 * Get the user data passed via LTI
	 *
	 * @param Ed_Tool_Provider $tool
	 *
	 * @return array
	 */
	private function lti_get_user_data( Ed_Tool_Provider $tool ) {
		$username = $this->lti_get_username_from_request();

		$user_data = [
			'username'  => $username,
			'email'     => $tool->user->email,
			'firstname' => $tool->user->firstname,
			'lastname'  => $tool->user->lastname,
			'password'  => $this->random_string( 20, '0123456789ABCDEFGHIJKLMNOPQRSTUVWZYZabcdefghijklmnopqrstuvwxyz' ),
		];

		return $user_data;
	}

	/**
	 * Get username from $_REQUEST
	 *
	 * @return string
	 */
	private function lti_get_username_from_request() {
		// LTI specs tell us that username should be set in the 'lis_person_sourcedid' param, but moodle doesn't do
		// this. In some instances, Moodle uses 'ext_user_username' instead
        // phpcs:disable
		if ( isset( $_REQUEST['ext_user_username'] ) && '' !== $_REQUEST['ext_user_username'] ) {
			return $_REQUEST['ext_user_username'];
		}

        if ( isset( $_REQUEST['lis_person_sourcedid'] ) && '' !== $_REQUEST['lis_person_sourcedid'] ) {
            return $_REQUEST['lis_person_sourcedid'];
        }

		if ( isset( $_REQUEST['user_id'] ) && '' !== $_REQUEST['user_id'] ) {
			return $_REQUEST['user_id'];
		}

		$error_message = 'Your username has not be passed to our site. Please contact <a href="'
			. get_site_option( 'is_helpline_url' ) . '">IS Helpline</a> for assistance.';

		wp_die( $error_message, 200 );
        // phpcs:enable
	}

	/**
	 * Get site information for the LTI provider
	 *
	 * @return array
	 */
	private function lti_get_site_data() {
        // phpcs:disable
		$site_category = $_REQUEST['custom_site_category'] ?? 1;

        $username = $this->lti_get_username_from_request();

		return [
			'course_id'        => $_REQUEST['context_label'],
			'course_title'     => $_REQUEST['context_title'],
			'domain'           => get_current_site()->domain,
			'resource_link_id' => $_REQUEST['resource_link_id'],
			'username'         => $username,
			'site_category'    => $site_category,
			'source_id'        => get_site_option( 'default_site_template_id' ),
		];
        // phpcs:enable
	}

	/**
	 * Create a WordPress user or return the logged in user
	 *
	 * @param array $data
	 *
	 * @return WP_User
	 */
	private function first_or_create_user( array $data ) {
		$user = get_user_by( 'login', $data['username'] );

		if ( ! $user ) {
			$user_id = wpmu_create_user( $data['username'], $data['password'], $data['email'] );

			if ( ! $user_id ) {
				$error_message = 'This Email address is already being used by another user. Please contact <a href="'
					. get_site_option( 'is_helpline_url' ) . '">IS Helpline</a> for assistance.';

                // phpcs:disable
				wp_die( $error_message, 200 );
                // phpcs:enable
			}

			$user = get_userdata( $user_id );

			$user->first_name = $data['firstname'];
			$user->last_name  = $data['lastname'];

			wp_update_user( $user );

			// set current user to null so that no administrator is added to a newly created blog.
			wp_set_current_user( null );
		}

		return $user;
	}

	/**
	 * Set user first and last name to info supplied by vle. We do not want to save this permanently, however, as that could undo changes the user made on the WordPress end.
	 *
	 * @param WP_User          $user
	 * @param Ed_Tool_Provider $tool
	 *
	 * @return void
	 */
	private function set_user_name_temporarily_to_vle_name( $user, Ed_Tool_Provider $tool ) {
		if ( '' !== $tool->user->firstname || '' !== $tool->user->lastname ) {
			$user->first_name = $tool->user->firstname;
			$user->last_name  = $tool->user->lastname;
		}
	}

	/**
	 * Create a login session for a user that has visited the blog via an LTI connection
	 *
	 * @param WP_User $user
	 * @param int     $blog_id
	 *
	 * @return void
	 */
	private function lti_signin_user( $user, $blog_id ) {
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

	/**
	 * Create a list of student blogs for a given course for a member of staff
	 *
	 * @param string           $course_id
	 * @param string           $resource_link_id
	 * @param Ed_Tool_Provider $tool
	 *
	 * @return void
	 */
	private function lti_show_staff_student_blogs_for_course( $course_id, $resource_link_id, Ed_Tool_Provider $tool ) {
		$this->lti_add_staff_info_to_session(
			$this->lti_get_user_data( $tool ),
			new User_LTI_Roles( $tool->user->roles ),
			$course_id,
			$resource_link_id
		);

		$this->lti_render_student_blogs_list_view( $course_id, $resource_link_id );
	}

	/**
	 * Add staff details to a current LTI session
	 *
	 * @param array          $user_data
	 * @param User_LTI_Roles $user_roles
	 * @param string         $course_id
	 * @param string         $resource_link_id
	 *
	 * @return void
	 */
	private function lti_add_staff_info_to_session(
		array $user_data,
		User_LTI_Roles $user_roles,
		$course_id,
		$resource_link_id
	) {
		$_SESSION['lti_staff']            = true;
		$_SESSION['lti_user_roles']       = $user_roles;
		$_SESSION['lti_staff_user_data']  = $user_data;
		$_SESSION['lti_staff_course_id']  = $course_id;
		$_SESSION['lti_resource_link_id'] = $resource_link_id;
	}

	/**
	 * Render a list of student blogs
	 *
	 * @param string $course_id
	 * @param string $resource_link_id
	 *
	 * @return void
	 */
	private function lti_render_student_blogs_list_view( $course_id, $resource_link_id ) {
		$blog_type = 'student';

		$query = "SELECT * FROM {$this->wpdb->base_prefix}blogs_meta "
			. "INNER JOIN {$this->wpdb->base_prefix}blogs "
			. "ON {$this->wpdb->base_prefix}blogs.blog_id = {$this->wpdb->base_prefix}blogs_meta.blog_id "
			. 'WHERE course_id = %s '
			. 'AND resource_link_id = %s '
			. 'AND blog_type = %s';

        // phpcs:disable
		$blogs = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$course_id,
				$resource_link_id,
				$blog_type
			)
		);
        // phpcs:enable

		get_template_part( 'header' );

		echo '<div style="width:80%; margin: 0 auto">';

		if ( empty( $blogs ) ) {
			echo '<p>No Student Blogs have been created for this course.</p>';
		} else {
			echo '<h2>Student Blogs For Course</h2>';
			echo '<ul>';

			foreach ( $blogs as $blog ) {
				$blog_details = get_blog_details( $blog->blog_id );
				$blog_name    = $blog_details->blogname;

				echo '<li><a href="index.php?lti_staff_view_blog=true&blog_id=' . esc_attr( $blog->blog_id ) . '">' .
					esc_html( $blog_name ) . '</a></li>';
			}
		}

		echo '<br><br>';
		echo '</div>';

		get_template_part( 'footer' );

		exit;
	}

	/**
	 * Add staff members to student blogs
	 *
	 * @return void
	 */
	public function lti_add_staff_to_student_blog() {
        // phpcs:disable
		if ( isset( $_REQUEST['lti_staff_view_blog'] ) && 'true' === $_REQUEST['lti_staff_view_blog'] ) {
        // phpcs:enable
			if ( session_status() === PHP_SESSION_NONE ) {
				session_start();
			}

			if ( ! isset( $_SESSION['lti_staff'] ) ) {
				wp_die( 'You do not have permission to view this page' );
			}

            // phpcs:disable
			$blog_id    = $_REQUEST['blog_id'];
            // phpcs:enable

			$course_id  = $_SESSION['lti_staff_course_id'];
			$user_roles = $_SESSION['lti_user_roles'];

			// If someone has been messing about with the blog id and the blog has nothing to do with the current
			// course redirect them to the home page
			if ( ! Blog_Handler::is_course_blog( $course_id, $blog_id ) ) {
				$this->lti_redirect_user_to_blog_without_login( $blog_id );
			}

			$user = $this->first_or_create_user( $_SESSION['lti_staff_user_data'] );

			$blog_handler = Blog_Handler_Factory::instance( 'student' );
			$blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );

			$this->lti_signin_user( $user, $blog_id );
		}
	}

	/**
	 * Redirect a user to the defined home URL
	 *
	 * @param string $blog_id
	 *
	 * @return void
	 */
	private function lti_redirect_user_to_blog_without_login( $blog_id ) {
		switch_to_blog( $blog_id );
		wp_safe_redirect( home_url() );

		exit;
	}

	/**
	 * Generates a cryptographically secure random string of a given length which can be used for generating passwords
	 *
	 * Adapted from https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
	 *
	 * @param int $length
	 * @param string $alphabet
	 *
	 * @return string
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
	private function random_string( $length, $alphabet ) {
		if ( $length < 1 ) {
			throw new InvalidArgumentException( 'Length must be a positive integer' );
		}

		$str = '';

		$alphamax = strlen( $alphabet ) - 1;

		if ( $alphamax < 1 ) {
			throw new InvalidArgumentException( 'Invalid alphabet' );
		}

		for ( $i = 0; $i < $length; ++$i ) {
			$str .= $alphabet[ random_int( 0, $alphamax ) ];
		}

		return $str;
	}

	/**
	 * Is NS Cloner Installed.
	 *
	 * @return boolean
	 */
	public static function is_nscloner_installed() {
		return is_plugin_active( 'ns-cloner-site-copier/ns-cloner.php' );
	}

	/**
	 * Get slug with slashes so it is a valid WordPress path.
	 *
	 * @param string $slug
	 *
	 * @return void
	 */
	public static function turn_slug_into_path( $slug ) {
		return rtrim( '/' . $slug, '/' ) . '/';
	}
}
