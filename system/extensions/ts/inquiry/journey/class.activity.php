<?php

use TsRegistrationForm\Interfaces\RegistrationInquiryService;

/**
 * @property string $id
 * @property string $changed
 * @property string $created
 * @property string $active
 * @property string $creator_id
 * @property string $journey_id
 * @property string $activity_id
 * @property string $from
 * @property string $until
 * @property string $weeks
 * @property string $blocks
 * @property string $visible
 * @property string $comment
 */
class Ext_TS_Inquiry_Journey_Activity extends Ext_TS_Inquiry_Journey_Service implements Ext_TS_Service_Interface_Activity, RegistrationInquiryService {

	protected $_sTable = 'ts_inquiries_journeys_activities';

	protected $_sTableAlias = 'ts_ijact';

	protected $_sPlaceholderClass = 'Ext_TS_Inquiry_Journey_Activity_Placeholder';

	protected $_aFormat = [
		'from' => [
			'validate' => 'DATE',
			'required' => true
		],
		'until' => [
			'validate' => 'DATE'
		],
		'weeks' => [
			'validate' => 'INT_POSITIVE',
			'required' => true
		],
		'blocks' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
	];

	protected $_aJoinedObjects = [
		// Preisberechnung für Aktivitäten im Frontend, da der Spaß ohne IDs sonst wieder einmal nicht funktioniert
		'journey' => [
			'class' => Ext_TS_Inquiry_Journey::class,
			'type' => 'parent',
			'check_active' => true,
			'key' => 'activity_id'
		],
		'activity' => [
			'class' => 'TsActivities\Entity\Activity',
			'type' => 'parent',
			'check_active' => true,
			'key' => 'activity_id'
		],
		'allocations' => [
			'class' => 'TsActivities\Entity\Activity\BlockTraveller',
			'key' => 'journey_activity_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];

	protected $_aJoinTables = array(
		'activity_travellers' => [
			'table' => 'ts_inquiries_journeys_activities_to_travellers',
			'primary_key_field' => 'journey_activity_id',
			'foreign_key_field' => 'contact_id',
			'autoload' => false
		]
	);

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	protected $sInfoTemplateType = 'activity';

	/**
	 * @return string
	 */
	public function getKey() {
		return 'activity';
	}

	/**
	 * @return TsActivities\Entity\Activity
	 */
	public function getActivity() {
		return $this->getJoinedObject('activity');
	}

	/**
	 * @return string
	 */
	public function getNameForEditData() {

		$sFrom = Ext_Thebing_Format::LocalDate($this->from);
		$sUntil = Ext_Thebing_Format::LocalDate($this->until);

		$sInterfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		$sName = $this->getJoinedObject('activity')->getName($sInterfaceLanguage). ' ('.$sFrom.' - '.$sUntil.')';

		return $sName;
	}

	/**
	 * @deprecated 
	 * @param $sDisplayLanguage
	 * @return mixed
	 * @throws Exception
	 */
	public function getInfo($sDisplayLanguage) {

		$oActivity = $this->getActivity();
		$sName = $oActivity->getName($sDisplayLanguage);

		$oJourney = $this->getJourney();
		$oSchool = $oJourney->getSchool();
		$iSchoolId = $oSchool->id;

		$sTemplate = $oSchool->getPositionTemplate('activity');
		
		$sFrom = Ext_Thebing_Format::LocalDate($this->from, $iSchoolId);
		$sUntil = Ext_Thebing_Format::LocalDate($this->until, $iSchoolId);

		if($oActivity->billing_period === "payment_per_week") {
			$iUnits = $this->weeks;
			if($iUnits == 1) {
				$sUnit = 'Woche';
			} else {
				$sUnit = 'Wochen';
			}

		} else {
			$iUnits = $this->blocks;
			if($iUnits == 1) {
				$sUnit = 'Block';
			} else {
				$sUnit = 'Blöcke';
			}
		}

		$sWeeksUnits = $iUnits.' '.\Ext_TC_Placeholder_Abstract::translateFrontend($sUnit, $sDisplayLanguage);

		$aSearch = [
			'{$weeks_units}',
			'{$name}',
			'{$from}',
			'{$until}'
		];

		$aReplace = [
			$sWeeksUnits,
			$sName,
			$sFrom,
			$sUntil
		];

		$sReturn = str_replace($aSearch, $aReplace, $sTemplate);

		return $sReturn;
	}

	/**
	 * @param null $oActivity
	 * @param string $sMode
	 * @return bool|string
	 * @throws Exception
	 */
	public function checkForChange($oActivity = null, $sMode = 'complete') {

		if($this->id <= 0) {
			return 'new';
		}

		if($this->active == 0) {
			return 'delete';
		}

		if($oActivity == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oActivity->getData();
		}

		if($sMode === 'complete') {

			if(
				(int)$this->activity_id !== (int)$aOriginalData['activity_id'] ||
				(int)$this->blocks !== (int)$aOriginalData['blocks'] ||
				(int)$this->weeks !== (int)$aOriginalData['weeks'] ||
				$this->from != $aOriginalData['from'] ||
				$this->until != $aOriginalData['until'] ||
				$this->visible != $aOriginalData['visible']
			) {
				return 'edit';
			}

		} elseif($sMode === 'only_time') {

			if(
				(int)$this->blocks !== (int)$aOriginalData['blocks'] ||
				(int)$this->weeks !== (int)$aOriginalData['weeks'] ||
				$this->from != $aOriginalData['from'] ||
				$this->until != $aOriginalData['until']
			) {
				return 'edit';
			}

		}

		return false;
	}

	public function isEmpty() {

		if($this->activity_id <= 0) {
			return true;
		}

		return false;

	}

	public function validate($bThrowExceptions = false) {

		$mError = parent::validate($bThrowExceptions);

		if(empty($this->blocks)) {
			$oActivity = $this->getActivity();
			if($oActivity->billing_period === 'payment_per_block') {
				if(!is_array($mError)) {
					$mError = [];
				}
				$mError[$this->_sTableAlias.'.blocks'][] = L10N::t('Die Abrechnung der Aktivität erfolgt Blockweise. Bitte tragen Sie die Anzahl der Blöcke ein.');
			}
		}

		return $mError;

	}

	/**
	 * Funktioniert leider nicht mit casade, da der Saver kein delete() aufruft
	 */
	public function deleteAllocations() {

		/** @var TsActivities\Entity\Activity\BlockTraveller[] $aAllocations */
		$aAllocations = $this->getJoinedObjectChilds('allocations', false);
		foreach($aAllocations as $oAllocation) {
			$oAllocation->delete();
		}

	}

	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
		
		$oActivity = $this->getActivity();
		$sName = $oActivity->getName($oLanguage->getLanguage());

		$oJourney = $this->getJourney();
		$oSchool = $oJourney->getSchool();
		$iSchoolId = $oSchool->id;

		$sTemplate = $oSchool->getPositionTemplate('activity');
		
		$sFrom = Ext_Thebing_Format::LocalDate($this->from, $iSchoolId);
		$sUntil = Ext_Thebing_Format::LocalDate($this->until, $iSchoolId);

		if($oActivity->billing_period === "payment_per_week") {
			$iUnits = $this->weeks;
			if($iUnits == 1) {
				$sUnit = 'Woche';
			} else {
				$sUnit = 'Wochen';
			}

		} else {
			$iUnits = $this->blocks;
			if($iUnits == 1) {
				$sUnit = 'Block';
			} else {
				$sUnit = 'Blöcke';
			}
		}

		$sWeeksUnits = $iUnits.' '.$oLanguage->translate($sUnit);
		
		$oSmarty->assign('weeks_units', $sWeeksUnits);
		$oSmarty->assign('name', $sName);
		$oSmarty->assign('from', $sFrom);
		$oSmarty->assign('until', $sUntil);
		
	}

	public function getRegistrationFormData(): array {

		$dFrom = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->from);

		return [
			'activity' => !empty($this->activity_id) ? (int)$this->activity_id : null,
			'start' => $dFrom !== null ? 'date:'.$dFrom->toDateString() : null,
			'duration' => !empty($this->weeks) ? (int)$this->weeks : null,
			'units' => !empty($this->blocks) ? (int)$this->blocks : null,
			'additional' => null // \TsStudentApp\Pages\Activities::order()
		];

	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$this->addStudentListParts($aSqlParts);

		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();

		$aSqlParts['select'] .= ",
			`ts_act`.`short` `activity_short`,
			`ts_act_i18n`.`name` `activity_name`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`ts_activities` `ts_act` ON
				`ts_act`.`id` = `ts_ijact`.`activity_id` AND
				`ts_act`.`active` = 1 LEFT JOIN
			`ts_activities_i18n` `ts_act_i18n` ON
				`ts_act_i18n`.`activity_id` = `ts_act`.`id` AND
				`ts_act_i18n`.`language_iso` = '$sInterfaceLanguage'
		";

		$aSqlParts['where'] .= " AND
			`ts_ijact`.`visible` = 1 AND
			`ts_i`.`canceled` = 0
		";

		$aSqlParts['groupby'] = "
			`ts_ijact`.`id`
		";

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		if ($oSchool->id > 0) {
			$aSqlParts['where'] .= " AND `ts_ij`.`school_id` = $oSchool->id ";
		}

	}

}
