<?php

namespace SSNepenthe\Hestia\Cache;

use Closure;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Wp_Transient_Cache implements Cache_Interface {
	protected $prefix;

	public function __construct( $prefix ) {
		$prefix = (string) $prefix;

		// MD5 plus "_" take 33 characters which leaves us with 139 for our prefix.
		if ( 139 < strlen( $prefix ) ) {
			$prefix = substr( $prefix, 0, 139 );
		}

		$this->prefix = $prefix . '_';
	}

	/**
	 * Deletes all transients from the database with the specified prefix. Does
	 * nothing when site is using external object cache.
	 *
	 * Mostly swiped from populate_options() in wp-admin/includes/schema.php.
	 */
	public function flush() {
		global $wpdb;

		// Only needs to run if site is storing transients in database.
		if ( wp_using_ext_object_cache() ) {
			return;
		}

		$time = time();

		$transient_prefix = '_transient_' . $this->prefix;
		$timeout_prefix = '_transient_timeout_' . $this->prefix;
		$length = strlen( $transient_prefix ) + 1;

		$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( %s, SUBSTRING( a.option_name, %d ) )
			AND b.option_value < %d";

		$wpdb->query( $wpdb->prepare(
			$sql,
			$wpdb->esc_like( $transient_prefix ) . '%',
			$wpdb->esc_like( $timeout_prefix ) . '%',
			$timeout_prefix,
			$length,
			$time
		) );
	}

	public function forget( $key ) {
		return delete_transient( $this->generate_id( $key ) );
	}

	public function get( $key ) {
		$value = get_transient( $this->generate_id( $key ) );

		if ( false === $value ) {
			return null;
		}

		return $value;
	}

	public function has( $key ) {
		return false !== get_transient( $this->generate_id( $key ) );
	}

	public function put( $key, $value, $seconds = 0 ) {
		return set_transient( $this->generate_id( $key ), $value, $seconds );
	}

	public function remember( $key, $seconds, Closure $callback ) {
		if ( ! is_null( $value = $this->get( $key ) ) ) {
			return $value;
		}

		$this->put( $key, $value = $callback(), $seconds );

		return $value;
	}

	protected function generate_id( $key ) {
		return $this->prefix . hash( 'md5', (string) $key );
	}
}
