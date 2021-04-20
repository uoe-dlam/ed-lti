<?php
namespace EdLTI\classes;

use Exception;
/**
 * Ed Tool Wrapper is a wrapper for the LTI tool provider package created by Stephen P. Vickers
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */

use ceLTIc\LTI\Tool;

class Ed_Tool_Provider extends Tool {

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
