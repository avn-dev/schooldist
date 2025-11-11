<?php

namespace TsTuition\Model\Report\Fields;

use \TsTuition\Model\Report\Field;

class Attendance extends Field {
	
	public function hasSelectField(): bool {
		return true;
	}
	
	public function getSelectField(array $column, string $setting=null) {
		
		$this->selectField = "
			GROUP_CONCAT(
				DISTINCT 
				`ktbic`.`id`, 
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 1
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.mo, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 2
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.di, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 3
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.mi, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 4
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.do, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 5
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.fr, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 6
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.sa, 0),
				'{y}', 
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id` AND
							`ktbd`.`day` = 7
					) * `ktt`.`lessons` * `ktcl`.`lesson_duration`
				),
				'|', 
				IFNULL(kta.so, 0)
				
				SEPARATOR '{x}'
			)";
		
		return $this->selectField;
	}
	
	public function hasPrepareField(): bool {
		return true;
	}
	
	public function getPrepareField(array $value, array $column) {
		
		$students = explode('{x}', reset($value));
		$present = $partlyAbsent = $absent = 0;
		
		if($column['setting'] == -1) {

			$days = [];
			for($i=1;$i<=7;$i++) {

				$totalCurrentDay = 0;
				$absentCurrentDay = 0;
				foreach($students as $student) {
					$studentData = explode('{y}', $student);
					$dayData = explode('|', $studentData[$i]);
					$totalCurrentDay += $dayData[0];
					$absentCurrentDay += $dayData[1];
				}

				if(
					$totalCurrentDay == 0 && 
					$absentCurrentDay == 0
				) {
					continue;
				}
				
				if($absentCurrentDay == 0) {
					$present++;
				}
				
				if(
					$totalCurrentDay == $absentCurrentDay &&
					$absentCurrentDay > 0
				) {
					$absent++;
				}
				
				if(
					$totalCurrentDay > $absentCurrentDay &&
					$absentCurrentDay > 0
				) {
					$partlyAbsent++;
				}

			}

		} else {
		
			foreach($students as $student) {

				$studentData = explode('{y}', $student);
				$studentData['total'] = 0;
				$studentData['absent'] = 0;


				if(!empty($column['setting'])) {

					$dayData = explode('|', $studentData[$column['setting']]);
					$studentData['total'] += $dayData[0];
					$studentData['absent'] += $dayData[1];

				} else {

					for($i=1;$i<=7;$i++) {
						$dayData = explode('|', $studentData[$i]);
						$studentData['total'] += $dayData[0];
						$studentData['absent'] += $dayData[1];
					}

				}

				if(
					$studentData['absent'] == 0 &&
					$studentData['total'] == 0
				) {
					continue;
				}
				
				if($studentData['absent'] == 0) {
					$present++;
				}
				if(
					$studentData['total'] == $studentData['absent'] &&
					$studentData['absent'] > 0
				) {
					$absent++;
				}
				if(
					$studentData['total'] > $studentData['absent'] &&
					$studentData['absent'] > 0
				) {
					$partlyAbsent++;
				}
			}
			
		}
		
		if($this->fieldId == 68) { // Vollständig anwesend

			return $present;
			
		} elseif($this->fieldId == 69) { // Abwesend
			
			return $absent;
				
		} elseif($this->fieldId == 70) { // Teilweise anwesend

			return $partlyAbsent;
			
		}
		
	}
	
	public function hasSettings(): bool {
		return true;
	}
	
	public function getSettings() {
		
		$this->settings = [
			'' => \L10N::t('Alle Tage', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'-1' => \L10N::t('Pro Tag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'1' => \L10N::t('Montag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'2' => \L10N::t('Dienstag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'3' => \L10N::t('Mittwoch', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'4' => \L10N::t('Donnerstag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'5' => \L10N::t('Freitag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'6' => \L10N::t('Samstag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
			'7' => \L10N::t('Sonntag', \Ext_Thebing_Tuition_Report_Gui2::$_sDescription),
		];
		
		return $this->settings;
	}

}
