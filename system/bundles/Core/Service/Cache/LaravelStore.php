<?php

namespace Core\Service\Cache;

use Core\Facade\Cache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Cache\MemcachedLock;

class LaravelStore implements Store, LockProvider {

	public function get($key) {
		return Cache::get($key);
	}

	public function many(array $keys) {
		// TODO: Implement many() method.
	}

	public function put($key, $value, $seconds) {
		Cache::put($key, $seconds, $value);
	}

	public function putMany(array $values, $seconds) {
		// TODO: Implement putMany() method.
	}

	public function increment($key, $value = 1) {
		return Cache::increment($key, $value);
	}

	public function decrement($key, $value = 1) {
		// TODO: Implement decrement() method.
	}

	public function forever($key, $value) {
		Cache::forever($key, $value);
	}

	public function forget($key) {
		Cache::forget($key);
	}

	public function flush() {
		Cache::flush();
	}

	public function getPrefix() {
		// TODO: Implement getPrefix() method.
	}
	    
	// -------------------------------
    // LockProvider-Implementierung
    // -------------------------------

    public function lock($name, $seconds = 0, $owner = null): Lock
    {
		$memcached = Cache::getDefault();
        return new MemcachedLock($memcached, $this->prefix.$name, $seconds, $owner);
    }

    public function restoreLock($name, $owner): Lock
    {
		$memcached = Cache::getDefault();
        return new MemcachedLock($memcached, $this->prefix.$name, 0, $owner);
    }
	
}
