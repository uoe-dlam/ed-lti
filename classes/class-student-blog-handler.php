<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
 */

class Student_Blog_Handler extends Blog_Handler {

	public function get_blog_type() {
		return 'student';
	}

	protected function get_path() {
		return $this->get_friendly_path( $this->username . '_' . $this->course_title );
	}

	protected function get_title() {
		return $this->user->first_name . ' ' . $this->user->last_name . ' / ' . $this->course_title;
	}

	protected function blog_exists() {
		global $wpdb;
		$blogs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}blogs_meta INNER JOIN {$wpdb->base_prefix}blogs ON {$wpdb->base_prefix}blogs.blog_id = {$wpdb->base_prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s AND creator_id = %d",
				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);

		return ( ! empty( $blogs ) );
	}

	public function get_blog_max_version() {
		global $wpdb;

		$blog_max_version = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT IFNULL(MAX(version), 0) AS max_version FROM {$wpdb->base_prefix}blogs_meta WHERE course_id = %s AND blog_type = %s AND creator_id = %d",
				$this->course_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);

		return (int) $blog_max_version[0]->max_version;
	}

	protected function get_blog_count() {
		global $wpdb;

		$blog_count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(id) AS blog_count FROM {$wpdb->base_prefix}blogs_meta WHERE course_id = %s AND blog_type = %s AND creator_id = %d",
				$this->course_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);

		return (int) $blog_count[0]->blog_count;
	}

	protected function get_blog_id() {
		global $wpdb;
		$blogs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->base_prefix}blogs_meta.blog_id AS blog_id FROM {$wpdb->base_prefix}blogs_meta INNER JOIN {$wpdb->base_prefix}blogs ON {$wpdb->base_prefix}blogs.blog_id = {$wpdb->base_prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s AND creator_id = %d",
				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);

		if ( ! $blogs ) {
			return null;
		}

		return $blogs[0]->blog_id;
	}

	public function get_wordpress_role( User_LTI_Roles $user_roles ) {
		if ( $user_roles->isLearner() || $user_roles->isAdmin() ) {
			return 'administrator';
		}

		return 'author';
	}

}
