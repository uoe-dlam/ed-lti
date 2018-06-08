<?php
use IMSGlobal\LTI\ToolProvider;

class EdToolProvider extends ToolProvider\ToolProvider {

    function onLaunch() {
        $_SESSION['lti_okay'] = true;
    }


    function onError() {
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

