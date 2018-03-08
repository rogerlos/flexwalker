<?php

namespace flexwalker\functions;

/**
 * Recursively merges two arrays. $a is always dominant, but extra key/value pairs in $b will be added.
 *
 * @since 1.0
 *
 * @param array $a               user submitted array
 * @param array $b               default array
 * @param bool  $append_numeric  if true, arrays with numeric keys will be appended to matching defaults
 *
 * @return array
 */
function parse_args_r( &$a, $b, $append_numeric = TRUE ) {
	
	$a = (array) $a;
	$b = (array) $b;
	$r = $b;
	
	foreach ( $a as $k => &$v ) {
		if ( $append_numeric === TRUE && positive_int( $k ) && isset( $r[ $k ] ) ) {
			$r[] = $v;
		} else if ( is_array( $v ) && isset( $r[ $k ] ) ) {
			$r[ $k ] = parse_args_r( $v, $r[ $k ] );
		} else {
			$r[ $k ] = $v;
		}
	}
	
	return $r;
}

/**
 * Determines if value is a whole, positive integer
 *
 * @since 1.0
 *
 * @param string|number $val  check this to see if it is a positive integer (including 0)
 *
 * @return bool
 */
function positive_int( $val ) {
	$val = strval( $val );
	return strpos( $val, '-' ) === FALSE && ctype_digit( $val ) && ( $val === (string) 0 || ltrim( $val, '0' ) === $val );
}

/**
 * Checks array of arrays and returns sub-array whose property with key $key matches $id.
 *
 * @since 1.0
 *
 * @param string $id      value expected for the key of a dots request
 * @param array  $array   array to examine
 * @param string $key     the array's internal key whose value to check against $id
 *
 * @return mixed
 */
function return_array_by_id( $id, $array, $key = 'id' ) {
	
	$ret = NULL;
	
	foreach ( $array as $subarray ) {
		if ( is_array( $subarray ) ) {
			foreach ( $subarray as $skey => $sval ) {
				if ( $skey == $key && $sval == $id ) {
					$ret = $subarray;
					break 2;
				}
			}
		}
	}
	
	return $ret;
}

/**
 * Produces a random ID. Can be passed a string.
 *
 * @since 1.0
 *
 * @param string $string  unused, but a base string can be passed if desired
 *
 * @return string
 */
function random_id( $string = '' ) {
	
	$rand = rand( 1000, 9999 );
	
	return $string ? $string . $rand : FLXW . $rand;
}

/**
 * Opens tags and adds attributes passed via tag array
 *
 * @since 1.4 Fixed non-exitent $arr bug, check for empty 'tag'
 * @since 1.0
 *
 * @param array $tag  a single tag configuration array from tags.json
 *
 * @return string
 */
function open_tag( $tag = [] ) {
	
	if ( empty( $tag ) || empty( $tag['tag'] ) ) {
		return '';
	}
	
	$ret = '<' . $tag['tag'];
	
	foreach ( $tag['attr'] as $att => $val ) {
		$val = is_array( $val ) ? implode( ' ', $val ) : $val;
		$ret .= ' ' . $att . '="' . $val . '"';
	}
	
	$ret .= '>';
	
	$cont = ! empty( $tag['content'] ) ? $tag['content'] : [ 'open' => '' ];
	$cont = ! empty( $cont['open'] ) ? $cont['open'] : '';
	
	return $ret . $cont;
}

/**
 * Closes open tag
 *
 * @since 1.4 Fixed non-exitent $arr bug, check for empty 'tag'
 * @since 1.0
 *
 * @param array $tag  a single tag configuration array from tags.json
 *
 * @return string
 */
function close_tag( $tag = [] ) {
	
	if ( empty( $tag ) || empty( $tag['tag'] ) ) {
		return '';
	}
	
	$cont = ! empty( $tag['content'] ) ? $tag['content'] : [ 'close' => '' ];
	$cont = ! empty( $cont['close'] ) ? $cont['close'] : '';
	
	return $cont . '</' . $tag['tag'] . '>';
}


/**
 * Reads file and returns decoded JSON as an array.
 *
 * @since 1.0
 *
 * @param string $file  complete path to file to be read
 *
 * @return array|mixed|object
 */
function read_json_cfg( $file ) {
	
	if ( file_exists( $file ) ) {
		$cont = file_get_contents( $file );
		
		return json_decode( $cont, TRUE );
	}
	
	return [];
}

/**
 * Recursive function to turn 'a.b.c' and $array['a']['b']['c'] = 'dork' into 'dork'.
 * Checks if a numerically indexed array contains a key matching $key, as well.
 *
 * @since 1.0
 *
 * @param string $key    in dots format, or can be empty
 * @param array  $array  array to check against
 *
 * @return mixed
 */
function dots( $key, $array ) {
	
	$ret = NULL;
	
	if ( $key == '' ) {
		return $array;
	}
	
	$dots = explode( '.', $key );
	$dot  = array_shift( $dots );
	$key  = implode( '.', $dots );
	
	if ( is_array( $array ) ) {
		$try   = return_array_by_id( $dot, $array );
		$array = $try === NULL ? $array : [ $dot => $try ];
	}
	
	if ( isset( $array[ $dot ] ) && ! empty( $key ) && is_array( $array[ $dot ] ) ) {
		$ret = dots( $key, $array[ $dot ] );
	} else if ( isset( $array[ $dot ] ) && empty( $key ) ) {
		$ret = $array[ $dot ];
	}
	
	return $ret;
}

/**
 * Simple recursive tokenizer.
 *
 * Looks for {{dots.encoded.array.keys}} within a string, and if it finds one, it sees if
 * $cfg['dots']['encoded']['array']['keys'] is set, and returns the value or empty array.
 *
 * @since 1.0
 *
 * @param string|array $array         Can be a plain string or an array
 * @param array        $cfg           Array to check for dots values
 * @param bool         $strings_only  True if all values returned within return array should be strings
 *
 * @return string|array
 */
function tokens( $array, $cfg, $strings_only = TRUE ) {
	
	$ret          = [];
	$backtostring = FALSE;
	
	if ( ! is_array( $array ) ) {
		$array        = (array) $array;
		$backtostring = TRUE;
	}
	
	foreach ( $array as $key => $value ) {
		
		$ret[ $key ] = $value;
		
		if ( ! is_array( $value ) ) {
			
			$ret[ $key ] = preg_replace_callback(
				'~{{(.+?)}}~',
				function ( $matches ) use ( $cfg ) {
					
					$result = dots( $matches[1], $cfg );
					if ( is_array( $result ) ) {
						$result = implode( ' ', $result );
					}
					
					return $result;
				},
				$value
			);
			
			if ( empty( $ret[ $key ] ) ) {
				$ret[ $key ] = '';
			}
			if ( $strings_only && is_array( $ret[ $key ] ) ) {
				$ret[ $key ] = implode( ' ', $ret[ $key ] );
			}
			
		} else {
			
			$ret[ $key ] = tokens( $value, $cfg );
		}
		
		if ( $strings_only && ! is_string( $ret[ $key ] ) ) {
			$ret[ $key ] = (string) $ret[ $key ];
		}
	}
	
	return $backtostring ? (string) $ret[0] : $ret;
}