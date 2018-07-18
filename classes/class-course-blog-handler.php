<?php

/**
 * Course Blog Handler
 *
 * Handles the creation of a blog for a course.
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
 */
class Course_Blog_Handler extends Blog_Handler {

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Return the type of blog this handler creates
	 *
	 * @return string
	 */
	public function get_blog_type() {
		return 'course';
	}

	/**
	 * TODO: Not sure what this does... what is the path used for?
	 *
	 * @return string
	 */
	protected function get_path() {
		return $this->get_friendly_path( $this->course_id );
	}

	/**
	 * Check if the blog we are trying to create already exists
	 *
	 * @return bool
	 */
	protected function blog_exists() {
		$query = "SELECT * FROM {$this->wpdb->base_prefix}blogs_meta "
            . "INNER JOIN {$this->wpdb->base_prefix}blogs "
            . "ON {$this->wpdb->base_prefix}blogs.blog_id = {$this->wpdb->base_prefix}blogs_meta.blog_id "
            . 'WHERE course_id = %s '
            . 'AND resource_link_id = %s '
            . 'AND blog_type = %s';

		$blogs = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type()
			)
		);

		return ( ! empty( $blogs ) );
	}

	/**
	 * Get the maximum version of a blog type
	 *
	 * @return int
	 */
	public function get_blog_max_version() {
		$query = 'SELECT IFNULL(MAX(version), 0) AS max_version '
			   . "FROM {$this->wpdb->base_prefix}blogs_meta "
			   . 'WHERE course_id = %s AND blog_type = %s';

		$blog_max_version = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$this->course_id,
				$this->get_blog_type()
			)
		);

		return (int) $blog_max_version[0]->max_version;
	}

	/**
	 * Get the total number of blogs of this type
	 *
	 * TODO: Check why we are doing this
	 *
	 * @return int
	 */
	protected function get_blog_count() {
		$query = 'SELECT COUNT(id) AS blog_count '
			. "FROM {$this->wpdb->base_prefix}blogs_meta "
			. 'WHERE course_id = %s '
			. 'AND blog_type = %s';

		$blog_count = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$this->course_id,
				$this->get_blog_type()
			)
		);

		return (int) $blog_count[0]->blog_count;
	}

	/**
	 * Get the blog ID
	 *
	 * @return string
	 */
	protected function get_blog_id() {
		$query = "SELECT {$this->wpdb->base_prefix}blogs_meta.blog_id AS blog_id "
			   . "FROM {$this->wpdb->base_prefix}blogs_meta "
			   . "INNER JOIN {$this->wpdb->base_prefix}blogs "
			   . "ON {$this->wpdb->base_prefix}blogs.blog_id = {$this->wpdb->base_prefix}blogs_meta.blog_id "
			   . 'WHERE course_id = %s '
			   . 'AND resource_link_id = %s '
			   . 'AND blog_type = %s';

		$blogs = $this->wpdb->get_results(
			$this->wpdb->prepare(

				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type()
			)
		);

		if ( ! $blogs ) {
			return null;
		}

		return $blogs[0]->blog_id;
	}

	/**
	 * Get the WordPress role for a given LTI user role
	 *
	 * @return string
	 */
	public function get_wordpress_role( User_LTI_Roles $user_roles ) {
		// student or teaching assistants will be set to the default author role
		$wordpress_user_role = 'author';

		if ( $user_roles->is_instructor() || $user_roles->is_content_developer() || $user_roles->is_admin() ) {
			$wordpress_user_role = 'administrator';
		}

		return $wordpress_user_role;
	}
}
