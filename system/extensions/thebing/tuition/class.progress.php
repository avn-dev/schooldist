<?php


class Ext_Thebing_Tuition_Progress extends Ext_Thebing_Basic{

	// Tabellenname
	protected $_sTable = 'kolumbus_tuition_progress';
	protected $_sTableAlias = 'ktp';

	protected $_aFormat = array(
			'inquiry_course_id' => array(
				'required' => true,
				'validate' => 'INT_POSITIVE',
			),
			'week' => array(
				'required' => true,
				'validate' => 'DATE',
			),
			'level' => array(
				'required' => true,
				'validate' => 'INT_NOTNEGATIVE',
			),
	);
    
	public static function findByInquiryAndLevelGroupAndWeekAndCourse(Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, Ext_Thebing_Tuition_LevelGroup $oLevelGroup, $sWeek, $bCheckActive = false)
	{
		
		$oInquiry = $oInquiryCourse->getInquiry();
		
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`week` = :week AND
				`courselanguage_id` = :courselanguage_id AND
				`inquiry_course_id` = :inquiry_course_id AND 
				`program_service_id` = :program_service_id				
		";
		
		$aSql = array();

		if($bCheckActive)
		{
			$sSql .= ' AND `active` = 1';
		}

		$aSql['inquiry_id']	= (int) $oInquiry->id;		
		$aSql['courselanguage_id'] = (int) $oLevelGroup->id;
		$aSql['inquiry_course_id'] = (int) $oInquiryCourse->id;
		$aSql['program_service_id'] = (int) $oProgramService->id;
		$aSql['week'] = $sWeek;

		$iDataId = (int) DB::getQueryOne($sSql,$aSql);

		return self::getInstance($iDataId);
	}

	public static function getStartLevel($iInquiryCourseId, $iProgramServiceId)
	{
		return self::getProgress($iInquiryCourseId, 'start', "name_short", false, false, $iProgramServiceId);
	}

	public static function getCurrentLevel($iInquiryCourseId, $iProgramServiceId)
	{
		return self::getProgress($iInquiryCourseId, 'current', "name_short", false, false, $iProgramServiceId);
	}

	public static function getCurrentLevelCount(Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $returnProgressId=false)
	{	
		$iCount = 0;
		$progressId = null;
		$mCurrentLevel		= (int)self::getProgress($oInquiryCourse->getId(), 'current', "id", false);

		$oCourse			= $oInquiryCourse->getCourse();
		$oLevelGroup		= $oInquiryCourse->getCourseLanguage();

		$aWeek				= Ext_Thebing_Util::getWeekTimestamps();
		$iWeek				= $aWeek['start'];

		$aWeekCourse		= Ext_Thebing_Util::getWeekTimestamps($oInquiryCourse->from);
		$iCourseFrom		= (int)$aWeekCourse['start'];

		$oWdDate			= new WDDate();
		$oWdDate->set($iCourseFrom, WDDate::TIMESTAMP);

		$iWeeks = (int)$oInquiryCourse->weeks;

		for($iCounter=0; $iCounter < $iWeeks; $iCounter++)
		{
			$dWeek		= $oWdDate->get(WDDate::DB_DATE);
			$iCompare = $oWdDate->compare($iWeek, WDDate::TIMESTAMP);

			if($iCompare <= 0)
			{

				$oProgress	= self::findByInquiryAndLevelGroupAndWeekAndCourse($oInquiryCourse, $oProgramService, $oLevelGroup, $dWeek, true);

				// Wenn Level für diese Woche nicht gesetzt, bedeutet das, dass es sich nicht verändert hat.
				if(!$oProgress->exist()) {
					$iCount++;
				} else {

					$iLevelWeek = (int)$oProgress->level;

					if($iLevelWeek == $mCurrentLevel) {
						if($progressId === null) {
							$progressId = $oProgress->id;
						}
						$iCount++;
					// Abweichendes Level (nicht gar kein Level), dann Wert zurücksetzen.
					} else {
						$progressId = null;
						$iCount = 0;
					}
				
				}

			}

			$oWdDate->add(1,WDDate::WEEK);
		}

		if($returnProgressId) {
			return $progressId;
		}
		
		return $iCount;
	}

	public static function getProgress($iInquiryCourseId, $sProgressType, $sColumn = "name_short", $dWeek = false, $iCourselanguageId = false, $iProgramServiceId = false)
	{
		$aSql = array(
			'inquiry_course_id' => (int)$iInquiryCourseId,
			'type' => $sProgressType
		);

		$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
		$oInquiry = $oJourneyCourse->getInquiry();
		$oProgramService = ($iProgramServiceId !== false)
			? \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId)
			: null;

		$sWeekSortDirection = 'DESC';
		$sPlaceholderWeek = '<= :week';

		if ($dWeek) {
			// do nothing
		} else if($sProgressType == 'start') {

			if (!$oProgramService) {
				throw new \InvalidArgumentException('Missing program service id for start level retrieving');
			}

			$dWeek = $oJourneyCourse->getProgramServiceFrom($oProgramService)?->format('Y-m-d');

			$sWeekSortDirection = 'ASC';
			$sPlaceholderWeek = '>= :week';
		} else {
			$aWeek			= Ext_Thebing_Util::getWeekTimestamps();
			$oDate			= new WDDate($aWeek['start'], WDDate::TIMESTAMP);
			$dWeek			= $oDate->get(WDDate::DB_DATE);
		}

