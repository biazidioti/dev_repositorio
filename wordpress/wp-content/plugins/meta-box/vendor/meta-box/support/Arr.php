<?php
namespace MetaBox\Support;

class Arr {
	/**
	 * New array map function that accepts more params than just values.
	 * Params: array|item, callback, other params.
	 */
	public static function map() {
		$args     = func_get_args();
		$items    = array_shift( $args );
		$callback = array_shift( $args );

		if ( ! is_array( $items ) ) {
			array_unshift( $args, $items );
			return call_user_func_array( $callback, $args );
		}

		return array_map(
			function( $item ) use ( $callback, $args ) {
				array_unshift( $args, $item );
				return call_user_func_array( $callback, $args );
			},
			$items
		);
	}

	/**
	 * Convert a comma separated string to array.
	 *
	 * @param array|string $csv Comma separated string.
	 */
	public static function from_csv( $csv ) : array {
		return is_array( $csv ) ? $csv : array_filter( array_map( 'trim', explode( ',', $csv . ',' ) ) );
	}

	/**
	 * Change array key.
	 *
	 * @param  array  $array Input array.
	 * @param  string $from  From key.
	 * @param  string $to    To key.
	 */
	public static function change_key( &$array, $from, $to ) {
		if ( isset( $array[ $from ] ) ) {
			$array[ $to ] = $array[ $from ];
		}
		unset( $array[ $from ] );
	}

	/**
	 * Ensure a variable is an array.
	 */
	public static function ensure( $input ) : array {
		return (array) $input;
	}

	/**
	 * Flatten an array.
	 * @link https://stackoverflow.com/a/1320156/371240
	 */
	public static function flatten( array $array ) : array {
		$return = [];
		array_walk_recursive(
			$array,
			function( $a ) use ( &$return ) {
				$return[] = $a;
			}
		);
		return $return;
	}

	/**
	 * Convert flatten collection (with dot notation) to multiple dimensional array
	 *
	 * @param  array $collection Collection to be flatten.
	 * @return array
	 */
	public static function unflatten( $collection ) {
		$collection = (array) $collection;
		$output     = [];

		foreach ( $collection as $key => $value ) {
			self::set( $output, $key, $value );

			if ( is_array( $value ) && ! strpos( $key, '.' ) ) {
				$nested         = self::unflatten( $value );
				$output[ $key ] = $nested;
			}
		}

		return $output;
	}

	/**
	 * Set array element value with dot notation.
	 */
	public static function set( &$array, $key, $value ) {
		if ( is_null( $key ) ) {
			$array = $value;
			return $array;
		}

		// Do not parse email value.
		if ( is_email( $key ) ) {
			$array[ $key ] = $value;
			return;
		}

		$keys = explode( '.', $key );

		while ( count( $keys ) > 1 ) {
			$key = array_shift( $keys );

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}

			$array =& $array[ $key ];
		}

		$array[ array_shift( $keys ) ] = $value;
	}

	/**
	 * Get array element value with dot notation.
	 */
	public static function get( $array, $key, $default = null ) {
		if ( is_null( $key ) ) {
			return $array;
		}

		$keys = explode( '.', $key );
		foreach ( $keys as $key ) {
			if ( isset( $array[ $key ] ) ) {
				$array = $array[ $key ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	public static function to_depth( $input, $depth ) {
		$current_depth = is_array( $input ) ? self::depth( $input ) : 0;

		if ( $depth < $current_depth ) {
			while ( $current_depth > $depth ) {
				$input = reset( $input );
				$current_depth--;
			}
		} elseif ( $depth > $current_depth ) {
			while ( $current_depth < $depth ) {
				$input = [ $input ];
				$current_depth++;
			}
		}

		return $input;
	}

	public static function depth( array $array ) {
		$max_depth = 1;

		foreach ( $array as $key => $value ) {
			if ( !is_string($key) && is_array( $value ) ) {
				$depth = self::depth( $value ) + 1;

				if ( $depth > $max_depth ) {
					$max_depth = $depth;
				}
			}
		}

		return $max_depth;
	}

	public static function remove_first( &$array, $query ) {
		$keys = explode( '.', $query );
		$key  = array_shift( $keys );

		if ( count( $keys ) === 0 ) {
			if ( is_array( $array ) && array_key_exists( $key, $array ) ) {
				unset( $array[ $key ][0] );
			}
			return;
		}

		if ( $key === '*' ) {
			foreach ( $array as $k => $v ) {
				if ( is_array( $array[ $k ] ) ) {
					self::remove_first( $array[ $k ], implode( '.', $keys ) );
				}
			}
			return;
		}

		if ( $key === '' ) {
			return;
		}

		if ( is_array( $array[ $key ] ) ) {
			self::remove_first( $array[ $key ], implode( '.', $keys ) );
		}
	}
}