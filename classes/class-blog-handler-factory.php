<?php

namespace EDLTI;

/**
 * Blog handler factory
 *
 * Factory that returns either a student blog handler or course blog handler object.
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
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