		$sSubPart = self::getSqlSubPart($sPlaceholderWeek, $sWeekSortDirection);

		$aSql['week'] = $dWeek;
		$aSql['inquiry_id'] = (int)$oInquiry->id;

		if (!$iCourselanguageId) {
			if ($oProgramService) {
				$oCourse = $oProgramService->getService();
			} else {
				$oCourse = $oJourneyCourse->getCourse();
			}
			$oLevelGroup = $oCourse->getLevelgroup();
			$iCourselanguageId = $oLevelGroup->id;
		}

		$aSql['courselanguage_id']	= (int)$iCourselanguageId;
		$aSql['name_column']	= $sColumn;

		$sSql = "
			SELECT
				`ktul`.#name_column
			FROM
				`kolumbus_tuition_progress` `ktp` INNER JOIN
				`ts_tuition_levels` `ktul` ON
					`ktul`.`id` = `ktp`.`level` AND
					`ktul`.`active` = 1 LEFT JOIN
				`ts_tuition_courselanguages` `ktlg` ON
					`ktlg`.`id` = `ktp`.`courselanguage_id` AND
					`ktlg`.`active` = 1 INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ktp`.`inquiry_id` AND
					`ki`.`active` = 1
			WHERE
				`ktp`.`inquiry_id` = :inquiry_id AND
				`ktp`.`courselanguage_id` = :courselanguage_id AND
				`ktp`.`active` = 1 AND
				`ktp`.`week` = (
					".$sSubPart."
				)
		";

		$sLevel = DB::getQueryOne($sSql, $aSql);

		// Kein Level vorhanden, Placementtest überprüfen
		if(empty($sLevel))
		{
			// Wenn Fortschritt Woche angegeben und diese nicht zwischen dem Kurszeitraum ist, 
			// dann gib auch im Placementtest nichts zurück
			if($sProgressType == 'current')
			{
				if(WDDate::isDate($dWeek, WDDate::DB_DATE))
				{
					$oWeek = new WDDate($dWeek, WDDate::DB_DATE);

					if(!$oWeek->isBetween(WDDate::DB_DATE, $oJourneyCourse->from, $oJourneyCourse->until))
					{
						return false;
					}
				}
			}

			$levelId = Ext_Thebing_Placementtests_Results::getLevelForInquiryAndLanguage($oInquiry->id, $iCourselanguageId);

			$sLevel = Ext_Thebing_Tuition_Level::getInstance($levelId)->name_short;

		}

		return $sLevel;
	}

	/**
	 * Sql Subselect für das Niveau auslagern, das wäre bei der Attendance auch ganz sinvoll
	 * @param <mixed> $mPlaceHolderWeek
	 * @param <string> $sDir
	 * @param <string> $sAliasInquiriesCourses
	 * @return string
	 */
	public static function getSqlSubPart($mPlaceHolderWeek, $sDir = 'DESC')
	{
		$sWeekPart = "";
		
		if(is_string($mPlaceHolderWeek))
		{
			$sWeekPart .= " `sub_ktp`.`week` ".$mPlaceHolderWeek." AND ";
		}
		
		if(
			$sDir == 'DESC'
		){
			$sSelect = "
				MAX(`sub_ktp`.`week`)
			";
		}else{
			$sSelect = "
				MIN(`sub_ktp`.`week`)
			";
		}
		
		$sSql = "
			SELECT
				".$sSelect."
			FROM
				`kolumbus_tuition_progress` `sub_ktp`
			WHERE
				`sub_ktp`.`courselanguage_id` = `ktlg`.`id` AND
				`sub_ktp`.`inquiry_id` = `ki`.`id` AND
				".$sWeekPart."
				`sub_ktp`.`active` = 1
		";

		return $sSql;
	}
	
	/**
	 * Anhand eines Arrays an Fortschritten & eines Enddatums den letzten Fortschritt errechnen
	 * 
	 * @param string $sUntil
	 * @param array $aProgressList
	 * @return int 
	 */
	public static function calculateProgress($sUntil, array $aProgressList)
	{
		if(WDDate::isDate($sUntil, WDDate::DB_DATE))
		{
			$iProgressStart		= false;
			
			if(isset($aProgressList['start']))
			{
				$iProgressStart = $aProgressList['start'];
				
				unset($aProgressList['start']);
			}
			
			$iProgress	= false;
			$oDate		= new WDDate($sUntil, WDDate::DB_DATE);
			
			foreach($aProgressList as $sDate => $iLevel)
			{				
				if($oDate->compare($sDate, WDDate::DB_DATE) > -1)
				{
					$iProgress = $iLevel;
					
					break;
				}
			}
			
			if(!$iProgress && $iProgressStart)
			{
				$iProgress = $iProgressStart;
			}
			
			return $iProgress;
		}
		else
		{
			throw new Exception('You have to set the until date!');
		}
	}
	
	public function save($bLog = true) {
		
		parent::save($bLog);
		
		// Letzte Leveländerung speichern
		$inquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($this->inquiry_course_id);
		$programService = \TsTuition\Entity\Course\Program\Service::getInstance($this->program_service_id);

		$progressId = self::getCurrentLevelCount($inquiryCourse, $programService, true);

		if(!empty($progressId)) {
			$inquiryCourse->disableUpdateOfCurrentTimestamp();
			$inquiryCourse->disableUpdateOfEditor();
			$inquiryCourse->index_latest_level_change_progress_id = $progressId;
			$inquiryCourse->save();
		}
		
		return $this;
	}
	
}
