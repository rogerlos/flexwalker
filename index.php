<?php
/**
 * A flexible walker for WordPress Menus which allows more complex internal menu structures.
 *
 * @wordpress-plugin
 * Plugin Name:       Flexwalker
 * Plugin URI:        https://github.com/rogerlos/flexwalker
 * Description:       Generate Bootstrap 4 Menus. Note, relies on bootstrap 4 being present.
 * Version:           1.0
 * Author:            Roger Los
 * Author URI:        https://github.com/rogerlos/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       flexwalker
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $FLEX;

$req  = [ 'php' => 5.4, 'wp' => 4.0, ];
$core = [
	'path'    => plugin_dir_path( __FILE__ ),
	'url'     => plugin_dir_url( __FILE__ ),
	'version' => '1.3.0',
	'json'    => [ 'tags', 'templates', 'walker', 'display' ],
];

$FLEX = flexwalker_launch( $req, $core );

/**
 * Helper function that returns menu without having to remember namespaces, etc.
 *
 * @param array $args Additional arguments, see readme.
 *
 * @return bool|string
 */
function flexwalker( $args = [] ) {
	global $FLEX;
	if ( $FLEX !== FALSE ) {
		return $FLEX->menu( $args );
	}
	return '';
}

/**
 * Helper function that returns internal config object
 *
 * @param string $key  Specific key from cfg array you wish to retrieve, in "dots" format.
 *
 * @return bool|string
 */
function flexwalker_cfg( $key = '' ) {
	global $FLEX;
	if ( $FLEX !== FALSE ) {
		return $FLEX->cfg( $key );
	}
	return '';
}

/**
 * Checks if server supports plugin, returns class instance if true
 *
 * @param array $req   PHP and WP requirements
 * @param array $core  Core configuration
 *
 * @return bool|\flexwalker\Flexwalker
 */
function flexwalker_launch( $req, $core ) {
	
	define( 'FLXW', 'flexwalker' );
	require 'autoloader.php';
	require 'code/functions.php';
	spl_autoload_register( 'flexwalker_autoloader' );
	
	if ( floatval( phpversion() ) >= $req['php'] && floatval( get_bloginfo( 'version' ) ) >=$req['wp'] ) {
		
		$flex = new \flexwalker\Flexwalker( $core );
		$flex->cfg( '', flexwalker_config( $core['json'], $core['path'] ) );
		$flex->js();
		return $flex;
	}
	
	return FALSE;
}

/**
 * Reads JSON into $this->cfg. If the current theme has a 'flexwalker' dir, it will preferentially use those files.
 *
 * @since 1.0
 *
 * @param $files
 * @param $path
 * @return array
 */
function flexwalker_config( $files, $path ) {
	
	$themepath = get_stylesheet_directory() . '/' . FLXW;
	$cfg = [];
	
	foreach ( $files as $f ) {
		
		$file  = '/' . $f . '.json';
		$theme = \flexwalker\functions\read_json_cfg( $themepath . $file );
		
		$cfg[ $f ] = ! empty( $theme ) ?
			$theme : \flexwalker\functions\read_json_cfg( $path . '/cfg' . $file );
	}
	
	return $cfg;
}