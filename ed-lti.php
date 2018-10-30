<?php

/*
Plugin Name: UoE LTI
Description: Allows LMSs to connect to our website and create blogs.
Author: DLAM Applications Development Team
Version: 1.0
Credits: This plugin was inspired by the IMS Basic Learning Tools Interoperability plugin ( developed by Chuck Severance & Antoni Bertran ). Some of the code used in this plugin is borrowed from that plugin ( see https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP )
Copyright: 2018 University of Edinburgh
License: MIT ( see LICENSE )
*/

require_once 'classes/class-ed-lti.php';

new Ed_LTI();

register_activation_hook( __FILE__, [ 'Ed_LTI', 'activate' ] );
