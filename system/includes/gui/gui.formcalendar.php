<?php
/*
 * -- webDynamics GUI --
 * Mark Koopmann <m.koopmann@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 *
 */
 
class GUI_FormCalendar extends GUI_FormSimple {
	

	public function __construct(array $aConfig = array()) {
		$aConfig['css'] = 'txt date_input';
		$aConfig['template'] = 'gui.formcalendar.tpl';
		parent::__construct($aConfig);
	}

}
