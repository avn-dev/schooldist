<?php

namespace TsMews\Hook;

use Core\Facade\Cache;
use TsMews\Entity\Allocation;
use TsMews\Entity\Customer;
use TsMews\Service\Synchronization;

class AllocationSaveHook extends AbstractMewsHook {

    public function run(\Ext_Thebing_Accommodation_Allocation $allocation) {

        if (!$this->hasApp()) {
            return;
        }

		$cacheKey = 'mews_'.get_class($allocation).'_'.$allocation->getId();

		// Cache setzen falls save() mehrfach aufgerufen wird
		if(Cache::exists($cacheKey)) {
			return false;
		}

		Cache::put($cacheKey, 60*5, 1);

		try {

			Synchronization::syncAllocationToMews($allocation);

		} catch(\Throwable $e) {

			Cache::forget($cacheKey);

			throw $e;
		}

		Cache::forget($cacheKey);

    }

}
