<?php

namespace TsMews\Hook;

use TsMews\Service\Synchronization;

class InquirySaveHook extends AbstractMewsHook {

    /**
     * Buchung mit Mews abgleichen
     *
     * @param \Ext_TS_Inquiry $inquiry
     */
    public function run(\Ext_TS_Inquiry $inquiry) {

        if (!$this->hasApp()) {
            return;
        }

        $traveller = $inquiry->getFirstTraveller();

        Synchronization::syncCustomerToMews($traveller);
    }

}
