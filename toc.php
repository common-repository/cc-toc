<?php

/*
	Plugin Name: CC-TOC
	Plugin URI: https://wordpress.org/plugins/cc-toc
	Description: This plugin automatically creates a table of contents based on html headings in content.
	Version: 1.1.1
	Author: Clearcode
	Author URI: https://clearcode.cc
	Text Domain: toc
	Domain Path: /languages/
	License: GPLv3
	License URI: http://www.gnu.org/licenses/gpl-3.0.txt

	Copyright (C) 2020 by Clearcode <https://clearcode.cc>
	and associates (see AUTHORS.txt file).

	This file is part of CC-TOC plugin.

	CC-TOC plugin is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	CC-TOC plugin is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with CC-TOC plugin; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( !defined( 'ABSPATH' ) ) exit;

require_once( __DIR__ . '/class-toc-settings.php' );
require_once( __DIR__ . '/class-toc-walker-page.php' );
require_once( __DIR__ . '/class-toc.php' );

if ( ! defined( 'TOC_PATH' ) ) define( 'TOC_PATH', __FILE__ );
$toc = new \Clearcode\TOC( TOC_PATH );
