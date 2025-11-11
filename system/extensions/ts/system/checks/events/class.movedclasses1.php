<?php

class Ext_TS_System_Checks_Events_MovedClasses1 extends \Ext_TC_System_Checks_Events_AbstractMovedClasses
{
	protected $events = [
		// old => new
		'Core\\Events\\SystemUpdate' => 'Core\\Events\\NewSystemUpdates',
		'Ts\\Events\\Inquiry\\PlacementtestResult' => 'TsFrontend\\Events\\PlacementtestResult',
	];

	protected $listeners = [
		// old => new
		'Ts\\Listeners\\Inquiry\\SendSchoolEmail' => 'Ts\\Listeners\\SendSchoolNotification',
		'Ts\\Listeners\\Inquiry\\SendIndividualEmail' => 'Ts\\Listeners\\SendIndividualEmail',
		'Ts\\Listeners\\Inquiry\\SendSalesPersonEmail' => 'Ts\\Listeners\\Inquiry\\SendSalesPersonNotification',
		'Ts\\Listeners\\Inquiry\\SendAgencyEmail' => 'Ts\\Listeners\\Inquiry\\SendAgencyNotification',
		'Ts\\Listeners\\Inquiry\\SendGroupContactEmail' => 'Ts\\Listeners\\Inquiry\\SendGroupContactNotification',
		'TsStudentApp\\Listeners\\RedirectAppMessageViaEmail' => 'TsStudentApp\\Listeners\\RedirectAppMessageToReceiver',
	];
}