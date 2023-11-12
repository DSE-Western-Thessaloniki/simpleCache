<?php

use Carbon\Carbon;
use Dsewth\SimpleCache\Drivers\MysqliDriver;
use Dsewth\SimpleCache\Cache;
use Dsewth\SimpleCache\Exceptions\InvalidArgumentException;
use Symfony\Component\Dotenv\Dotenv;

beforeEach(function() {
	$dotenv = new Dotenv();
	$dotenv->load(__DIR__.'/../../.env');

	$host = $_ENV["DB_HOST"];
	$port = $_ENV["DB_PORT"];
    $username = $_ENV["DB_USERNAME"];
    $password = $_ENV["DB_PASSWORD"];
    $database = $_ENV["DB_DATABASE"];

    // Create a new MySQLi connection
    $mysqli = new mysqli($host, $username, $password, $database, $port);

	// Check if the connection was successful
    if ($mysqli->connect_errno) {
        throw new Exception(sprintf('Failed to connect to MySQL: %s', $mysqli->connect_error));
    }

	$mysqli->query(
		"CREATE table if not exists cache (
			`key` varchar(1024) not null, 
			`value` varchar(4096), 
			`expiresAt` int unsigned, 
			primary key (`key`)
		)"
	);

	$mysqli->query("DELETE FROM cache");

    // Set the MySQLi connection for the test
    $this->mysqli = $mysqli;
});

afterEach(function() {
	$this->mysqli->close();
});

it('can create a new cache in mysqli', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getName())->toBe('MysqliDriver');
});

it('can store and recall a value', function () {
    $value = "foo";
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->set('test', $value))->toBe(true);
    expect($cache->get('test'))->toBe($value);
    expect($cache->has('test'))->toBe(true);
});

it('returns the default value if the key does not exist', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->get('test'))->toBe(null);
    expect($cache->get('test', 0))->toBe(0);
    expect($cache->get('test', "default"))->toBe("default");
});

it('removes the value from the cache if you use ttl of 0 or negative', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    $cache->set('test', 'value');
    expect($cache->getDriver()->getKeys())->toHaveCount(1);
    $cache->set('test', 'value', 0);
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
});

it('removes expired keys', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    Carbon::setTestNow(Carbon::now());
    $cache->set('test', 'value', 60);
    expect($cache->getDriver()->getKeys())->toHaveCount(1);
    Carbon::setTestNow(new Carbon("+1 hour"));
    $cache->get('test');
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    Carbon::setTestNow();
});

it('accepts int and DateTimeInterval as ttl', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    Carbon::setTestNow(Carbon::now());
    $cache->set('test', 'value', 60);
    expect($cache->getDriver()->getKeys())->toHaveCount(1);
    Carbon::setTestNow(new Carbon("+61 seconds"));
    $cache->get('test');
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    $cache->set('test', 'value', new \DateInterval('P1D'));
    expect($cache->getDriver()->getKeys())->toHaveCount(1);
    Carbon::setTestNow(new Carbon("+1 day"));
    $cache->get('test');
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    Carbon::setTestNow();
});

it('can clear the cache', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    for($i = 0; $i < 100; $i++) {
        $cache->set("test$i", $i);
    }
    expect($cache->getDriver()->getKeys())->toHaveCount(100);
    $cache->clear();
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
});

it('can add multiple values at once', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test$i"] = $i;
    }
    $cache->setMultiple($values);
    expect($cache->getDriver()->getKeys())->toHaveCount(100);
    expect($cache->get('test0'))->toBe(0);
    expect($cache->get('test99'))->toBe(99);
});

it('can retrieve multiple values at once', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test$i"] = $i;
    }
    $cache->setMultiple($values);
    expect($cache->getDriver()->getKeys())->toHaveCount(100);
    $retrieved = $cache->getMultiple(array_keys($values));
    expect($retrieved)->toHaveCount(100);
    expect($retrieved['test0'])->toBe(0);
    expect($retrieved['test99'])->toBe(99);
});

it('can delete multiple values at once', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test$i"] = $i;
    }
    $cache->setMultiple($values);
    expect($cache->getDriver()->getKeys())->toHaveCount(100);
    unset($values['test10']);
    $cache->deleteMultiple(array_keys($values));
    expect($cache->getDriver()->getKeys())->toHaveCount(1);
    expect($cache->get('test10'))->toBe(10);
});

it('throws an exception if you give an invalid key', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect(fn() => $cache->set('test{1}', 'value'))
        ->toThrow(InvalidArgumentException::class);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test($i)"] = $i;
    }
    expect(fn() => $cache->getMultiple(array_keys($values)))
        ->toThrow(InvalidArgumentException::class);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test@$i"] = $i;
    }
    expect(fn() => $cache->setMultiple($values))
        ->toThrow(InvalidArgumentException::class);
        $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test/$i"] = $i;
    }
    expect(fn() => $cache->deleteMultiple(array_keys($values)))
        ->toThrow(InvalidArgumentException::class);
    expect(fn() => $cache->has('test\test2'))
        ->toThrow(InvalidArgumentException::class);
});

it('returns true always when deleting', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->delete('test'))->toBe(true);
    $cache->set('test', "This is a test");
    expect($cache->delete('test'))->toBe(true);
    $values = array();
    for ($i = 0; $i < 100; $i++) {
        $values["test$i"] = $i;
    }
    expect($cache->deleteMultiple(array_keys($values)))->toBe(true);
    $cache->setMultiple($values);
    expect($cache->deleteMultiple(array_keys($values)))->toBe(true);
});

it('returns an empty array when cache is empty', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    expect($cache->getDriver()->getKeys())->toHaveCount(0);
});

it('returns the ttl when requested', function () {
    $cache = new Cache(new MysqliDriver($this->mysqli));
    $cache->set('test', 'value', 10);
    expect($cache->getDriver()->getExpirationTimestamp('test'))->toBeInt();
});