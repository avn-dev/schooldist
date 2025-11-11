<?php

class Ext_TS_System_Checks_Events_UpdateAccommodationProviderNotificationNamespace extends Ext_TC_System_Checks_Events_AbstractMovedClasses
{
	protected $listeners = [
		// old => new
		'Ts\\Listeners\\SendAccommodationProviderNotification' => 'Tc\\Listeners\\SendAccommodationProviderNotification'
	];
}

