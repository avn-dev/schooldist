<?php

namespace Core\Console\Scheduling;

class CacheFactory implements \Illuminate\Contracts\Cache\Factory {
	
	public function store($name = null) {
		return new \Core\Console\Scheduling\Cache(\Core\Facade\Cache::getDefault());
	}
	
}

