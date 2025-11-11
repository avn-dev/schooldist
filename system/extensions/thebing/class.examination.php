<?php

use \Core\Helper\DateTime;

/**
 * @property $id 
 * @property $changed 	
 * @property $created 	
 * @property $active
 * @property $creator_id 	
 * @property $user_id 	
 * @property $examination_term_id 	
 * @property $term_possible_date 	
 * @property $examination_template_id 	
 * @property $inquiry_course_id 	
 * @property $course_id 	
 * @property $program_service_id
 * @property $document_id
*/
class Ext_Thebing_Examination extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_examination';

	protected $_aSections = array();

	protected $_sPlaceholderClass = 'Ext_Thebing_Examination_Placeholder_Smarty';

	protected $_aFormat = array(
		'examination_template_id' => array(
			'required' => true
		),
		'inquiry_course_id'		=> array(
			'required'	=> true,
		),
	);

	protected $_aJoinedObjects = array(
		'versions' => array(
			'class' => 'Ext_Thebing_Examination_Version',
			'key' => 'examination_id',
			'type' => 'child',
			'on_delete' => 'cascade'
		)
	);

	protected $_aEntityErrors = array();

	/**
	 * @TODO Irgendwie eine latest_version_id einbauen, wie bei Dokumenten
	 *
	 * @return Ext_Thebing_Examination_Version
	 */
	public function getLastVersion() {

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_examination_version`
			WHERE
				`examination_id` = :examination_id AND 
				`active` = 1
			ORDER BY
				`created` DESC
			LIMIT 1
		";

		$iVersionId = (int)DB::getQueryOne($sSql, ['examination_id' => $this->id]);
		return Ext_Thebing_Examination_Version::getInstance($iVersionId);

	}

	/**
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument() {
		return Ext_Thebing_Inquiry_Document::getInstance($this->document_id);
	}

	public static function getTemplates($bPrepareForSelect = false, $iSchoolID = null)
	{
		$aBack = array();
		if(empty($iSchoolID))
		{
			$iSchoolID = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_examination_templates`
			WHERE
				`active` = 1
				AND `school_id` = :school_id
			ORDER BY
				`title`
		";

		$aTemplates = DB::getPreparedQueryData($sSql, array(
			'school_id' => $iSchoolID,
		));

		if($bPrepareForSelect)
		{
			foreach((array)$aTemplates as $aData)
			{
				$aBack[$aData['id']] = $aData['title'];
			}
		}
		else
		{
			$aBack = $aTemplates;
		}

		return $aBack;
	}

	public function  __set($sName, $mValue)
	{
		if( 'term_possible_date' == $sName )
		{
			if(is_numeric($mValue))
			{
				$mValue = date('Y-m-d', $mValue);
			}

			$this->_aData[$sName] = $mValue;
		} 
		elseif( 'inquiry_course_course'==$sName )
		{
			$aInfo = explode('_',$mValue);
			if(count($aInfo)>1)
			{
				$this->inquiry_course_id	= $aInfo[0];
				$this->course_id			= $aInfo[1];
				$this->program_service_id	= $aInfo[2];
			}
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}

	public function  __get($sName) {

		if('inquiry_course_course'==$sName) {

			if(
				empty($this->inquiry_course_id) ||
				empty($this->course_id) ||
				empty($this->program_service_id)
			) {
				return null;
			}

			return $this->inquiry_course_id.'_'.$this->course_id.'_'.$this->program_service_id;
		} else {
			return parent::__get($sName);
		}
	}

	public function save($bLog = true) {

		if(empty($this->_aData['term_possible_date'])) {
			$this->_aData['term_possible_date'] = $this->_aOriginalData['term_possible_date'];
		}

		parent::save($bLog);

		return $this;
	}

	public function getPlaceholderValue($sPlaceholder) {

		$oInquiry = $this->getDocument()->getInquiry();
		$oVersion = $this->getLastVersion();
		$oPlaceholder = new Ext_Thebing_Examination_Placeholder($oInquiry->id, 0, $oVersion->id);
		return $oPlaceholder->searchPlaceholderValue($sPlaceholder, 0);

	}

	/**
	 * Nächstes Prüfungsdatum ermitteln
	 *
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return DateTime|null
	 * @throws Exception
	 */
	public static function getNextExaminationDate(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, \TsTuition\Entity\Course\Program\Service $oProgramService) {

		$dToday = new DateTime();
		$dToday->setTime(0, 0, 0);
		$dCourseFrom = new DateTime($oJourneyCourse->from);
		$dCourseUntil = new DateTime($oJourneyCourse->until);

		$sSql = "
			SELECT
				`kext`.`id`
			FROM
				`ts_inquiries_journeys_courses` `ts_ijc` INNER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON 
				    `ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND 
				    `ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
				    `ts_tcps`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_ijc`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
					`ts_i`.`active` = 1 INNER JOIN
				`kolumbus_examination_templates_courses` `kextc` ON
					`kextc`.`course_id` = `ts_ijc`.`course_id` INNER JOIN
				`kolumbus_examination_templates` `kext` ON
					`kext`.`id` = `kextc`.`examination_template_id` AND
					`kext`.`active` = 1
			WHERE
				`ts_ijc`.`id` = :journey_course_id AND
				`ts_tcps`.`id` = :program_service_id AND
				`ts_ijc`.`active` = 1 AND
				`kext`.`active` = 1 AND
				`ts_ijc`.`until` >= CURDATE() AND
				`ts_i`.`confirmed` != '0000-00-00'
		";

		$aAllDates = [];
		$aResult = (array)DB::getQueryCol($sSql, ['journey_course_id' => $oJourneyCourse->getId(), 'program_service_id' => $oProgramService->getId()]);

		// Alle DateTime-Objekte sammeln
		foreach($aResult as $iTemplateId) {
			$oTemplate = Ext_Thebing_Examination_Templates::getInstance($iTemplateId);
			$aTemplateDates = $oTemplate->getExaminationDates($dCourseFrom, $dCourseUntil);
			$aAllDates = array_merge($aAllDates, $aTemplateDates);
		}

		usort($aAllDates, function($dDate1, $dDate2) {
			if($dDate1 == $dDate2) {
				return 0;
			}

			return $dDate1 > $dDate2 ? 1 : -1;
		});

		foreach($aAllDates as $dDate) {
			if($dDate >= $dToday) {
				return $dDate;
			}
		}

		return null;

	}

	/**
	 * Startdatum ermitteln für Prüfungsperiode (wenn Eintrag editiert wird, der aus Prüfungsvorlage erzeugt wurde)
	 *
	 * @param $sEndDate
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 * @return array|int|mixed
	 */
	public static function getLastExaminationDateForPeriod($sEndDate, Ext_TS_Inquiry_Journey_Course $oInquiryCourse) {

		$sSql = "
			SELECT
				`kexv`.`examination_date`
			FROM
				`kolumbus_examination` `kex` INNER JOIN
				`kolumbus_examination_version` `kexv` ON
					`kexv`.`examination_id` = `kex`.`id` AND
					`kexv`.`id` = (
						SELECT
							`id`
						FROM
							`kolumbus_examination_version`
						WHERE
							`examination_id` = `kex`.`id`
						ORDER BY
							`created` DESC
						LIMIT 1
					)
			WHERE
				`kex`.`inquiry_course_id` = :journey_course_id AND
				`kex`.`active` = 1 AND
				`kexv`.`examination_date` < :end_date
			ORDER BY
				`kexv`.`examination_date` DESC
			LIMIT 1
		";

		$sLastExaminationDate = DB::getQueryOne($sSql, [
			'journey_course_id' => $oInquiryCourse->id,
			'end_date' => $sEndDate
		]);

		return $sLastExaminationDate;

	}

	/**
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return Ext_Thebing_Examination
	 * @throws Exception
	 */
	public static function getExaminiationByDocumentId(Ext_Thebing_Inquiry_Document $oDocument) {

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_examination`
			WHERE
				`document_id` = :documentId
			ORDER BY
				`created` DESC
			LIMIT 1
		";

		$aSql = ['documentId' => $oDocument->id];

		$iId = (int)DB::getQueryOne($sSql, $aSql);

		return Ext_Thebing_Examination::getInstance($iId);

	}
	
}
