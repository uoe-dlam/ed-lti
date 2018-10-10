<?php

/**
 * Blog creator factory.
 *
 * Factory that returns either a ns cloner or wp blog creator object.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright Edinburgh University
 */
class Blog_Creator_Factory {
	public static function instance() {

		if ( Ed_LTI::is_nscloner_installed() ) {
			return new NS_Cloner_Blog_Creator();
		}

		return new WP_Cloner_Blog_Creator();
	}
}
