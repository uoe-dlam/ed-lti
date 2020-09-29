<?php
namespace EdLTI\classes;

/**
 * Abstract class used to handle different types of WordPress blogs.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
abstract class Blog_Handler {

	protected $blog_id;
	protected $course_id;
	protected $course_title;
	protected $domain;
	protected $resource_link_id;
	protected $username;
	protected $user;
	protected $site_category;
	protected $source_id;
	protected $wpdb;
	const MAX_PATH_CHARS = 95;

	/**
	 * Returns the subdirectory name for the blog: path/slug.
	 *
	 * @return string
	 */
	abstract protected function get_path();

	/**
	 * Return the type of blog this handler creates
	 *
	 * @return string
	 */
	abstract public function get_blog_type();

	/**
	 * Get the WordPress role for a given LTI user role.
	 *
	 * @param User_LTI_Roles $roles
	 *
	 * @return string
	 */
	abstract public function get_wordpress_role( User_LTI_Roles $roles );

	/**
	 * Get the maximum version of a blog type
	 *
	 * @return int
	 */
	abstract public function get_blog_max_version();

	/**
	 * Get blog ID if blog exists.
	 *
	 * @return string
	 */
	abstract protected function get_blog_id_if_exists();

	/**
	 * Gets the blog options to set when the blog is created or loaded
	 *
	 * @param array   $request_data
	 *
	 * @return array
	 */
	abstract public function get_options_from_request( array $request_data ): array;

	/**
	 * Allow blog id to be set from path,
	 * as there is an issue should settings change.
	 *
	 * @return void
	 */
	abstract protected function fix_blog_id_from_path(): void;

	/**
	 * Set class properties using array.
	 *
	 * @param array   $data
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function init( array $data, $user = null ) {

		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}

		$this->user = $user;
	}

	/**
	 * Create or return the existing blog.
	 *
	 * @param bool $make_private
	 *
	 * @return int
	 */
	public function first_or_create_blog( $make_private = false ) {

		$this->validate_blog_data();

		$blog_id = $this->get_blog_id_if_exists();

		if ( null === $blog_id ) {
			$this->fix_blog_id_from_path();
			$blog_id = $this->get_blog_id_if_exists();
		}

		if ( null === $blog_id ) {
			$blog_id = $this->create_blog( $make_private );
		}

		$this->blog_id = $blog_id;

		return $blog_id;
	}

	/**
	 * Manage additional blog options when a blog is created or viewed
	 *
	 * @param array $options_to_set
	 *
	 * @return void
	 */
	public function set_additional_blog_options( array $options_to_set ) {
		foreach ( $options_to_set as $blog_option_key => $blog_option_value ) {
			update_blog_option( $this->blog_id, $blog_option_key, $blog_option_value );
		}
	}

	protected function validate_blog_data() {
		if (
			null === $this->course_id || null === $this->course_title || null === $this->domain ||
			null === $this->resource_link_id || null === $this->username || null === $this->source_id ||
			null === $this->site_category
		) {
			wp_die( 'Blog_Handler: You must set all data before calling first_or_create_blog' );
		}
	}

	/**
	 * Create a new blog.
	 *
	 * @param bool $make_private
	 *
	 * @return int
	 */
	protected function create_blog( $make_private = false ) {
		$path  = $this->get_path();
		$title = $this->get_title();

		$version = $this->get_blog_max_version();
		$version++;

		if ( $version > 1 ) {
			// we already have a main blog, so create new blog and increment version number.
			$path  .= '_v' . $version;
			$title .= ' ' . $version;
		}

		$blog_data = [
			'path'      => $path,
			'title'     => $title,
			'domain'    => $this->domain,
			'source_id' => $this->source_id,
		];

		// if NS Cloner is installed we will use the NS Cloner blog creator, else we will us the wp creator.
		$blog_creator = Blog_Creator_Factory::instance();
		$blog_id      = $blog_creator->create( $blog_data );

		$this->add_blog_meta( $blog_id, $version );
		$this->add_site_category( $blog_id );

		if ( $make_private ) {
			$this->make_blog_private( $blog_id );
		}

		return $blog_id;
	}

	/**
	 * Add a newly created blog's details to the database.
	 *
	 * @param int $blog_id
	 * @param int $version
	 *
	 * @return void
	 */
	protected function add_blog_meta( $blog_id, $version = 1 ) {
		$this->wpdb->insert(
			$this->wpdb->base_prefix . 'blogs_meta',
			[
				'blog_id'           => $blog_id,
				'version'           => $version,
				'course_id'         => $this->course_id,
				'resource_link_id'  => $this->resource_link_id,
				'blog_type'         => $this->get_blog_type(),
				'creator_firstname' => $this->user->first_name,
				'creator_lastname'  => $this->user->last_name,
				'creator_id'        => $this->user->ID,
			]
		);
	}

	/**
	 * Add a site category to a given blog
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	protected function add_site_category( $blog_id ) {
		switch_to_blog( $blog_id );
		update_option( 'site_category', $this->site_category );
		restore_current_blog();
	}

	/**
	 * Make a blog private
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	protected function make_blog_private( $blog_id ) {
		switch_to_blog( $blog_id );
		update_option( 'blog_public', '-2' );
		restore_current_blog();
		update_blog_details( $blog_id, [ 'public' => '-2' ] );
	}

	/**
	 * Check if a given blog is associated with the given course ID
	 *
	 * @param int $course_id
	 * @param int $blog_id
	 *
	 * @return bool
	 */
	public static function is_course_blog( $course_id, $blog_id ) {
		global $wpdb;
		$query = 'SELECT COUNT(id) AS blog_count '
			. 'FROM ' . $wpdb->base_prefix . 'blogs_meta '
			. 'WHERE course_id = %s '
			. 'AND blog_id = %d';

        // phpcs:disable
		$prepared_statement = $wpdb->prepare(
			$query,
			$course_id,
			$blog_id
		);

		$blog_count = $wpdb->get_results( $prepared_statement );
        // phpcs:enable

		return ( (int) $blog_count[0]->blog_count > 0 );
	}

	/**
	 * Get friendly path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function get_friendly_path( $path ) {
		$path = str_replace( ' ', '-', $path ); // Replaces all spaces with hyphens.
		$path = preg_replace( '/[^A-Za-z0-9\-_]/', '', $path ); // Removes special chars.
		$path = strtolower( $path ); // Convert to lowercase.

		if ( $this->is_subdirectory_install() ) {
			$path = $this->append_subdirectory_install_base_path( $path );
		}

		// Make sure path isn't too big for DB Column.
        $path = substr($path, 0, self::MAX_PATH_CHARS);

		return $path;
	}

	/**
	 * Let's us know if the current site has been installed in a subdirectory; e.g. http:://mysite.co.uk/wp rather than http:://mysite.co.uk.
	 *
	 * @return boolean
	 */
	public function is_subdirectory_install() {
		return ( get_current_site()->path !== '/' );
	}

	/**
	 * Append the subdirectory base install path. If your site is installed on http:://mysite.co.uk/wp for example, wp/ will be prepended to the path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function append_subdirectory_install_base_path( $path ) {
		$site_base = ltrim( get_current_site()->path, '/' );
		return $site_base . $path;
	}

	/**
	 * Return the course title
	 *
	 * @string
	 */
	protected function get_title() {
		return $this->course_title;
	}

	/**
	 * Add a user to a blog.
	 *
	 * @param WP_User        $user
	 * @param int            $blog_id
	 * @param User_LTI_Roles $user_roles
	 *
	 * @return void
	 */
	public function add_user_to_blog( $user, $blog_id, User_LTI_Roles $user_roles ) {
		if ( ! is_user_member_of_blog( $user->ID, $blog_id ) ) {
			$role = $this->get_wordpress_role( $user_roles );

			// Set blog admin_email to user email if they are the first administrator.
			if ( 'administrator' === $role && ! $this->has_blog_admin( $blog_id ) ) {
				update_blog_option( $blog_id, 'admin_email', $user->user_email );
				$this->send_admin_notification_email( $user, $blog_id );
			}

			add_user_to_blog( $blog_id, $user->ID, $role );
		}
	}

	/**
	 * Does blog have one or more admin users.
	 *
	 * @param int $blog_id
	 *
	 * @return boolean
	 */
	public function has_blog_admin( $blog_id ) {
		return ! empty(
			get_users(
				[
					'blog_id' => $blog_id,
					'role'    => 'administrator',
				]
			)
		);
	}


	/**
	 * Send admin user notification email.
	 *
	 * @param WP_User $user
	 * @param int     $blog_id
	 *
	 * @return void
	 */
	private function send_admin_notification_email( $user, $blog_id ) {
		$blog_details = get_blog_details( $blog_id );
		$protocol     = stripos( $_SERVER['SERVER_PROTOCOL'], 'https' ) === true ? 'https://' : 'http://';

		$to      = $user->user_email;
		$subject = 'You have been made the admin for the blog: ' . $blog_details->blogname;
		$message = "Hi {$user->first_name},<br><br>
                            You have been made the main admin for the '{$blog_details->blogname}' blog.<br><br>
                            This means that you will get notification emails when the blog is updated. You will also get notification emails if comments are held for moderation, etc. For a full list of the notifications that you will get, please visit <a href='https://en.support.wordpress.com/email-notifications/'>wordpress.com</a>.<br><br>
                            If you would like to change the notifications email address for this blog, please update the email address field in the <a href='$protocol{$blog_details->domain}{$blog_details->path}wp-admin/options-general.php'>settings page</a><br><br>
                            The Academic Blogging Service Team";
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Add a user to the top level blog so we don't get login issues if a user tries to login to the top level site
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function add_user_to_top_level_blog( $user ) {
		$top_level_blog_id = get_main_site_id();

		if ( ! is_user_member_of_blog( $user->ID, $top_level_blog_id ) ) {
			add_user_to_blog( $top_level_blog_id, $user->ID, 'subscriber' );
		}
	}
}
