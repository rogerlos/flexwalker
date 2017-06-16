<?php

/**
 * Autoloads classes at configured directories/namespaces
 *
 * @param $class
 */
function flexwalker_autoloader( $class ) {
	
	$base_dir   = __DIR__;
	$lower      = strtolower( $class );

	// array items: namespaced directories: [ directory, namespace ] otherwise: [ directory ]
	$dirs = [
		[ DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR, FLXW, ],
	];
	
	// check to see if the class being asked for is in dir above
	foreach ( $dirs as $dir ) {
		
		if ( isset( $dir[1] ) && $dir[1] ) {
			$class = str_replace( $dir[1], '', $class );
			$lower = strtolower( str_replace( $dir[1], '', $class ) );
		}
		
		$file      = $base_dir . $dir[0] . str_replace( '\\', '', $class ) . '.php';
		$filelower = $base_dir . $dir[0] . str_replace( '\\', '', $lower ) . '.php';
		
		if ( file_exists( $file ) ) {
			require $file;
			break;
		} else if ( file_exists( $filelower ) ) {
			require $filelower;
			break;
		}
	}
}