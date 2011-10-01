<?php

/*
Plugin Name: scGeSHi
Plugin URI: http://lloc.de/scGeSHi
Description: Simple code highlighting plugin for your remote source files
Version: 0.3
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

define ('SCGESHI_MIN_PHP', '5.2');
define ('SCGESHI_MIN_WP', '2.8');
define ('SCGESHI_TPREFIX', 'scg_');

function scGeSHi_activate () {
	$error = '';
	$phpversion = phpversion();
    if (version_compare (SCGESHI_MIN_PHP, $phpversion, '>'))
		$error .= sprintf ("Minimum PHP version required is %s, not %s.\n", SCGESHI_MIN_PHP, $phpversion);
    $wpversion = get_bloginfo('version');
    if (version_compare (SCGESHI_MIN_WP, $wpversion, '>'))
		$error .= sprintf ("Minimum PHP version required is %s, not %s.\n", SCGESHI_MIN_WP, $wpversion);
	if (!$error) return;
	deactivate_plugins (__FILE__);
	die ($error);
}
register_activation_hook (__FILE__, 'scGeSHi_activate');

function scGeSHi_deactivate () {
	scGeSHi::clear ();
}
register_deactivation_hook (__FILE__, 'scGeSHi_deactivate');

class scGeSHi {

	public function get ($source, $lang) {
		if (!class_exists ('geshi')) { 
			require_once ('geshi.php');
		}
		$geshi = new GeSHi ($source, $lang);
		$geshi->enable_classes ();
		$geshi->set_overall_class ('scgeshi');
		$geshi->set_header_type (GESHI_HEADER_DIV);
		if ($geshi->error ()) {
			return $geshi->error ();
		}
		return $geshi->parse_code ();
    }

	static function clear () {
		global $wpdb;
		$wpdb->query ("DELETE FROM {$wpdb->options} WHERE option_name like '%_transient_%{SCGESHI_TPREFIX}%'");
	}

}

function my_scGeSHi ($atts) {
	extract (
		shortcode_atts (
			array (
				'lang' => 'php',
				'href' => '',
				'out' => 'An error occurred while processing your request.',
			),
			$atts
		)
	);
	if (!empty ($href)) {
		$name = SCGESHI_TPREFIX . md5 ($href);
		if (false === ($source = get_transient ($name))) {
			$response = wp_remote_get ($href);
			if (!is_wp_error ($response)) {
				$source = trim ($response['body']);
				if (!empty ($source)) {
					set_transient ($name, $source, 86400);
				}
			}
		}
		$scg = new scGeSHi ();
		$out = $scg->get ($source, $lang);
	}
	return sprintf ('<div class="scgeshi-container">%s</div>', $out);
}
add_shortcode ('source', 'my_scGeSHi');

?>