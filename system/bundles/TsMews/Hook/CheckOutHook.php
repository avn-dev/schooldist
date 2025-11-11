<?php

namespace TsMews\Hook;

use TsMews\Api\Operations\ProcessReservation;
use TsMews\Entity\Allocation;
use TsMews\Service\Synchronization;

class CheckOutHook extends AbstractMewsHook {

    public function run(\Ext_TS_Inquiry $inquiry) {

        if (!$this->hasApp()) {
            return;
        }

        Synchronization::confirmInquiryDepartureInMews($inquiry);

    }

}
