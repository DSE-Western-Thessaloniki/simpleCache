<?php

namespace Dsewth\SimpleCache\Drivers;

abstract class Driver implements DriverInterface {
	protected string $name;
	protected int $ttl;

	/**
	 * Return the name of the driver.
	 *  @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Check whether the key is valid. A key is valid if it's a string
	 * but it doesn't contain any of the characters {}()/\@.
	 * @param string $key 
	 * @return bool 
	 */
	public function isValidKey(string $key): bool {
		if (!is_string($key)) {
			return false;
		}

		$chars = array('{', '}', '(', ')', '/', '\\', '@');

		foreach ($chars as $char) {
			if (strpos($key, $char) !== false) {
				return false;
			}
		}

		return true;
	}

	public function setDefaultTtl(int $ttl): void {
		$this->ttl = $ttl;
	}

	public function getDefaultTtl(): int {
		return $this->ttl;
	}
}