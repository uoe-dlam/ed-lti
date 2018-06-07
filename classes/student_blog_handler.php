<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
 */

class Student_Blog_Handler extends Blog_Handler {

    public function get_blog_type() {
        return 'student';
    }

    protected function get_path() {
        return $this->get_friendly_path( $this->data['username'] . '_' . $this->data['course_title'] );
    }

    protected function get_title() {
        return $this->user->first_name . ' ' . $this->user->last_name . ' / ' . $this->data['course_title'];
    }

    public function get_wordpress_role( User_LTI_Roles $user_roles ) {
        if( $user_roles->isLearner() || $user_roles->isAdmin() ) {
            return 'administrator';
        }

        return 'author';
    }

}