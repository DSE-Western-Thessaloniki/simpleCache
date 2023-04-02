<?php

namespace Dsewth\SimpleCache\Drivers\Traits;

use Psr\SimpleCache\InvalidArgumentException;

trait Multiple {
	public function getMultiple($keys, $default = null) {
		if( !is_array( $keys ) && !$keys instanceof \Traversable ) {
			throw new InvalidArgumentException( 'getMultiple expects an array or Traversable of keys' );
		}

		$values = array();

		foreach($keys as $key) {
			$values[$key] = $this->get($key, $default);
		}

		return $values;
	}

    public function setMultiple($values, $ttl = null) {
		if( !is_array( $values ) && !$values instanceof \Traversable ) {
			throw new InvalidArgumentException( 'getMultiple expects an array or Traversable of keys' );
		}

		$result = true;
		foreach($values as $key => $value) {
			$result = $this->set($key, $value, $ttl) && $result;
		}

		return $result;
	}

    public function deleteMultiple($keys) {
		if( !is_array( $keys ) && !$keys instanceof \Traversable ) {
			throw new InvalidArgumentException( 'getMultiple expects an array or Traversable of keys' );
		}

		$result = true;
		foreach($keys as $key) {
			$result = $this->delete($key) && $result;
		}

		return $result;
	}
}