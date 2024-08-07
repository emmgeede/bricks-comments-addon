<?php

/*
Plugin Name: Bricks Comments Addon (Development)
Plugin URI: https://github.com/emmgeede/bricks-comments-addon
Description: Adds a couple of settings to the bricks comment element
Version: 1.0.3
Author: emmgee Digitalagentur
Author URI: https://emmgee.de
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: bricks
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require 'libs/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class Bricks_Comments_Addon {
	public $comment_form_label = 'Comment *';
	
	public function __construct() {
		add_action( 'init', [ $this, 'bca_check_for_updates' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'bca_register_scripts' ] );
		
		add_filter( 'bricks/elements/post-comments/control_groups', [ $this, 'bca_add_control_group' ], 10, 1 );
		add_filter( 'bricks/elements/post-comments/controls', [ $this, 'bca_add_controls' ], 10, 2 );
		add_filter( 'bricks/element/settings', [ $this, 'bca_enqueue_scripts_on_true' ], 10, 2 );
	}
	
	/**
	 * Check if there are updates
	 *
	 * @return void
	 */
	public function bca_check_for_updates() {
		$myUpdateChecker = PucFactory::buildUpdateChecker(
			'https://github.com/emmgeede/bricks-comments-addon',
			__FILE__,
			'bricks-comments-addon'
		);
		
		$myUpdateChecker->setBranch( 'main' );
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
	 * and add a skiplink to the topo f the comments
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
		$comments       = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' bricks-comments-inner ')]" );
		
		if ( $comments_title->length > 0 && $comment_list->length > 0 ) {
			$wrapper = $doc->createElement( 'div' );
			$wrapper->setAttribute( 'class', 'comments__wrapper' );
			
			$comments_title->item( 0 )->parentNode->insertBefore( $wrapper, $comments_title->item( 0 ) );
			
			$wrapper->appendChild( $comments_title->item( 0 ) );
			$wrapper->appendChild( $comment_list->item( 0 ) );
		}
		
		if ( $comments->length > 0 ) {
			$link = $doc->createElement( 'a' );
			$link->setAttribute( 'href', '#respond' );
			$link->setAttribute( 'class', 'skip-link' );
			$link->setAttribute( 'title', esc_html__( 'Skip to comment form', 'bricks' ) );
			$link->nodeValue = esc_html__( 'Skip to comment form', 'bricks' );
			
			$comments->item( 0 )->parentNode->insertBefore( $link, $comments->item( 0 ) );
		}
		
		$newHtml = $doc->saveHTML();
		
		return $newHtml;
	}
	
	/**
	 * Register the css file for the frontend
	 *
	 * @return void
	 */
	public function bca_register_scripts() {
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
		if ( ! is_admin() ) {
			
			if (
				isset( $element->settings['bcaFormLabel'] )
				&& $element->settings['bcaFormLabel'] != ''
			) {
				$this->comment_form_label = $element->settings['bcaFormLabel'];
				add_filter( 'bricks/frontend/render_data', [ $this, 'bca_change_form_label' ], 10, 3 );
			}
			
			if (
				$element->name == 'post-comments' &&
				isset( $element->settings['bcaCommentFormFirst'] ) &&
				$element->settings['bcaCommentFormFirst']
			) {
				add_filter( 'bricks/frontend/render_data', [ $this, 'bca_wrap_comments_elements' ], 10, 3 );
				wp_enqueue_style( 'bricks-comments-addon' );
			}
			
		}
		
		return $settings;
	}
	
	/**
	 * Change the form label
	 *
	 * @param $content
	 * @param $post
	 * @param $area
	 *
	 * @return mixed
	 */
	public function bca_change_form_label( $content, $post, $area ) {
		$content = str_replace( '<label for="comment">Comment *</label>',
			'<label for="comment">' . $this->comment_form_label . ' <span class="required">*</span></label>',
			$content );
		
		return $content;
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
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'description' => esc_html__( 'Gap between form and comments', 'bricks' ),
			'type'        => 'number',
			'default'     => 50,
			'units'       => true,
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
		
		$controls['bcaFormLabel'] = [
			'tab'     => 'content',
			'group'   => 'bcaCustomGroup',
			'label'   => esc_html__( 'Form label', 'bricks' ),
			'type'    => 'text',
			'default' => 'Comment',
		];
		
		return $controls;
	}
}

$mg_addon = new Bricks_Comments_Addon();