<?php
/**
 * Blog creator factory.
 *
 * Factory that returns either a ns cloner or wp blog creator object.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
class Blog_Creator_Factory {
	public static function instance() {
		return Ed_LTI::is_nscloner_installed() ? new NS_Cloner_Blog_Creator() : new WP_Blog_Creator();
	}
}
