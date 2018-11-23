<?php
namespace EdLTI\classes;

/**
 * Blog handler factory
 *
 * Factory that returns either a student blog handler or course blog handler object.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
class Blog_Handler_Factory {
	public static function instance( $type ) {
		switch ( $type ) {
			case 'student':
				return new Student_Blog_Handler();
			default:
				return new Course_Blog_Handler();
		}
	}
}
