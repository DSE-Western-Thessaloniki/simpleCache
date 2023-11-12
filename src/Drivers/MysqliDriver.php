<?php

namespace Dsewth\SimpleCache\Drivers;

use Carbon\Carbon;
use Dsewth\SimpleCache\Drivers\Traits\Multiple;
use Dsewth\SimpleCache\Exceptions\CacheException;
use Dsewth\SimpleCache\Exceptions\InvalidArgumentException;

class MysqliDriver extends Driver {
	use Multiple;

	const DEFAULT_TTL = 15*60; // Default to 15 minutes
	protected \mysqli $connection;
	protected string $table;

	public function __construct(\mysqli $connection, string $table = 'cache') {
		$this->name = "MysqliDriver";
		$this->ttl = self::DEFAULT_TTL;
		$this->connection = $connection;
		$this->table = $table;
	}

	protected function deleteFromDatabase(string $key): bool {
		$stmt = $this->connection->prepare(
			"DELETE FROM `$this->table`
			WHERE `key` = ?"
		);
		if ($stmt === false) {
			throw new CacheException("Error preparing statement!");
		}
		$stmt->bind_param('s', $key);
		return $stmt->execute();
	}

	protected function findFromDatabase(string $key): ?array {
		$stmt = $this->connection->prepare(
			"SELECT `value`, `expiresAt`
			FROM `$this->table`
			WHERE `key` = ?
			LIMIT 1"
		);
		if ($stmt === false) {
			throw new CacheException("Error preparing statement!");
		}
		$stmt->bind_param('s', $key);
		$stmt->execute();

		$result = $stmt->get_result();
		if (!$result->num_rows) {
			return null;
		}

		return $result->fetch_assoc();
	}

	protected function storeInDatabase(string $key, $value, int $ttl): bool {
		$stmt = $this->connection->prepare(
			"INSERT INTO `$this->table`
			(`key`, `value`, `expiresAt`) 
			values (?, ?, ?)"
		);
		if ($stmt === false) {
			throw new CacheException("Error preparing statement!");
		}
		$stmt->bind_param('ssi', $key, $value, $ttl);
		return $stmt->execute();
	}

    public function get($key, $default = null) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		$result = self::findFromDatabase($key);
		
        if ($result) {
			// Check if the object has not expired
			$now = Carbon::now()->getTimestamp();
			if ($result["expiresAt"] > $now) {
				return unserialize($result["value"]);
			}
			
			self::deleteFromDatabase($key);
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
				return self::deleteFromDatabase($key);
			}

            $expiresAt->addSeconds($ttl);
        } else if (is_null($ttl)){
			$expiresAt->addSeconds($this->ttl);
		} else {
			throw new InvalidArgumentException('ttl must be an integer or a \DateInterval object or null');
		}

		return self::storeInDatabase($key, serialize($value), $expiresAt->getTimestamp());
    }

    public function delete($key) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		return self::deleteFromDatabase($key);
	}

    public function clear() {
		return $this->connection->query("DELETE FROM `$this->table`");
	}

    public function has($key) {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		$result = self::findFromDatabase($key);
		return ($result !== null);
	}

	public function getKeys(): array
	{
		$result = $this->connection->query("SELECT `key` FROM `$this->table`");
		return $result->fetch_all();
	}

	public function getExpirationTimestamp(string $key): int {
		if (!$this->isValidKey($key)) {
			throw new InvalidArgumentException('Cache key must be a string and not contain {}()/\@');
		}

		$result = self::findFromDatabase($key);
		if (is_null($result)) {
			return 0;
		}

		return $result["expiresAt"];
	}

}