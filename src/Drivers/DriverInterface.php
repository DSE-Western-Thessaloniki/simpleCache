<?php

namespace Dsewth\SimpleCache\Drivers;

use Psr\SimpleCache\CacheInterface;

interface DriverInterface extends CacheInterface {
	public function getName(): string;

	public function isValidKey(string $key): bool;

	public function getDefaultTtl(): int;

	public function setDefaultTtl(int $ttl): void;

	/**
	 * Return an array of all the keys stored in the cache.
	 *  @return array  
	 */
	public function getKeys(): array;

	public function getExpirationTimestamp(string $key): int;
}