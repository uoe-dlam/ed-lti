<?php
class User_LTI_Roles {

	protected $roles = array();

	public function __construct( array $roles ) {
		$this->roles = $roles;
	}

	public function is_admin() {
		return $this->has_role( 'Administrator' ) || $this->has_role( 'urn:lti:sysrole:ims/lis/SysAdmin' ) ||
			$this->has_role( 'urn:lti:sysrole:ims/lis/Administrator' ) || $this->has_role( 'urn:lti:instrole:ims/lis/Administrator' );
	}

	public function is_staff() {
		return ( $this->has_role( 'Instructor' ) || $this->has_role( 'ContentDeveloper' ) || $this->has_role( 'TeachingAssistant' ) );
	}


	public function is_learner() {
		return $this->has_role( 'Learner' );
	}

	public function is_instructor() {
		return $this->has_role( 'Instructor' );
	}

	public function is_teaching_assistant() {
		return $this->has_role( 'TeachingAssistant' );
	}

	public function is_content_developer() {
		return $this->has_role( 'ContentDeveloper' );
	}

	private function has_role( $role ) {
		if ( substr( $role, 0, 4 ) !== 'urn:' ) {
			$role = 'urn:lti:role:ims/lis/' . $role;
		}

		return in_array( $role, $this->roles );
	}


}
