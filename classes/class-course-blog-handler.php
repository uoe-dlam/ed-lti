<?php
class Course_Blog_Handler extends Blog_Handler {
	public function get_blog_type() {
		return 'course';
	}

	protected function get_path() {
		return $this->get_friendly_path( $this->course_id );
	}

	protected function blog_exists() {
		global $wpdb;
		$blogs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}blogs_meta INNER JOIN {$wpdb->base_prefix}blogs ON {$wpdb->base_prefix}blogs.blog_id = {$wpdb->base_prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type()
			)
		);

		return ( ! empty( $blogs ) );
	}

	public function get_blog_max_version() {
		global $wpdb;

		$blog_max_version = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT IFNULL(MAX(version), 0) AS max_version FROM {$wpdb->base_prefix}blogs_meta WHERE course_id = %s AND blog_type = %s",
				$this->course_id,
				$this->get_blog_type()
			)
		);

		return (int) $blog_max_version[0]->max_version;
	}

	protected function get_blog_count() {
		global $wpdb;

		$blog_count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(id) AS blog_count FROM {$wpdb->base_prefix}blogs_meta WHERE course_id = %s AND blog_type = %s",
				$this->course_id,
				$this->get_blog_type()
			)
		);

		return (int) $blog_count[0]->blog_count;
	}

	protected function get_blog_id() {
		global $wpdb;
		$blogs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->base_prefix}blogs_meta.blog_id AS blog_id FROM {$wpdb->base_prefix}blogs_meta INNER JOIN {$wpdb->base_prefix}blogs ON {$wpdb->base_prefix}blogs.blog_id = {$wpdb->base_prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
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

	public function get_wordpress_role( User_LTI_Roles $user_roles ) {
		if ( $user_roles->is_instructor() || $user_roles->is_content_developer() || $user_roles->is_admin() ) {
			return 'administrator';
		}

		// else must be student or teaching assistant
		return 'author';
	}

}
