<?php
/**
 * Transient_Store class.
 *
 * @package hestia
 */

namespace SSNepenthe\Hestia\Cache;

use wpdb;

/**
 * Defines the transient cache store class.
 *
 * @todo Consider *_site_transient() functions...
 */
class Transient_Store extends Abstract_Store {
	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Class constructor.
	 *
	 * @param wpdb   $db     WordPress database instance.
	 * @param string $prefix Cache prefix.
	 */
	public function __construct( wpdb $db, $prefix = '' ) {
		$this->db = $db;
		$this->set_prefix( $prefix );
	}

	/**
	 * Flush the cache.
	 *
	 * @return boolean
	 */
	public function flush() {
		if ( wp_using_ext_object_cache() ) {
			return false;
		}

		$sql = "DELETE FROM {$this->db->options}
			WHERE option_name LIKE %s";

		$count = $this->db->query( $this->db->prepare(
			$sql,
			$this->db->esc_like( '_transient_' . $this->prefix ) . '%'
		) );

		return false === $count ? false : true;
	}

	/**
	 * Flush expired entries from the cache.
	 *
	 * @return boolean
	 */
	public function flush_expired() {
		if ( wp_using_ext_object_cache() ) {
			return false;
		}

		$now = time();

		$transient_prefix = '_transient_' . $this->prefix;
		$timeout_prefix = '_transient_timeout_' . $this->prefix;
		$length = strlen( $transient_prefix ) + 1;

		$sql = "DELETE a, b FROM {$this->db->options} a, {$this->db->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( %s, SUBSTRING( a.option_name, %d ) )
			AND b.option_value < %d";

		$count = $this->db->query( $this->db->prepare(
			$sql,
			$this->db->esc_like( $transient_prefix ) . '%',
			$this->db->esc_like( $timeout_prefix ) . '%',
			$timeout_prefix,
			$length,
			$now
		) );

		return false === $count ? false : true;
	}

	/**
	 * Remove an entry from the cache.
	 *
	 * @param  string $key Cache key.
	 *
	 * @return boolean
	 */
	public function forget( $key ) {
		return delete_transient( $this->hash_key( $key ) );
	}

	/**
	 * Get an entry from the cache.
	 *
	 * @param  string $key Cache key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		$value = get_transient( $this->hash_key( $key ) );

		return false === $value ? null : $value;
	}

	/**
	 * Put an entry in the cache.
	 *
	 * If saving to DB, will return false if existing value is same as new value.
	 *
	 * @param  string  $key     Cache key.
	 * @param  mixed   $value   The value to put in the cache.
	 * @param  integer $seconds Time to cache expiration in seconds.
	 *
	 * @return boolean
	 */
	public function put( $key, $value, $seconds ) {
		return set_transient(
			$this->hash_key( $key ),
			$value,
			max( 0, $seconds )
		);
	}

	/**
	 * Hash a given key to create the real cache key.
	 *
	 * @param  string $key Desired cache key.
	 *
	 * @return string
	 */
	protected function hash_key( $key ) {
		return $this->prefix . parent::hash_key( $key );
	}

	/**
	 * Set the cache prefix ensuring we won't go over the DB key limit.
	 *
	 * @param string $prefix Cache prefix.
	 */
	protected function set_prefix( $prefix ) {
		// SHA1 plus "_" take 41 characters which leaves us with 131 for our prefix.
		if ( 131 < strlen( $prefix ) ) {
			$prefix = substr( $prefix, 0, 131 );
		}

		$this->prefix = empty( $prefix ) ? '' : $prefix . '_';
	}
}
