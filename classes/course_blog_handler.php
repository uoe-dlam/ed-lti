<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
 */

class Course_Blog_Handler extends Blog_Handler {

    public function get_blog_type() {
        return 'course';
    }

    protected function get_path() {
        return $this->get_friendly_path( $this->data['course_id'] );
    }

    public function get_wordpress_role( User_LTI_Roles $user_roles ) {
        if( $user_roles->isLearner() ) {
            return 'author';
        }

        // else must be admin or staff
        return 'administrator';
    }

}