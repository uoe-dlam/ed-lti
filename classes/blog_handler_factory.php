<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 10:35
 */

class Blog_Handler_Factory {

    public static function instance( $type ) {
        switch ( $type ) {
            case 'student' :
                return new Student_Blog_Handler();
                break;

            default :
                return new Course_Blog_Handler();
        }
    }
}