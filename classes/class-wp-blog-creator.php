<?php
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
class WP_Blog_Creator implements Blog_Creator {

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
}
