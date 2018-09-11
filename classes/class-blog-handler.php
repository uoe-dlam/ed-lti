<?php

/**
 * Abstract class used to handle different types of WordPress blogs
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
 */
abstract class Blog_Handler {

	protected $course_id;
	protected $course_title;
	protected $domain;
	protected $resource_link_id;
	protected $username;
	protected $user = null;
	protected $site_category;
	protected $source_id;
	protected $wpdb;

	/**
	 * TODO: Not sure what this does... what is the path used for?
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
	 * Get the WordPress role for a given LTI user role
	 *
	 * @return string
	 */
	abstract public function get_wordpress_role( User_LTI_Roles $roles );

	/**
	 * Check if the blog we are trying to create already exists
	 *
	 * @return bool
	 */
	abstract protected function blog_exists();

	/**
	 * Get the maximum version of a blog type
	 *
	 * @return int
	 */
	abstract public function get_blog_max_version();

	/**
	 * Get the total number of blogs of this type
	 *
	 * TODO: Check why we are doing this
	 *
	 * @return int
	 */
	abstract protected function get_blog_count();

	/**
	 * Get the blog ID
	 *
	 * @return string
	 */
	abstract protected function get_blog_id();

	/**
	 * TODO: Not sure what this is doing. Need to find its usage
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
	 * Create or return the existing blog
	 *
	 * @return int
	 */
	public function first_or_create_blog( $make_private = false ) {

		if (
			is_null( $this->course_id ) || is_null( $this->course_title ) || is_null( $this->domain ) ||
			is_null( $this->resource_link_id ) || is_null( $this->username ) || is_null( $this->source_id ) ||
			is_null( $this->site_category )
		) {
			wp_die( 'Blog_Handler: You must set all data before calling first_or_create_blog' );
		}

		if ( $this->blog_exists() ) {
			return $this->get_blog_id();
		}

		return $this->create_blog( $make_private );
	}

	/**
	 * Create a new blog
	 *
	 * @return int
	 */
	protected function create_blog( $make_private = false ) {

		$path  = $this->get_path();
		$title = $this->get_title();

		$version = $this->get_blog_max_version();
		$version++;

		if ( $version > 1 ) {
			// we already have a main blog, so create new blog and increment version number
			$path  .= '_v' . $version;
			$title .= ' ' . $version;
		}

		$blog_data = [
			'path'      => $path,
			'title'     => $title,
			'domain'    => $this->domain,
			'source_id' => $this->source_id,
		];

		$blog_id = $this->do_ns_cloner_create( $blog_data );
		$this->add_blog_meta( $blog_id, $version );
		$this->add_site_category( $blog_id );

		if ( $make_private ) {
			$this->make_blog_private( $blog_id );
		}

		return $blog_id;
	}

	/**
	 * Create a new blog using the NS Cloner plugin
	 *
	 * @return int
	 */
	protected function do_ns_cloner_create( array $data ) {
		$_POST['action']         = 'process';
		$_POST['clone_mode']     = 'core';
		$_POST['source_id']      = $data['source_id'];
		$_POST['target_name']    = $data['path'];
		$_POST['target_title']   = $data['title'];
		$_POST['disable_addons'] = true;
		$_POST['clone_nonce']    = wp_create_nonce( 'ns_cloner' );

		$ns_site_cloner = new ns_cloner();
		$ns_site_cloner->process();

		$site_id   = $ns_site_cloner->target_id;
		$site_info = get_blog_details( $site_id );

		if ( $site_info ) {
			return $site_id;
		}

		// TODO handle unsucessfull clone
		wp_die( 'NS CLoner did not create site' );
	}

	/**
	 * Add a newly created blog's details to the database
	 *
	 * @return void
	 */
	protected function add_blog_meta( $blog_id, $version = 1 ) {
		$this->wpdb->insert(
			$this->wpdb->base_prefix . 'blogs_meta', [
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
	 * Get friendly path
	 *
	 * @return string
	 */
	public function get_friendly_path( $path ) {
		$path = str_replace( ' ', '-', $path ); // Replaces all spaces with hyphens.
		$path = preg_replace( '/[^A-Za-z0-9\-\_]/', '', $path ); // Removes special chars.
		$path = strtolower( $path ); // Convert to lowercase

		return $path;
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
	 * Add a user to a blog
	 *
	 * @return void
	 */
	public function add_user_to_blog( $user, $blog_id, User_LTI_Roles $user_roles ) {
		if ( ! is_user_member_of_blog( $user->ID, $blog_id ) ) {
			$role = $this->get_wordpress_role( $user_roles );
			add_user_to_blog( $blog_id, $user->ID, $role );
		}
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
