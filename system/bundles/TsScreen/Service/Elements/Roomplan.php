<?php

namespace TsScreen\Service\Elements;

use Carbon\Carbon;

class Roomplan extends AbstractElement {
	
	public function prepare() {
		
		// Name, Klasse, Uhrzeit, Raum (Etage)
		$school = \Ext_Thebing_School::getInstance($this->schedule->school_id);

		// Im Frontend gibt es keinen Schulkontext für die Zeitzone
		$timezone = $school->getTimezone();

		// Entweder muss alles mit oder ohne Zeitzone sein, aber SQL-TIME darf nicht mit TZ-abhängigen Werten/Funktionen verglichen werden
		$now = Carbon::now($timezone);

		// Aktuelle oder nächste Klassen am aktuellen Tag in der Schule (dem Gebäude)
		// Query mit Datumsparametern cachebar machen
		$sqlParameters = [
			'school_id' => $school->id,
			'today' => $now->toDateString(),
			'start' => $now->toTimeString(),
			'end' => $now->copy()->addHours(2)->toTimeString()
		];

		$sqlQuery = "
			SELECT
				`tc_c`.`firstname`,
				`tc_c`.`lastname`,
				`ktcl`.`name` `class`,
				`ktt`.`from`,
				`kcr`.`name` `room`,
				`ksf`.`title` `floor`
			FROM 
				`kolumbus_tuition_classes` ktcl JOIN
				`kolumbus_tuition_blocks` ktb ON
					ktcl.id = ktb.class_id AND
					ktb.active = 1 JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1 JOIN
				`kolumbus_tuition_blocks_days` ktbd ON
					ktb.id = ktbd.block_id LEFT JOIN
				`kolumbus_tuition_blocks_inquiries_courses` ktbic ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktbic`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON 
					`ktbtr`.`block_id` = `ktb`.`id` LEFT JOIN
				`kolumbus_classroom` `kcr` ON
			 	    `kcr`.`id` = `ktbtr`.`room_id` AND
			 	    `kcr`.`active` = 1 LEFT JOIN
				`kolumbus_school_floors` `ksf` ON
					`kcr`.`floor_id` = `ksf`.`id` JOIN
				ts_inquiries_journeys_courses ts_ijc ON
					ktbic.inquiry_course_id = ts_ijc.id JOIN
				ts_inquiries_journeys ts_ij ON
					ts_ijc.journey_id = ts_ij.id JOIN
				ts_inquiries ts_i ON
					ts_ij.inquiry_id = ts_i.id JOIN
				ts_inquiries_to_contacts ts_itc ON
					ts_i.id = ts_itc.inquiry_id AND
					ts_itc.type = 'traveller' JOIN
				tc_contacts tc_c ON
					ts_itc.contact_id = tc_c.id
			WHERE
				ktcl.`active` = 1 AND
				ktcl.`school_id` = :school_id AND
				getRealDateFromTuitionWeek(
					`ktb`.`week`,
					`ktbd`.`day`,
					1
				) = :today AND
			    ktt.until >= :start AND
			    /*ktt.from <= :end AND*/
				`kcr`.`online` = 0
			";
		
		if(!empty($this->schedule->buildings)) {
			$sqlQuery .= "
				AND `ksf`.`building_id` IN (:buildings)
					";
			$sqlParameters['buildings'] = $this->schedule->buildings;
		}

		$sqlQuery .= "
			GROUP BY
				`ktbic`.`id`
			ORDER BY
				`ktt`.`from`,
				`tc_c`.`lastname`,
				`tc_c`.`firstname`				
			";
		
		$students = (array)\DB::getQueryRows($sqlQuery, $sqlParameters);

		if(empty($students)) {
			$this->assign('error', $this->translator->translate('Keine Informationen vorhanden'));		
		} else {
			$this->assign('students', $students);			
		}
		
		$this->assign('autoplay_speed', (int)$this->schedule->autoplay_speed);
		
		$this->assign('translations', [
			'name' => $this->translator->translate('Name'),
			'class' => $this->translator->translate('Class'),
			'time' => $this->translator->translate('Time'),
			'room' => $this->translator->translate('Room')
			]
		);
		
	}

}
