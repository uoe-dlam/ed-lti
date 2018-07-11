<?php
class Blog_Handler_Factory {
	public static function instance( $type ) {
		switch ( $type ) {
			case 'student':
				return new Student_Blog_Handler();
				break;

			default:
				return new Course_Blog_Handler();
		}
	}
}
