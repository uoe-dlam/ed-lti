<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
 */

abstract class Blog_Handler {

    protected $data = array();

    protected $user = null;

    abstract protected function get_path();

    abstract public function get_blog_type();

    abstract public function get_wordpress_role( User_LTI_Roles $roles );

    public function init( array $data, $user = null ) {
        $this->data = $data;
        $this->user = $user;
    }

    public function first_or_create_blog() {

        if( empty( $this->data ) ) {
            wp_die( 'Blog_Handler: You must set data before calling first_or_create_blog' );
        }

        if( $this->blog_exists() ) {
            return $this->get_blog_id();
        }

        $path = $this->get_path();
        $title = $this->get_title();

        $version = $this->get_blog_max_version();
        $version++;

        if( $version > 1) {
            // we already have a main blog, so create new blog and increment version number
            $path .= '_v' .  $version;
            $title .= ' ' . $version;
        }

        $blog_data = array(
            'path' => $path,
            'title' => $title,
            'domain' => $this->data['domain'],
        );

        $blog_id = $this->create_blog( $blog_data, $version );

        return $blog_id;
    }

    protected function blog_exists() {
        global $wpdb;
        $blogs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}blogs_meta INNER JOIN {$wpdb->prefix}blogs ON {$wpdb->prefix}blogs.blog_id = {$wpdb->prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
                $this->data['course_id'],
                $this->data['resource_link_id'],
                $this->get_blog_type()
            )
        );

        return ( ! empty( $blogs ) );
    }

    public function get_blog_max_version() {
        global $wpdb;

        $blog_max_version = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT IFNULL(MAX(version), 0) AS max_version FROM {$wpdb->prefix}blogs_meta WHERE course_id = %s AND blog_type = %s",
                $this->data['course_id'],
                $this->get_blog_type()

            )
        );

        return (int) $blog_max_version[0]->max_version;
    }

    protected function create_blog( $blog_data, $version = 1 ) {
        $blog_id = $this->do_ns_cloner_create( $blog_data );
        $this->add_blog_meta( $blog_id, $version );
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

        //TODO handle unsucessfull clone
        wp_die('NS CLoner did not create site');
    }

    protected function add_blog_meta( $blog_id, $version = 1 ) {
        global $wpdb;

        $firstname = '';
        $lastname = '';

        if( $this->user ) {
            $firstname = $this->user->first_name;
            $lastname = $this->user->last_name;
        }

        $wpdb->insert($wpdb->prefix . 'blogs_meta', array(
            'blog_id' => $blog_id,
            'version' => $version,
            'course_id' => $this->data['course_id'],
            'resource_link_id' => $this->data['resource_link_id'],
            'blog_type' => $this->get_blog_type(),
            'student_firstname' => $firstname,
            'student_lastname' => $lastname,
        ));
    }

    protected function get_blog_id() {
        global $wpdb;
        $blogs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$wpdb->prefix}blogs_meta.blog_id AS blog_id FROM {$wpdb->prefix}blogs_meta INNER JOIN {$wpdb->prefix}blogs ON {$wpdb->prefix}blogs.blog_id = {$wpdb->prefix}blogs_meta.blog_id WHERE course_id = %s AND resource_link_id = %s AND blog_type = %s",
                $this->data['course_id'],
                $this->data['resource_link_id'],
                $this->get_blog_type()
            )
        );

        if ( ! $blogs ) {
            return null;
        }

        return $blogs[0]->blog_id;
    }

    protected function get_blog_count() {
        global $wpdb;

        $blog_count = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COUNT(id) AS blog_count FROM {$wpdb->prefix}blogs_meta WHERE course_id = %s AND blog_type = %s",
                $this->data['course_id'],
                $this->get_blog_type()
            )
        );

        return (int) $blog_count[0]->blog_count;
    }

    public static function is_course_blog( $course_id, $blog_id ) {
        global $wpdb;

        $blog_count = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COUNT(id) AS blog_count FROM {$wpdb->prefix}blogs_meta WHERE course_id = %s AND blog_id = %s",
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
        return $this->data['course_title'];
    }

    public function add_user_to_blog( $user, $blog_id, User_LTI_Roles $user_roles ) {
        if( ! is_user_member_of_blog( $user->ID, $blog_id ) ) {
            $role = $this->get_wordpress_role( $user_roles );
            add_user_to_blog( $blog_id, $user->ID, $role );
        }
    }
}