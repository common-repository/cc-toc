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
if ( ! class_exists( __NAMESPACE__ . '\TOC_Settings' ) ) {
    class TOC_Settings {
        private $version = null;

        protected $post_types = array( 'post', 'page' );
        protected $exclude_heading = '';
        protected $start_heading = '';
        protected $toc = array(
            'shortcode' => 'toc',
            'type' => 'ol',
            'before' => '<nav>',
            'title' => '<h2>%s</h2>',
            'after' => '</nav>'
        );
        protected $index = array( 
            'shortcode' => 'index',
            'type' => 'ol',
            'before' => '<nav>',
            'title' => '<h2>%s</h2>',
            'after' => '</nav>'
        );

        public function __construct( $version ) {
            $this->version = $version;

            $this->toc['title']   = sprintf( $this->toc['title'],   __( 'Table of contents', 'toc' ) );
            $this->index['title'] = sprintf( $this->index['title'], __( 'Index', 'toc' ) );

            add_action( 'admin_init', array( $this, 'admin_init' ) );
        }

        public function __wakeup() {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
        }

        public function __get( $name ) {
            if( property_exists( $this, $name ) )
                return $this->$name;
            return;
        }

        public function admin_init() {
            register_setting( 'writing', 'toc', array( $this, 'sanitize' ) );
            add_settings_section( 'toc', __( 'TOC', 'toc' ), array( $this, 'section' ), 'writing' ); 
            add_settings_field( 'post_types', __( 'Post types', 'toc' ), array( $this, 'post_types'), 'writing', 'toc' );
            add_settings_field( 'exclude_heading', __( 'Exclude heading', 'toc' ), array( $this, 'exclude_heading'), 'writing', 'toc' );
            add_settings_field( 'start_heading', __( 'Start heading', 'toc' ), array( $this, 'start_heading'), 'writing', 'toc' );
            add_settings_field( 'toc', __( 'TOC', 'toc' ), array( $this, 'settings'), 'writing', 'toc', array( 'field' => 'toc' ) );
            add_settings_field( 'index', __( 'INDEX', 'toc' ), array( $this, 'settings'), 'writing', 'toc', array( 'field' => 'index' ) );
            
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_post' ) );
        }

        public function section() {
            printf( '<p>%s</p>', __( 'TOC settings', 'toc' ) );
        }

        protected function get_post_types( $output ) {
            switch( $output ) {
                case 'objects':
                    $array = array( get_post_type_object( 'post' ), get_post_type_object( 'page' ) );
                    return array_merge( $array, get_post_types( array( '_builtin' => false ), 'objects' ) );
                case 'names':
                default:
                    return array_merge( array( 'post', 'page' ), get_post_types( array( '_builtin' => false ) ) );
            }
        }

        // TODO errors
        public function sanitize( $settings ) {
            if ( is_a( $settings, __CLASS__ ) ) return $settings;

            $post_types = $this->get_post_types( 'names' ); 
            $post_types = array_intersect( (array)$settings['post_types'], $post_types );
            $this->post_types = $post_types;
            $this->exclude_heading = 'h1' === $settings['exclude_heading'] ? 'h1' : null;
            $this->start_heading = 'h2' === $settings['start_heading'] ? 'h2' : null;
            foreach( array( 'toc', 'index' ) as $field ) {
                $this->{$field}['type'] = in_array( $settings[$field]['type'], array( 'ol', 'ul' ) ) ? $settings[$field]['type'] : $this->{$field}['type'];
                $this->{$field}['before'] = (string)$settings[$field]['before'];
                $this->{$field}['title'] = (string)$settings[$field]['title'];
                $this->{$field}['after'] = (string)$settings[$field]['after'];
                $this->{$field}['shortcode'] = sanitize_text_field( $settings[$field]['shortcode'] );
            }
            return $this;
        }

        protected function input( $type, $id, $name, $value, $label = '', $checked = '' ) {
            $input = '<label><input type="%s" id="%s" name="%s" value="%s" %s /> %s</label><br />';
            printf( $input, $type, $id, $name, $value, $checked, $label );
        }

        public function post_types() {
            $post_types = $this->get_post_types( 'objects' ); 

            foreach( $post_types as $post_type ) {
                $checked = checked( in_array( $post_type->name, $this->post_types ), true, false );
                $this->input( 'checkbox', sprintf( 'toc_%s', $post_type->name ), 'toc[post_types][]', $post_type->name, $post_type->labels->name, $checked );
            }
        }

        public function exclude_heading() {
            $checked = checked( $this->exclude_heading, 'h1', false );
            $this->input( 'checkbox', 'toc_exclude_heading', 'toc[exclude_heading]', 'h1', 'h1', $checked );
        }

        public function start_heading() {
            $checked = checked( $this->start_heading, 'h2', false );
            $this->input( 'checkbox', 'toc_start_heading', 'toc[start_heading]', 'h2', 'h2', $checked );
        }

        public function settings( $args ) {
            $field = $args['field'];
            foreach( array( 'ol', 'ul' ) as $type ) {
            $checked = checked( $this->{$field}['type'], $type, false );
                $id = sprintf( 'toc_%s_%s', $field, $type );
                $name = sprintf( 'toc[%s][type]', $field );
                $this->input( 'radio', $id, $name, $type, $type, $checked );
            }
            foreach( array( 'before', 'title', 'after' ) as $option ) {
                $id = sprintf( 'toc_%s_%s', $field, $option );
                $name = sprintf( 'toc[%s][%s]', $field, $option );
                $value = esc_attr( $this->{$field}[$option] );
                $label = ucfirst( __( $option, 'toc' ) );
                $this->input( 'text', $id, $name, $value, $label );
            }
        $this->input( 'text', 'toc_shortcode', sprintf( 'toc[%s][shortcode]', $field ), esc_attr( $this->{$field}['shortcode'] ), 'Shortcode' );
        }

        public function add_meta_boxes() {
            foreach( $this->post_types as $post_type )
                add_meta_box( 'toc', 
                    __( 'TOC', 'toc' ), 
                    array( $this, 'meta_box' ), 
                    $post_type,
                    'side'
                );
        }

        public function meta_box( $post ) {
            wp_nonce_field( 'toc', 'toc' );
            foreach( array( 'toc' => 'before content', 'index' => 'after content' ) as $meta => $desc ) {
                $checked = get_post_meta( $post->ID, sprintf( '_%s', 'toc' ), true );
                $checked = isset( $checked[$meta] ) ? 
                    checked( $checked[$meta], true, false ) : false;
                $this->input( 
                    'checkbox', 
                    sprintf( '%s_%s', 'toc', $meta ), 
                    sprintf( '_%s[%s]', 'toc', $meta ), 
                    true, 
                    __( sprintf( 'Disable %s %s', strtoupper( $meta ), $desc ), 'toc' ), 
                    $checked 
                );
            }
        }

        public function save_post( $post_id ) {
            // nonce is set
            if( !isset( $_REQUEST['toc'] ) )
                return $post_id;
            // nonce is valid
            if( !check_admin_referer( 'toc', 'toc' ) )
                return $post_id;
            // is autosave
            if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
                return $post_id;
            // user can
            $post_type = get_post_type_object( $_REQUEST['post_type'] );
            if( !current_user_can( $post_type->cap->edit_post, $post_id ) )
                return $post_id;
            // save
            $meta = sprintf( '_%s', 'toc' );
            if( isset( $_REQUEST[$meta] ) )
                update_post_meta( $post_id, $meta, $_REQUEST[$meta] );
            else delete_post_meta( $post_id, $meta );
        }
    }
}
