<?php
/*
Plugin Name: scGeSHi
Plugin URI: http://lloc.de/scGeSHi
Description: Simple code highlighting plugin for your remote source files
Version: 0.5
Author: Dennis Ploetner	
Author URI: http://lloc.de/
*/

/*
Copyright 2010  Dennis Ploetner  (email : re@lloc.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * scGeSHi
 *
 * With scGeSHi you can use a shortcode like
 * [source href="http://source_code_url" lang="php"]
 * in your post when you want to show a code-example.
 *  
 * @package scGeSHi
 */

/**
 * PHP 5.2 is required
 */
define( 'SCGESHI_MIN_PHP', '5.2' );

/**
 * WordPress 2.8 is required
 */
define( 'SCGESHI_MIN_WP', '2.8' );

/**
 * Plugin uses scg_ as prefix
 */
define( 'SCGESHI_TPREFIX', 'scg_' );

/**
 * Plugin Activation Hook
 * 
 * The function will check which version of PHP and Wordpress is installed and
 * die if the minimum requirements are not satisfied.
 *
 * @package scGeSHi
 */
function scgeshi_activate() {
	$error      = '';
	$phpversion = phpversion();
	if ( version_compare( SCGESHI_MIN_PHP, $phpversion, '>' ) ) {
		$error .= sprintf( "Minimum PHP version required is %s, not %s.\n", SCGESHI_MIN_PHP, $phpversion );
	}
	$wpversion = get_bloginfo( 'version' );
	if ( version_compare( SCGESHI_MIN_WP, $wpversion, '>' ) ) {
		$error .= sprintf( "Minimum PHP version required is %s, not %s.\n", SCGESHI_MIN_WP, $wpversion );
	}
	if ( !$error ) return;
	deactivate_plugins( __FILE__ );
	die ( $error );
}
register_activation_hook( __FILE__, 'scgeshi_activate' );

/**
 * Plugin Deactivation Hook
 * 
 * The function will clear all related entries in the database
 * 
 * @package scGeSHi
 */
function scgeshi_deactivate() {
	scGeSHi::clear();
}
register_deactivation_hook( __FILE__, 'scgeshi_deactivate' );

/**
 * GeSHi Adapter 
 *
 * @package scGeSHi
 */
class scGeSHi {

	/**
	 * Return parsed code
	 * 
	 * @access public
	 * @param string $source
	 * @param string $lang
	 * return string
	 */
	public function get( $source, $lang ) {
		if ( !class_exists( 'geshi' ) ) { 
			require_once ( 'geshi.php' );
		}
		$geshi = new GeSHi( $source, $lang );
		$geshi->enable_classes();
		$geshi->set_overall_class( 'scgeshi' );
		$geshi->set_header_type( GESHI_HEADER_DIV );
		if ( $geshi->error() )
			return $geshi->error();
		return $geshi->parse_code();
	}

	/**
	 * Delete all plugin-related entries in the database
	 */
	public static function clear() {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%{SCGESHI_TPREFIX}%'"
		);
	}

}

/**
 * Handles the shortcode
 * 
 * @package scGeSHi
 * @param array $attr
 * @return string
 */
function my_scgeshi( array $atts ) {
	extract(
		shortcode_atts(
			array(
				'lang' => 'php',
				'href' => '',
				'out' => 'An error occurred while processing your request.',
			),
			$atts
		)
	);
	if ( !empty( $href ) ) {
		$name = SCGESHI_TPREFIX . md5( $href );
		if ( false === ( $source = get_transient( $name ) ) ) {
			$response = wp_remote_get( $href );
			if ( !is_wp_error( $response ) ) {
				$source = trim( $response['body'] );
				if ( !empty( $source ) )
					set_transient( $name, $source, 86400 );
			}
		}
		$scg = new scGeSHi();
		$out = $scg->get( $source, $lang );
	}
	return sprintf( '<div class="scgeshi-container">%s</div>', $out );
}
add_shortcode( 'source', 'my_scgeshi' );

/**
 * Add stylesheet
 */
function my_scgeshi_stylesheet() {
	$url = plugins_url( 'scGeSHi.css', __FILE__ );
	wp_register_style( 'scGeSHistyle', $url );
	wp_enqueue_style( 'scGeSHistyle' );
}
add_action( 'wp_print_styles', 'my_scgeshi_stylesheet' );
