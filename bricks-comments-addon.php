<?php

/*
Plugin Name: Bricks Comments Addon
Plugin URI: https://github.com/emmgeede/bricks-comments-addon
Description: Adds a setting to show the comment form before comments
Version: 1.0.0
$Author: Michael Großklos
Author URI: https://emmgee.de
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: bricks
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bricks_Comments_Addon {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'bca_register_scripts' ] );
		
		add_filter( 'bricks/elements/post-comments/control_groups', [ $this, 'bca_add_control_group' ], 10, 1 );
		add_filter( 'bricks/elements/post-comments/controls', [ $this, 'bca_add_controls' ], 10, 2 );
		add_filter( 'bricks/element/settings', [ $this, 'bca_enqueue_scripts_on_true' ], 10, 2 );
	}
	
	/**
	 * Add cusdtom control group
	 *
	 * @param $control_groups
	 *
	 * @return mixed
	 */
	public function bca_add_control_group( $control_groups ) {
		$control_groups['bcaCustomGroup'] = [
			'tab'   => 'content',
			'title' => esc_html__( 'Custom Form Settings', 'bricks' ),
		];
		
		return $control_groups;
	}
	
	/**
	 * Wrapping the comment elements with a wrapper
	 *
	 * @param $content
	 * @param $post
	 * @param $area
	 *
	 * @return false|string
	 * @throws DOMException
	 */
	public function bca_wrap_comments_elements( $content, $post, $area ) {
		$doc = new DOMDocument();
		@$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED |
		                                                                            LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $doc );
		
		$comments_title = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' comments-title ')]" );
		$comment_list   = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' comment-list ')]" );
		
		if ( $comments_title->length > 0 && $comment_list->length > 0 ) {
			$wrapper = $doc->createElement( 'div' );
			$wrapper->setAttribute( 'class', 'comments__wrapper' );
			
			$comments_title->item( 0 )->parentNode->insertBefore( $wrapper, $comments_title->item( 0 ) );
			
			$wrapper->appendChild( $comments_title->item( 0 ) );
			$wrapper->appendChild( $comment_list->item( 0 ) );
		}
		
		$newHtml = $doc->saveHTML();
		
		return $newHtml;
	}
	
	/**
	 * Register the javascript and css files for the frontend
	 *
	 * @return void
	 */
	public function bca_register_scripts() {
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
	}
	
	/**
	 * Enqueue the javascript and css files for the frontend
	 *
	 * @param $settings
	 * @param $element
	 *
	 * @return mixed
	 */
	public function bca_enqueue_scripts_on_true( $settings, $element ) {
		if (
			! is_admin() &&
			$element->name == 'post-comments' &&
			isset( $element->settings['bcaCommentFormFirst'] ) &&
			$element->settings['bcaCommentFormFirst']
		) {
			add_filter( 'bricks/frontend/render_data', [ $this, 'bca_wrap_comments_elements' ], 10, 3 );
			wp_enqueue_style( 'bricks-comments-addon' );
		}
		
		return $settings;
	}
	
	/**
	 * Add additional controls to the Bricks comment element
	 *
	 * @param $controls
	 *
	 * @return array
	 */
	public function bca_add_controls( $controls ) {
		$controls['bcaCommentFormFirst'] = [
			'tab'     => 'content',
			'group'   => 'bcaCustomGroup',
			'label'   => esc_html__( 'Show form before comments', 'bricks' ),
			'type'    => 'checkbox',
			'default' => false,
			'inline'  => true,
			'small'   => true,
		];
		
		$controls['bcaGridGap'] = [
			'tab'         => 'content',
			'group'       => 'bcaCustomGroup',
			'label'       => esc_html__( 'Gap to form', 'bricks' ),
			'description' => esc_html__( 'Set the gap between the comment form and the comments', 'bricks' ),
			'type'        => 'number',
			'default'     => 50,
			'units'       => [ 'px', 'em', 'rem', '%' ],
			'unit'        => 'px',
			'inline'      => true,
			'small'       => true,
			'css'         => [
				[
					'property' => 'grid-gap',
					'selector' => '.bricks-comments-inner',
				],
			],
			'required'    => [ 'bcaCommentFormFirst', '!=', '' ],
		];
		
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