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
if ( ! class_exists( __NAMESPACE__ . '\TOC' ) ) {
    class TOC {
        const VERSION = '1.1.1';

        protected $settings = null;
        protected $content  = array();
        protected $anchors  = array();

        public function __construct( $file ) {
            $file = apply_filters( 'toc_file', $file );
            $dir  = apply_filters( 'toc_dir',  plugin_dir_path( $file ) );
            
            load_plugin_textdomain( 'toc', false, basename( $dir ) . '/languages' );

            // TODO add network activation hook
            register_activation_hook(   $file, array( $this, 'activation' ) );
            register_deactivation_hook( $file, array( $this, 'deactivation' ) );
            
            add_action( 'init', array( $this, 'init' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( $file ), array( $this, 'links' ) );
        }

        public function activation() {
            add_option( 'toc', new TOC_Settings( self::VERSION ) );
        }

        public function deactivation() {
            delete_option( 'toc' );
        }

        public function init() {
            if( !get_option( 'toc' ) ) $this->activation();

            $this->settings = get_option( 'toc' );
            $settings = $this->settings;

            remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );

            add_filter( 'get_the_excerpt', array( $this, 'wp_trim_excerpt' ) );
            add_filter( 'the_content',     array( $this, 'the_content' ) );

            add_shortcode( $settings->toc['shortcode'],   array( $this, 'toc_shortcode' ) );
            add_shortcode( $settings->index['shortcode'], array( $this, 'index_shortcode' ) );
        }

        public function links( $links ) {
            array_unshift( $links, sprintf( '<a href="%s">%s</a>', get_admin_url( null, 'options-writing.php' ), __( 'Settings', 'toc' ) ) );
            return $links;
        }

        public function toc() {
            global $post_id;

            $settings = $this->settings;

            $level = 0;
            $toc = '';
            foreach( $this->content as $match ) {
                $match['level'] = 'h1' === $settings->exclude_heading ? --$match['level'] : $match['level'];
                $link = sprintf( '<a href="%s#%s">%s</a>', get_permalink( $post_id ), $match['anchor'], $match['title'] );
                // same level
                if( $level == $match['level'] )
                    $toc .= sprintf( '</li><li>%s', $link );
                // level up
                elseif( $level < $match['level'] ) {
                    $diff = $match['level'] - $level;
                    while( $diff-- > 0 )
                        $toc .= sprintf( '<%s><li>', $settings->toc['type'] );
                    $toc .= $link;
                }
                // level down
                elseif( $level > $match['level'] ) {
                    $diff = $level - $match['level'];
                    while ( $diff-- > 0 )
                        $toc .= sprintf( '</li></%s>', $settings->toc['type'] );
                    $toc .= sprintf( '<li>%s', $link );
                }
                // current level
                $level = $match['level'];
            }
            // close level
            while( $level-- > 0 )
                $toc .= sprintf( '</li></%s>', $settings->toc['type'] );
            return $toc;
        }

        public function toc_shortcode() {
            global $post_id;
            //if( !$this->is_tocable() ) return;
            $meta = get_post_meta( $post_id, sprintf( '_%s', 'toc' ), true );
            if( empty( $meta ) ) $meta = array();

            if( !isset( $meta['cache'] ) ) {
                $meta['cache'] = $this->toc();
                update_post_meta( $post_id, sprintf( '_%s', 'toc' ), $meta );
            }

            if( is_admin() || apply_filters( 'toc_cache_disable', false ) )
                $meta['cache'] = $this->toc();

            if( !$meta['cache'] ) return;
            return $this->shortcode( 'toc', $meta['cache'] );
        }

        protected function index() {
            global $post_id;
            $post = get_post( $post_id );

            $settings = $this->settings;
            
            $index = wp_list_pages( 
                array( 
                    'child_of' => $post->ID, 
                    'post_type' => $post->post_type, 
                    'echo' => false,
                    'title_li' => null,
                    'walker' => new TOC_Walker_Page
                )
            );

            if( !$index ) return;
            $index = sprintf( '<%s>%s</%s>',
                $settings->index['type'],
                $index,
                $settings->index['type']
            );
            return $this->shortcode( 'index', $index );
        }

        public function index_shortcode() {
            //if( !$this->is_tocable() ) return;
            return $this->index();
        }

        protected function shortcode( $shortcode, $content ) {
            $settings = $this->settings;
            foreach( $settings->{$shortcode} as $key => $value )
                $$key = apply_filters( sprintf( 'toc_%s_%s', $shortcode, $key ), $value );

            return $before . $title . $content . $after;
        }

        public function has_shortcode() {
            global $post_id;
            if( !$post = get_post( $post_id ) ) return;

            $settings = $this->settings;
            return has_shortcode( $post->post_content, $settings->toc['shortcode'] );
        }

        public function is_tocable() {
            global $post_id;
            if( !$post = get_post( $post_id ) ) return;

            $settings = $this->settings;
            return in_array( $post->post_type, $settings->post_types );
        }

        public function is_disabled( $shortcode ) {
            global $post_id, $post;
            if( $post ) $post_id = $post->ID;
            if( !$post_id ) return false;
            $meta = get_post_meta( $post_id, sprintf( '_%s', 'toc' ), true );
            return isset( $meta[$shortcode] ) ? (bool)$meta[$shortcode] : false;
        }

        public function wp_trim_excerpt( $text ) {
            remove_filter( 'the_content', array( $this, 'the_content' ) );
            $text = wp_trim_excerpt( $text );
            add_filter( 'the_content', array( $this, 'the_content' ) );
            return $text;
        }

        protected function replace_callback( $match ) {
            $level = $match[1];
            $attrs = $match[2];
            $title = $match[3];

            if ( false !== $pos = strpos( $attrs, $id = ' id=' ) ) {
                $start  = $pos + strlen( $id );
                $stop   = strpos( $attrs, ' ', $start );
                $length = $stop ? $stop - $start : strlen( $attrs );
                $anchor = trim( substr( $attrs, $start, $length ), '\'"' );
            } else {
                $anchor = sanitize_title( $title );
                $attrs .= sprintf( '%s"%s"', $id, $anchor );
            }

            $this->anchors[$anchor] = isset( $this->anchors[$anchor] ) ? ++$this->anchors[$anchor] : 0;
            $anchor = $this->anchors[$anchor] ? sprintf( '%s-%s', $anchor, $this->anchors[$anchor] ) : $anchor;

            $this->content[] = array( 'level' => $level, 'title' => strip_tags( $title ), 'anchor' => $anchor );

            $settings = $this->settings;
            $level = 'h2' === $settings->start_heading ? ++$level : (int)$level;

            return sprintf( '<h%s%s>%s</h%s>', $level, $attrs, $title, $level );
        }

        public function the_content( $raw_content ) {
            $this->content = array();
            $this->anchors = array();

            if( !$this->is_tocable() and !$this->has_shortcode() ) return $raw_content;

            $content  = preg_replace_callback('/<h(\d)([^>]*?)>(.+?)<\/h\1>/si', array( $this, 'replace_callback' ), $raw_content );
            $index    = $this->is_disabled( 'index' ) ? '' : $this->index();
            $content .= $index;

            if( empty( $this->content ) ) return $content;
            if( $this->has_shortcode()  ) return $content;
            if( !$this->is_tocable()    ) return $raw_content;

            $settings = $this->settings;
            return $this->is_disabled( 'toc' ) ? $raw_content . $index : sprintf( '[%s] %s', $settings->toc['shortcode'], $content );
        }
    }
}
