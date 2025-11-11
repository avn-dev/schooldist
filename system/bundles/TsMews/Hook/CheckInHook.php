<?php

namespace TsMews\Hook;

use TsMews\Api\Operations\StartReservation;
use TsMews\Entity\Allocation;
use TsMews\Service\Synchronization;

class CheckInHook extends AbstractMewsHook {

    public function run(\Ext_TS_Inquiry $inquiry) {

        if (!$this->hasApp()) {
            return;
        }

        Synchronization::confirmInquiryArrivalInMews($inquiry);

    }

}
