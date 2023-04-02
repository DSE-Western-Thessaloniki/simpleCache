<?php

namespace Dsewth\SimpleCache;

use Psr\SimpleCache\CacheInterface;
use Dsewth\SimpleCache\Drivers\Driver;

class Cache implements CacheInterface {
	protected Driver $driver;

	public function __construct(Driver $driver) {
		$this->driver = $driver;
	}

    public function get($key, $default = null) {
        return $this->driver->get($key, $default);
    }

    public function set($key, $value, $ttl = null) {
        return $this->driver->set($key, $value, $ttl);
    }

    public function delete($key) {
        return $this->driver->delete($key);
    }

    public function clear() {
        return $this->driver->clear();
    }

    public function getMultiple($keys, $default = null) {
        return $this->driver->getMultiple($keys, $default);
    }

    public function setMultiple($values, $ttl = null) {
        return $this->driver->setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys) {
        return $this->driver->deleteMultiple($keys);
    }

    public function has($key) {
        return $this->driver->has($key);
    }

    public function getDriver(): Driver {
        return $this->driver;
    }
}