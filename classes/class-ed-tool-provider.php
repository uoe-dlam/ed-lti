<?php

use IMSGlobal\LTI\ToolProvider;

class Ed_Tool_Provider extends ToolProvider\ToolProvider {

	public function onLaunch() {
		$_SESSION['lti_okay'] = true;
	}


	public function onError() {
		wp_die( $this->reason );
		// TODO handle this exception
		$msg = $this->message;

		/*
		 *  TODO log reason on error
		 *
		 *  if( isset( $this->reason ) ) {
		 *      // log $this->reason
		 *  }
		 */

		throw new Exception( $msg );

	}

}

