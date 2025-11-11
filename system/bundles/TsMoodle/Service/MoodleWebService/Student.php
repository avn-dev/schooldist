<?php

namespace TsMoodle\Service\MoodleWebService;

use MoodleSDK\Api\Model\Course;
use MoodleSDK\Api\Model\User;

class Student extends \TsMoodle\Service\MoodleWebService {
	
	public function sync(\Ext_TS_Inquiry $inquiry, bool $syncAssignments=true) 
	{
	
		// Nur bestätigte Buchungen übertragen
		if($inquiry->isConfirmed() !== true) {
			return;
		}

		\System::wd()->executeHook('ts_moodle_student_sync', $this, $inquiry, $syncAssignments);
		
		$student = $inquiry->getFirstTraveller();

		// Einstellung, welche Schüler synchronisiert werden sollen
		$syncStudentMode = \System::d(\TsMoodle\Handler\ExternalApp::KEY_SYNC_STUDENT_MODE.'_'.$this->school->id, \TsMoodle\Handler\ExternalApp::KEY_SYNC_STUDENT_MODE_ALLOCATION);
		
		/*
		 * Nur Schüler mit Zuweisung zu Klassen übertragen
		 * Wenn Zuweisungen nicht übertragen werden sollen, dann immer übertragen
		 */
		$tuitionAllocationIds = $inquiry->getTuitionAllocationIds();
		if(
			$syncStudentMode == \TsMoodle\Handler\ExternalApp::KEY_SYNC_STUDENT_MODE_ALLOCATION &&
			empty($tuitionAllocationIds) &&
			$syncAssignments === true
		) {
			return;
		}
		
		// Keine Schüler ohne E-Mail übertragen
		if(empty($student->email)) {
			throw new \RuntimeException(sprintf('Student "%s" could not be transferred because no e-mail address is stored.', $student->getName()));
		}
		
		$moodleUser = $this->syncStudent($inquiry);

		if($syncAssignments === true) {
			$this->syncAssignments($tuitionAllocationIds);
		}

		return $moodleUser;
	}
	
	public function syncAssignments(array $tuitionAllocationIds) {
		
		$processedClassIds = [];
		foreach($tuitionAllocationIds as $tuitionAllocationId) {
			
			$assignment = \Ext_Thebing_School_Tuition_Allocation::getInstance($tuitionAllocationId);
			
			$class = $assignment->getBlock()->getClass();

			// Klasse nur einmal durchlaufen, nicht jeden Block
			if(isset($processedClassIds[$class->id])) {
				continue;
			}

			$journeyCourse = $assignment->getJourneyCourse();
			$course = $assignment->getCourse();

			$assignmentSync = new TuitionClassAssignment($this->school);
			$assignmentSync->sync($class, $journeyCourse, $course);
			
			$processedClassIds[$class->id] = true;
			
		}
		
	}
	
	public function syncStudent(\Ext_TS_Inquiry $inquiry) {

		$student = $inquiry->getFirstTraveller();
		$studentId = strtolower($student->getCustomerNumber());
		
		// Ist User schon da? Schüler-ID!
		$moodleUser = User::instance()
			->findOneByField($this->context, 'idnumber', $student->id);

		// Als zweites nach E-Mail suchen
		if($moodleUser === null) {
			$moodleUser = User::instance()
				->findOneByField($this->context, 'email', $student->email);
		}
		
		// Als drittes nach Username suchen
		if($moodleUser === null) {
			$moodleUser = User::instance()
				->findOneByField($this->context, 'username', $studentId);
		}
		
		$customFieldValues = $this->getCustomFields($inquiry);
		
		// Falls nicht, anlegen
		if($moodleUser === null) {

			$createUser = User::instance();
			$createUser->setIdnumber($student->id);
			$createUser->setUsername($studentId);
			
			$sDefaultPassword = \System::d(\TsMoodle\Handler\ExternalApp::KEY_DEFAULT_PASSWORD.'_'.$this->school->id);
			if(!empty($sDefaultPassword)) {
				$createUser->setPassword($sDefaultPassword);
			} else {
				$createUser->setCreatePassword(true);
			}

			if(!empty($customFieldValues)) {
				$createUser->setCustomFields($customFieldValues);
			}
			
			$createUser->setFirstName($student->firstname);
			$createUser->setLastName($student->lastname);
			$createUser->setEmail($student->email);
			$createUser->create($this->context);

			$moodleUser = User::instance()->findOneByField($this->context, 'username', $studentId);

		} else {

			// Daten immer aktualisieren
			$moodleUser->setIdnumber($student->id);
			$moodleUser->setUsername($studentId);
			$moodleUser->setFirstName($student->firstname);
			$moodleUser->setLastName($student->lastname);
			$moodleUser->setEmail($student->email);

			if(!empty($customFieldValues)) {
				$moodleUser->setCustomFields($customFieldValues);
			}

			$moodleUser->update($this->context);

		}
		
		return $moodleUser;
	}
	
	private function getCustomFields(\Ext_TS_Inquiry $inquiry) {
		
		$customFieldValues = [];
		
		$customFields = \System::d(\TsMoodle\Handler\ExternalApp::KEY_CUSTOM_FIELDS.'_'.$this->school->id, null);
		if(!empty($customFields)) {
			$fields = (array)explode(';', $customFields);
			foreach($fields as $mapping) {
				list($moodleField, $fideloField) = (array)explode('=', $mapping);

				$value = '';
				switch($fideloField) {
					case 'nationality':
						$value = $inquiry->getNationality();
						break;
					default:
						throw new \RuntimeException('No valid Fidelo field "'.$fideloField.'"');
						break;
				}
				$customField = new \MoodleSDK\Api\Model\CustomField();
				$customField->setType($moodleField);
				$customField->setValue($value);
				$customFieldValues[] = $customField;
				
			}
		}
		
		return $customFieldValues;
	}
	
}
