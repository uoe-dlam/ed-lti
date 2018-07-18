<?php

/*
Plugin Name: Ed-LTI
Description: Allows LMSs to connect to site and create blogs
Author: Richard Lawson (richard.lawson@ed.ac.uk)
Version: 1.0.0
*/

require_once 'classes/class-ed-lti.php';

new Ed_LTI();

register_activation_hook( __FILE__, [ 'Ed_LTI', 'activate' ] );
