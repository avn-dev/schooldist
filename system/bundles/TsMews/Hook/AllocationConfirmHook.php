<?php

namespace TsMews\Hook;

use Core\DTO\DateRange;
use TsMews\Api\Operations\ConfirmReservation;
use TsMews\Api\Operations\StartReservation;
use TsMews\Entity\Allocation;
use TsMews\Handler\ExternalApp;
use TsMews\Service\Synchronization;

class AllocationConfirmHook extends AbstractMewsHook {

    public function run(\Ext_Thebing_Accommodation_Allocation $allocation) {

        if (!$this->hasApp()) {
            return;
        }

        Synchronization::confirmAllocationInMews($allocation);

    }

}
