<?php

namespace Dsewth\SimpleCache\Drivers;

use Dsewth\SimpleCache\Drivers\Traits\Multiple;
use Psr\SimpleCache\InvalidArgumentException;
use Carbon\Carbon;

class MemoryDriver extends Driver {
	use Multiple;

	const DEFAULT_TTL = 15*60; // Default to 15 minutes
	protected array $data = array();

	public function __construct() {
		$this->name = "MemoryDriver";
		$this->ttl = self::DEFAULT_TTL;
	}

    public function get($key, $default = null) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

        if (isset($this->data[$key])) {
			$object = $this->data[$key];
			
			// Check if the object has not expired
			$now = Carbon::now();
			if ($object->expiresAt > $now) {
				return $object->value;
			}
			unset($this->data[$key]);
		}
		
		return $default;
    }

    public function set($key, $value, $ttl = null) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

        $expiresAt = Carbon::now();

        if ($ttl instanceof \DateInterval) {
            $expiresAt->add($ttl);
        } else if (is_int($ttl)) {
			// If ttl 0 or negative, remove from cache
			if ($ttl < 1) {
				unset($this->data[$key]);
				return true;
			}

            $expiresAt->addSeconds($ttl);
        } else if (is_null($ttl)){
			$expiresAt->addSeconds($this->ttl);
		} else {
			throw new InvalidArgumentException('ttl must be an integer or a \DateInterval object or null');
		}

		$object = new \stdClass();
		$object->expiresAt = $expiresAt;
		$object->value = $value;
		$this->data[$key] = $object;

		return true;
    }

    public function delete($key) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		if (isset($this->data[$key])) {
			unset($this->data[$key]);
		}
		
		return true;
	}

    public function clear() {
		$this->data = array();

		return true;
	}

    public function has($key) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		return isset($this->data[$key]);
	}

	public function getKeys(): array
	{
		return array_keys($this->data);
	}

	public function getExpirationTimestamp(string $key): int {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		
		if (!isset($this->data[$key])) {
			return 0;
		}

		$object = $this->data[$key];

		return $object->expiresAt->getTimestamp();
	}

}