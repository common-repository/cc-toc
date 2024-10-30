<?php

/*
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

namespace Clearcode;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\TOC_Walker_Page' ) ) {
    class TOC_Walker_Page extends \Walker_Page {
        protected $domain = 'toc';

        public function __construct() {
            $this->domain = apply_filters( 'toc_domain', $this->domain );
        }

        public function end_el( &$output, $page, $depth = 0, $args = array() ) {
            global $post_id, $toc;
            $post = $post_id;
            $post_id = $page->ID;
            $meta = get_post_meta( $page->ID, sprintf( '_%s', $this->domain ), true );
            if( apply_filters( 'toc_cache_disable', false ) ) $meta['cache'] = $toc->toc();
            $output .= isset( $meta['cache'] ) ? $meta['cache'] : '';
            $output .= "</li>\n";
            $post_id = $post;
        }
    }
}
