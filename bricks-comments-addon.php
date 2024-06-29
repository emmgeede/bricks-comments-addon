<?php

/*
Plugin Name: Bricks Comments Addon
Plugin URI: https://emmgee.de
Description: Adds additional controls toi the Bricks comment element
Version: 0.0.1
Author: Michael Großklos
Author URI: https://emmgee.de
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: bricks
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bricks_Comments_Addon {
	public function __construct() {
		add_filter( 'bricks/elements/post-comments/controls', [ $this, 'bca_add_controls' ] );
		add_filter( 'bricks/element/settings', [ $this, 'bca_manipulate_frontend' ], 10, 2 );
	}
	
	public function bca_manipulate_frontend( $settings, $element ) {
		if (
			! is_admin() &&
			$element->name == 'post-comments' &&
			isset( $element->settings['bcaCommentFormFirst'] ) &&
			$element->settings['bcaCommentFormFirst']
		) {
			//	wp_die( '<pre>' . print_r( $settings, true ) . '</pre>' );
			
			wp_register_script(
				'bricks-comments-addon',
				plugins_url( 'js/bricks-comments-addon.js', __FILE__ ),
				[],
				fileatime( __DIR__ . "/js/bricks-comments-addon.js" ),
				true
			);
			wp_register_style(
				'bricks-comments-addon',
				plugins_url( 'css/bricks-comments-addon.css', __FILE__ ),
				[],
				fileatime( __DIR__ . "/css/bricks-comments-addon.css" )
			);
			
			wp_enqueue_script( 'bricks-comments-addon' );
			wp_enqueue_style( 'bricks-comments-addon' );
		}
		
		return $settings;
		
	}
	
	public function bca_add_controls( $controls ) {
		//	wp_die( '<pre>' . print_r( $controls, true ) . '</pre>' );
		
		$bcaCommentFormFirst['bcaCommentFormFirst'] = [
			'tab'     => 'content',
			'group'   => 'form',
			'label'   => esc_html__( 'Show form before comments', 'bricks' ),
			'type'    => 'checkbox',
			'default' => false,
			'inline'  => true,
			'small'   => true,
		];
		
		$controls = $this->array_insert_after( $controls, 'formTitle', $bcaCommentFormFirst );
		
		return $controls;
	}
	
	/**
	 * Insert a value or key/value pair after a specific key in an array. If key doesn't exist, value is appended
	 * to the end of the array.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  array   $new
	 *
	 * @return array
	 */
	private function array_insert_after( array $array, $key, array $new ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys );
		$pos   = false === $index ? count( $array ) : $index + 1;
		
		return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
	}
	
}

$mg_addon = new Bricks_Comments_Addon();