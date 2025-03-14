<?php
namespace EdLTI\classes;

/**
 * WP Blog Creator.
 *
 * Uses wp core methods to create blog.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
class WP_Blog_Creator implements Blog_Creator_Interface {

	/**
	 * Create blog using WP core methods.
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function create( array $data ) {
		// We need to make sure our path is not just a slug and contains appropriate slashes.
		$path = Ed_LTI::turn_slug_into_path( $data['path'] );

		// Unlike NS Cloner, WordPress requires a user to create a blog
		$default_user_id = wp_get_current_user()->ID;
		$site_id         = wpmu_create_blog( $data['domain'], $path, $data['title'], $default_user_id );
		$site_info       = get_blog_details( $site_id );

		if ( $site_info ) {
			$this->set_blog_template( $site_id );
			$this->set_plugins( $site_id );
			$this->remove_default_posts( $site_id );
			$this->set_posts( $site_id );
			// we don't want the default user to be the site owner, so remove them from the blog we just created
			remove_user_from_blog( $default_user_id, $site_id );

			return $site_id;
		}

		wp_die( 'WP did not create site. Please contact the site administrator.' );
	}

	/**
	 * Set blog template for newly created blog to use template sites theme.
	 *
	 * @param int $site_id
	 *
	 * @return void
	 */
	protected function set_blog_template( $site_id ) {
		$template = get_blog_option( get_site_option( 'default_site_template_id' ), 'template' );

		update_blog_option( $site_id, 'template', $template );

		$stylesheet = get_blog_option( get_site_option( 'default_site_template_id' ), 'stylesheet' );

		update_blog_option( $site_id, 'stylesheet', $stylesheet );
	}

	protected function set_plugins( $site_id ) {
		switch_to_blog( get_site_option( 'default_site_template_id' ) );

		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins' );

		restore_current_blog();

		switch_to_blog( $site_id );

		foreach ($plugins as $plugin_file => $plugin_info) {
			// Activate the plugin
			if ( in_array( $plugin_file, $active_plugins ) ) {
				activate_plugin( $plugin_file );
			}
		}

		restore_current_blog();
	}

	protected function remove_default_posts( $site_id ) {
		switch_to_blog( $site_id );

		$source_args = array(
			'post_type'      => 'post',        // Fetch posts
			'posts_per_page' => -1,            // Retrieve all posts
		);

		$query = new \WP_Query( $source_args );

		while ( $query->have_posts() ) {
			$query->the_post();
			wp_delete_post( get_the_ID(), true, );
		}

		restore_current_blog();
	}

	protected function set_posts( $site_id ) {
		// Source site query
		switch_to_blog( get_site_option( 'default_site_template_id' ) );

		$source_args = array(
			'post_type'      => 'post',        // Fetch posts
			'posts_per_page' => -1,            // Retrieve all posts
		);

		$source_query = new \WP_Query( $source_args );

		while ( $source_query->have_posts() ) {
			$source_query->the_post();

			// Create the post on the target site
			$post_data = array(
				'post_title'   => get_the_title(),
				'post_content' => get_the_content(),
				'post_status'  => get_post_status()
			);

			$post_thumbnail_id = null;

			if ( has_post_thumbnail() ) {
				$post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
				$image_url = wp_get_attachment_image_src( $post_thumbnail_id, 'full' )[0];
				$filename = basename( $image_url );
			}

			// Target site
			switch_to_blog( $site_id );

			$inserted_post_id = wp_insert_post( $post_data );

			if ( $post_thumbnail_id ) {
				$this->set_featured_image( $inserted_post_id, $image_url, $filename );
			}

			switch_to_blog( get_site_option( 'default_site_template_id' ) );
		}

		// Restore the original blog context
		restore_current_blog();
	}

	protected function set_featured_image( int $inserted_post_id, string $image_url, string $filename ) {
		$upload_dir = wp_upload_dir();
		$file       = $upload_dir['path'] . '/' . $filename;
		$result = file_put_contents( $file, file_get_contents( $image_url ) );

		if ($result !== false) {
			// Upload the image to the Media Library
			$attachment = array(
				'post_mime_type' => 'image/jpeg', // Adjust the MIME type as needed
				'post_title' => $filename,
				'post_content' => '',
				'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $file );

			if ( ! is_wp_error( $attach_id ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				// Set the image as the featured image for a post
				set_post_thumbnail( $inserted_post_id, $attach_id );
			}
		}
	}
}
