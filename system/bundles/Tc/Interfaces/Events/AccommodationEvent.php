<?php

namespace Tc\Interfaces\Events;

interface AccommodationEvent {

	public function getAccommodation(): \Ext_Thebing_Accommodation|\Ext_TA_School_Typicalaccommodation;

}