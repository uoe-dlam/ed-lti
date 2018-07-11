<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
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

	abstract protected function get_path();

	abstract public function get_blog_type();

	abstract public function get_wordpress_role( User_LTI_Roles $roles );

	abstract protected function blog_exists();

	abstract public function get_blog_max_version();

	abstract protected function get_blog_count();

	abstract protected function get_blog_id();

	public function init( array $data, $user = null ) {

		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}

		$this->user = $user;
	}

	public function first_or_create_blog() {

		if ( is_null( $this->course_id ) || is_null( $this->course_title ) || is_null( $this->domain ) || is_null( $this->resource_link_id ) || is_null( $this->username ) || is_null( $this->source_id ) || is_null( $this->site_category ) ) {
			wp_die( 'Blog_Handler: You must set all data before calling first_or_create_blog' );
		}

		if ( $this->blog_exists() ) {
			return $this->get_blog_id();
		}

		return $this->create_blog();

	}

	protected function create_blog() {
		$path  = $this->get_path();
		$title = $this->get_title();

		$version = $this->get_blog_max_version();
		$version++;

		if ( $version > 1 ) {
			// we already have a main blog, so create new blog and increment version number
			$path  .= '_v' . $version;
			$title .= ' ' . $version;
		}

		$blog_data = array(
			'path'      => $path,
			'title'     => $title,
			'domain'    => $this->domain,
			'source_id' => $this->source_id,
		);

		$blog_id = $this->do_ns_cloner_create( $blog_data );
		$this->add_blog_meta( $blog_id, $version );
		$this->add_site_category( $blog_id );
		$this->make_blog_private( $blog_id );

		return $blog_id;
	}

	protected function do_ns_cloner_create( array $data ) {

		/**
		 * Before doing anything: setup clone $_POST data.
		 * [] {
		 *     @type  string  'action'         => 'process',
		 *     @type  string  'clone_mode'     => 'core',
		 *     @type  int     'source_id'      => $blog_id,
		 *     @type  string  'target_name'    => $target_name,
		 *     @type  string  'target_title'   => $target_title,
		 *     @type  bool    'disable_addons' => true,
		 *     @type  string  'clone_nonce'    => wp_create_nonce('ns_cloner')
		 * }
		 */

		$_POST['action']         = 'process';
		$_POST['clone_mode']     = 'core';
		$_POST['source_id']      = $data['source_id'];
		$_POST['target_name']    = $data['path'];
		$_POST['target_title']   = $data['title'];
		$_POST['disable_addons'] = true;
		$_POST['clone_nonce']    = wp_create_nonce( 'ns_cloner' );

		// Setup clone process and run it.
		$ns_site_cloner = new ns_cloner();
		$ns_site_cloner->process();

		$site_id   = $ns_site_cloner->target_id;
		$site_info = get_blog_details( $site_id );
		if ( $site_info ) {
			return $site_id;
			// Clone successful!
		}

		//TODO handle unsucessfull clone
		wp_die( 'NS CLoner did not create site' );
	}

	protected function add_blog_meta( $blog_id, $version = 1 ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->base_prefix . 'blogs_meta', array(
				'blog_id'           => $blog_id,
				'version'           => $version,
				'course_id'         => $this->course_id,
				'resource_link_id'  => $this->resource_link_id,
				'blog_type'         => $this->get_blog_type(),
				'creator_firstname' => $this->user->first_name,
				'creator_lastname'  => $this->user->last_name,
				'creator_id'        => $this->user->ID,
			)
		);
	}

	protected function add_site_category( $blog_id ) {
		switch_to_blog( $blog_id );
		update_option( 'site_category', $this->site_category );
		restore_current_blog();
	}

	protected function make_blog_private( $blog_id ) {
		switch_to_blog( $blog_id );
		update_option( 'blog_public', '-2' );
		restore_current_blog();
		update_blog_details(
			$blog_id, array(
				'public' => '-2',
			)
		);
	}

	public static function is_course_blog( $course_id, $blog_id ) {
		global $wpdb;

		$blog_count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(id) AS blog_count FROM {$wpdb->base_prefix}blogs_meta WHERE course_id = %s AND blog_id = %d",
				$course_id,
				$blog_id
			)
		);

		$blog_count = (int) $blog_count[0]->blog_count;

		return ( $blog_count > 0 );
	}

	public function get_friendly_path( $string ) {
		$string = str_replace( ' ', '-', $string ); // Replaces all spaces with hyphens.
		$string = preg_replace( '/[^A-Za-z0-9\-\_]/', '', $string ); // Removes special chars.
		$string = strtolower( $string ); // Convert to lowercase
		return $string;
	}

	protected function get_title() {
		return $this->course_title;
	}

	public function add_user_to_blog( $user, $blog_id, User_LTI_Roles $user_roles ) {
		if ( ! is_user_member_of_blog( $user->ID, $blog_id ) ) {
			$role = $this->get_wordpress_role( $user_roles );
			add_user_to_blog( $blog_id, $user->ID, $role );
		}
	}
}
