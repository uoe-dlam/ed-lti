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

		wp_die( 'NS CLoner did not create site. Please contact the site administrator.' );
	}
}
