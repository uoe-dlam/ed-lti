<?php
/**
 * Created by PhpStorm.
 * User: rlawson3
 * Date: 05/06/2018
 * Time: 09:29
 */

class User_LTI_Roles {

    protected $roles = array();

    public function __construct( array $roles ) {
        $this->roles = $roles;
    }

    public function isAdmin() {
        return $this->hasRole( 'Administrator' ) || $this->hasRole( 'urn:lti:sysrole:ims/lis/SysAdmin' ) ||
            $this->hasRole( 'urn:lti:sysrole:ims/lis/Administrator' ) || $this->hasRole( 'urn:lti:instrole:ims/lis/Administrator' );
    }

    public function isStaff() {
        return ( $this->hasRole( 'Instructor' ) || $this->hasRole( 'ContentDeveloper' ) || $this->hasRole( 'TeachingAssistant' ) );
    }


    public function isLearner() {
        return $this->hasRole('Learner');
    }

    public function isInstructor() {
        return $this->hasRole('Instructor');
    }

    public function isTeachingAssistant() {
        return $this->hasRole('TeachingAssistant');
    }

    public function isContentDeveloper() {
        return $this->hasRole('ContentDeveloper');
    }

    private function hasRole($role) {

        if ( substr( $role, 0, 4 ) !== 'urn:' ) {
            $role = 'urn:lti:role:ims/lis/' . $role;
        }

        return in_array( $role, $this->roles );
    }


}