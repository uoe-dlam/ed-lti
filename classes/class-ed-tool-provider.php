<?php

namespace EdLTI\classes;

use IMSGlobal\LTI\ToolProvider;
use Exception;

/**
 * Ed Tool Wrapper is a wrapper for the LTI tool provider package created by Stephen P. Vickers
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
 */
class Ed_Tool_Provider extends ToolProvider\ToolProvider {

	/**
	 * On launch set the lti_okay session to true
	 *
	 * @return void
	 */
	public function onLaunch() {
		$_SESSION['lti_okay'] = true;
	}

	/**
	 * Ensure errors in the LTI package are rendered correctly in WordPress
	 *
	 * @return void
	 * @throws Exception
	 */
	public function onError() {
		wp_die( esc_html( $this->reason ) );

		throw new Exception( $this->message );
	}
}
