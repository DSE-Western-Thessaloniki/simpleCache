<?php

namespace Dsewth\SimpleCache\Drivers;

use Psr\SimpleCache\CacheInterface;

interface DriverInterface extends CacheInterface {
	/**
	 * Return the name of the driver.
	 *  @return string
	 */
	public function getName(): string;

	/**
	 * Check whether the key is valid.
	 * @param string $key 
	 * @return bool 
	 */
	public function isValidKey(string $key): bool;

	/**
	 * Get the default TTL value 
	 * @return int $ttl 
	 */
	public function getDefaultTtl(): int;

	/**
     * Set the default TTL value (in seconds)
     * @param int $ttl 
     */
	public function setDefaultTtl(int $ttl): void;

	/**
	 * Return an array of all the keys stored in the cache.
	 *  @return string[]  
	 */
	public function getKeys(): array;

	/**
     * Get the expiration timestamp for a key.
     * @param string $key 
     * @return int 
     */
	public function getExpirationTimestamp(string $key): int;
}