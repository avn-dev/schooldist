<?php
	
class Ext_Thebing_Gui2_Format_Position_Placeholder extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'flex flex-row gap-2 items-center';
		
		$oSelect = new Ext_Gui2_Html_Select();
		$oSelect->class			= 'position_placeholders form-control txt input-sm';
		$oSelect->style			= 'width: 170px;';

		// Leerer Eintrag
		$oOption = new Ext_Gui2_Html_Option();
		$oOption->value = '';
		$oOption->setElement('--- ' . L10N::t('Platzhalter') . ' ---');
		$oSelect->setElement($oOption);

		switch($aResultData['position_key']){
			case 'transfer':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'transfer';
				$oOption->setElement('{$transfer}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'arrival_date';
				$oOption->setElement('{$arrival_date}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'departure_date';
				$oOption->setElement('{$departure_date}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'from';
				$oOption->setElement('{$arrival_pick_up}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'to';
				$oOption->setElement('{$arrival_drop_off}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'weekday';
				$oOption->setElement('{$weekday}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'date';
				$oOption->setElement('{$date}');
				$oSelect->setElement($oOption);
				
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'time';
				$oOption->setElement('{$time}');
				$oSelect->setElement($oOption);
				break;
			case 'insurance':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'insurance';
				$oOption->setElement('{$insurance}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'from';
				$oOption->setElement('{$from}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'until';
				$oOption->setElement('{$until}');
				$oSelect->setElement($oOption);

//				$oOption = new Ext_Gui2_Html_Option();
//				$oOption->value = 'insurance_weeks';
//				$oOption->setElement('{$insurance_weeks}');
//				$oSelect->setElement($oOption);
				break;
			case 'accommodation':
//				$oOption = new Ext_Gui2_Html_Option();
//				$oOption->value = 'accommodation';
//				$oOption->setElement('{$accommodation}');
//				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'nickname';
				$oOption->setElement('{$nickname}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'from';
				$oOption->setElement('{$from}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'until';
				$oOption->setElement('{$until}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'weeks';
				$oOption->setElement('{$weeks}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'category';
				$oOption->setElement('{$category}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'date_accommodation_start';
				$oOption->setElement('{$date_accommodation_start}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'date_accommodation_end';
				$oOption->setElement('{$date_accommodation_end}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'roomtype';
				$oOption->setElement('{$roomtype}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'roomtype_full';
				$oOption->setElement('{$roomtype_full}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'meal';
				$oOption->setElement('{$meal}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'meal_full';
				$oOption->setElement('{$meal_full}');
				$oSelect->setElement($oOption);

				break;
			case 'additional_course':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'name';
				$oOption->setElement('{$name}');
				$oSelect->setElement($oOption);
				
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'description';
				$oOption->setElement('{$description}');
				$oSelect->setElement($oOption);
				break;
			case 'additional_accommodation':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'name';
				$oOption->setElement('{$name}');
				$oSelect->setElement($oOption);
				
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'description';
				$oOption->setElement('{$description}');
				$oSelect->setElement($oOption);
				break;
			case 'additional_general':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'name';
				$oOption->setElement('{$name}');
				$oSelect->setElement($oOption);
				break;
			case 'extra_week':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'weeks';
				$oOption->setElement('{$weeks}');
				$oSelect->setElement($oOption);
				break;
			case 'extra_night':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'nights';
				$oOption->setElement('{$nights}');
				$oSelect->setElement($oOption);
				break;
			case 'course':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'course';
				$oOption->setElement('{$course}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'name_frontend';
				$oOption->setElement('{name_frontend}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'nickname';
				$oOption->setElement('{$nickname}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'course_language';
				$oOption->setElement('{$course_language}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'weeks_units';
				$oOption->setElement('{$weeks_units}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'category';
				$oOption->setElement('{$category}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'from';
				$oOption->setElement('{$from}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'until';
				$oOption->setElement('{$until}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'first_assignment_time';
				$oOption->setElement('{$first_assignment_time}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'first_assignment_weekday';
				$oOption->setElement('{$first_assignment_weekday}');
				$oSelect->setElement($oOption);
				break;
			case 'activity':
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'weeks_units';
				$oOption->setElement('{$weeks_units}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'from';
				$oOption->setElement('{$from}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'until';
				$oOption->setElement('{$until}');
				$oSelect->setElement($oOption);

				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = 'name';
				$oOption->setElement('{$name}');
				$oSelect->setElement($oOption);

		}

		$oDiv->setElement('<i class="fas fa-arrow-left"></i>');

		$oDiv->setElement($oSelect);

		return $oDiv->generateHTML();

	}

}