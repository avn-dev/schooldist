<?php

namespace TsAccommodation\Service\RoomCleaning\CleanPlan;

use DateTime;
use TsAccommodation\Entity\Cleaning\Type;

class Date {

    public $date;

    public $type;

    public $cycle;

    public $allocation;

    public function __construct(DateTime $date, Type $type, Type\Cycle $cycle, \Ext_Thebing_Accommodation_Allocation $allocation = null) {
        $this->date = $date;
        $this->type = $type;
        $this->cycle = $cycle;
        $this->allocation = $allocation;
    }

}
