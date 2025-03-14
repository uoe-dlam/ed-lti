<?php
namespace EdLTI\classes;
use ns_cloner;

/**
 * NS Cloner Blog Creator.
 *
 * Uses NS Cloner to create blog.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
class NS_Cloner_Blog_Creator implements Blog_Creator_Interface {

	/**
	 * Create blog using NS Cloner.
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function create( array $data ) {
		add_filter( 'ns_cloner_do_step_create_site', '__return_true' );

		$request = array(
			'clone_mode'   => 'core',
			'source_id'    => $data['source_id'], // any blog/site id on network
			'target_name'  => $data['path'],
			'target_title' => $data['title'],
			'debug'        => 1,
			'copy_theme'   => true
		);

		add_filter( 'ns_cloner_do_step_create_site', '__return_true' );

		// This is required to bootstrap the required plugin classes.
		\ns_cloner()->init();

		// This will create a background process via WP Cron to run the cloning operation.
		\ns_cloner()->schedule->add(
			$request,
			time(), // timestamp of date/time to start cloning - use time() to run immediately
			'ed-lti' // name of your project, required but used only for debugging
		);

		$site_id = get_blog_id_from_url($data['domain'], '/' . $data['path'] . '/');

		$site_info = get_blog_details( $site_id );

		if ( $site_info ) {
			return $site_id;
		}

		wp_die( 'NS CLoner did not create site. Please contact the site administrator.' );
	}
}
