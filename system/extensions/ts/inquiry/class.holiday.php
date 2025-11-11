<?php

use Core\Exception\Entity\ValidationException;

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $inquiry_id
 * @property string $type (ENUM)
 * @property int $weeks
 * @property string $from (DATE)
 * @property string $until (DATE)
 */
class Ext_TS_Inquiry_Holiday extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_inquiries_holidays';

	protected $_aFormat = [
		// Funktioniert relational nicht
//		'inquiry_id' => [
//			'validate' => 'INT_POSITIVE',
//			'required' => true
//		],
		'weeks' => [
			'validate' => 'INT_POSITIVE',
			'required' => true
		],
		'from' => [
			'validate' => 'DATE',
			'required' => true,
		],
		'until' => [
			'validate' => 'DATE',
			'required' => true,
		]
	];

	protected $_aJoinedObjects = array(
		'splittings' => [
			'class' => 'Ext_TS_Inquiry_Holiday_Splitting',
			'key' => 'holiday_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		]
	);
	
	protected $_sPlaceholderClass = '\Ts\Service\Placeholder\Booking\Holiday';
	
	/**
	 * @return Ext_TS_Inquiry_Holiday_Splitting[]
	 */
	public function getSplittings() {
		return $this->getJoinedObjectChilds('splittings', true);
	}

	/**
	 * @param array $aOriginalData Muss separat übergeben werden, da $oOldService bereits verändert wurde
	 * @param Ext_TS_Inquiry_Journey_Service $oOldService
	 * @param Ext_TS_Inquiry_Journey_Service|null $oNewService
	 */
	public function addSplitting(array $aOriginalData, Ext_TS_Inquiry_Journey_Service $oOldService, Ext_TS_Inquiry_Journey_Service $oNewService = null) {

		/** @var Ext_TS_Inquiry_Holiday_Splitting $oSplitting */
		$oSplitting = $this->getJoinedObjectChild('splittings');

		if($oOldService instanceof Ext_TS_Inquiry_Journey_Course) {
			$oSplitting->setJoinedObject('old_course', $oOldService);
		} else if($oOldService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
			$oSplitting->setJoinedObject('old_accommodation', $oOldService);
		} else {
			throw new InvalidArgumentException('Unknown holiday service');
		}

		if($oNewService !== null) {
			if($oNewService instanceof Ext_TS_Inquiry_Journey_Course) {
				$oSplitting->setJoinedObject('new_course', $oNewService);
			} else if($oNewService instanceof Ext_TS_Inquiry_Journey_Accommodation) {
				$oSplitting->setJoinedObject('new_accommodation', $oNewService);
			} else {
				throw new InvalidArgumentException('Unknown holiday service');
			}
		}

		$oSplitting->original_weeks = $aOriginalData['weeks'];
		$oSplitting->original_from = $aOriginalData['from'];
		$oSplitting->original_until = $aOriginalData['until'];

	}

	/**
	 * Ferien löschen inkl. Wiederherstellung der Originalleistung
	 *
	 * @param bool $bRestoreOriginalService Originale Leistungsdaten wiederherstellen
	 * @throws ValidationException
	 */
	public function deleteHoliday($bRestoreOriginalService = false) {

		if(
			// Nur bei Schülerferien, weil bei Schulferien wieder erneut gesplittet würde (oder auch nicht)
			$bRestoreOriginalService &&
			$this->type === 'student'
		) {

			$aSplittings = $this->getSplittings();

			foreach($aSplittings as $oSplitting) {

				if (!$oSplitting->hasOriginalData()) {
					throw new InvalidArgumentException('Holiday splitting has no original data for restoring of original service!');
				}

				/** @var Ext_TS_Inquiry_Journey_Course|Ext_TS_Inquiry_Journey_Accommodation $oService */
				$oService = $oSplitting->getJoinedObject('old_'.$oSplitting->getType()); // old_course, old_accommodation
				$oService->weeks = $oSplitting->original_weeks;
				$oService->from = $oSplitting->original_from;
				$oService->until = $oSplitting->original_until;

				if (($aErrors = $oService->validate()) !== true) {
					throw (new ValidationException())->setAdditional(['errors' => $aErrors]);
				}

				$oService->save();

				$sSplitField = 'journey_split_'.$oSplitting->getType().'_id'; // journey_split_course_id, journey_split_accommodation_id
				if (!empty($oSplitting->$sSplitField)) {
					$oService = $oSplitting->getJoinedObject('new_'.$oSplitting->getType()); // new_course, new_accommodation

					if (($aErrors = $oService->delete()) !== true) {
						throw (new ValidationException())->setAdditional(['errors' => $aErrors]);
					}
				}

			}

		}

		$this->delete();

	}

	public function validate($bThrowExceptions = false) {
		$errors = parent::validate($bThrowExceptions);

		if ($errors === true){
			$errors = [];
		}

		if (!\Ext_Thebing_School::getSchoolFromSession()?->tuition_allow_allocation_with_attendances_modification) {
			$activeAttendences = Ext_Thebing_Tuition_Attendance::getActiveAttendances(
				[
					['inquiry_id', '=', $this->inquiry_id]
				],
				\Carbon\Carbon::parse($this->until),
				\Carbon\Carbon::parse($this->from)
			);
			/** @var Ext_Thebing_Tuition_Attendance $attendance */
			foreach ($activeAttendences as $attendance) {
				$errors['course_id['.$attendance?->getAllocation()?->getJourneyCourse()?->getId().']'][] = 'ATTENDANCES_EXIST';
			}
		}

		if (empty($errors)) {
			$errors = true;
		}

		return $errors;
	}

}
