<?php

/*
Plugin Name: UoE LTI
Description: Allows LMSs to create blogs in a WordPress multisite installation via an LTI connection
Author: DLAM Applications Development Team
Version: 1.4.0
Credits: This plugin was inspired by the IMS Basic Learning Tools Interoperability plugin ( developed by Chuck Severance & Antoni Bertran ). Some of the code used in this plugin is borrowed from that plugin ( see https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP )
Copyright: University of Edinburgh
License: GPL-3.0+
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace EdLTI;

// Include the autoloader so we can dynamically include the rest of the classes.
require_once trailingslashit( dirname( __FILE__ ) ) . 'inc/autoloader.php';

use EdLTI\classes\Ed_LTI;

new Ed_LTI();

register_activation_hook( __FILE__, [ 'EdLTI\classes\Ed_LTI', 'activate' ] );
