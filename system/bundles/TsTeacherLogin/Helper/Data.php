<?php

namespace TsTeacherLogin\Helper;

use Core\Handler\CookieHandler;
use TsTeacherLogin\Proxy\JourneyCourseProxy;
use TsTuition\Controller\Scheduling\PageController;
use Core\Helper\DateTime;

/**
 * Class Data
 * @package TsTeacherLogin\Helper
 */
class Data {

	/**
	 * @param int $iBlockId
	 * @return JourneyCourseProxy[]
	 */
	static public function getBlockStudents($iBlockId) {

		global $_VARS;

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		if(!$oBlock->exist()) {
			return [];
		}

		$iSchoolId = $oBlock->school_id;

		/*
		 * @todo Nicht mehr nutzen, da hier viel zu viel passiert und diese Abfrage zu langsam ist 
		 */
		$sAllocatedHash	= md5('thebing_tuition_blocks_students_allocated');
		$oGuiAllocatedStudents = PageController::getStudentGui2('allocated', $sAllocatedHash, $iSchoolId, true);
		$oGuiAllocatedStudents->column_flexibility = false;

		$_VARS['block_id'] = $oBlock->id;

		$dWeek = new DateTime($oBlock->week);
		$aFilter['week'] = $dWeek->getTimestamp();

		$aData = $oGuiAllocatedStudents->getTableData($aFilter, [], [], 'list', true);

		$aStudents = $aData['body'];

		$aStudentsArray = [];

		foreach($aStudents as $aStudent) {

			$aDataArray = [];

			foreach($aStudent['items'] as $aItems) {
				$sKey = \Ext_Gui2_Data::setFieldIdentifier($aItems['db_column'], $aItems['db_alias']);
				$aDataArray[$sKey] = $aItems;
			}

			$aDecodedId = $oGuiAllocatedStudents->decodeId($aStudent['id']);

			$oInquiryJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($aDecodedId['inquiry_course_id']);

			$oProxy = new JourneyCourseProxy($oInquiryJourneyCourse);
			$oProxy->setBlock($oBlock);
			$oProxy->setData($aDataArray);
			$oProxy->setAllocationId($aDecodedId['allocation_id']);

			$aStudentsArray[] = $oProxy;

		}

		return $aStudentsArray;
	}

	/**
	 * @param int $iBlockId
	 * @return JourneyCourseProxy[]
	 */
	static public function getPotentialStudents(\Ext_Thebing_School_Tuition_Block $block) {

		global $_VARS;

		if(!$block->exist()) {
			return [];
		}

		$class = $block->getClass();
		$courses = $class->courses;
		
		/*
		 * @todo Für mehrere Kurse
		 */
		#$firstCourseId = reset($courses);

		$iSchoolId = $block->school_id;

		$unallocatedHash = md5('thebing_tuition_blocks_students_unallocated');
		$oGuiUnallocatedStudents = PageController::getStudentGui2('unallocated', $unallocatedHash, $iSchoolId, true);
		$oGuiUnallocatedStudents->column_flexibility = false;

		#$_VARS['block_id'] = $block->id;

		$dWeek = new DateTime($block->week);
		$aFilter['week'] = $dWeek->getTimestamp();
		$aFilter['courses'] = $courses;

		$aData = $oGuiUnallocatedStudents->getTableData($aFilter, [], [], 'list', true);

		$aStudents = $aData['body'];

		$aStudentsArray = [];

		foreach($aStudents as $aStudent) {

			$aDataArray = [];

			foreach($aStudent['items'] as $aItems) {
				$sKey = \Ext_Gui2_Data::setFieldIdentifier($aItems['db_column'], $aItems['db_alias']);
				$aDataArray[$sKey] = $aItems;
			}

			$aDecodedId = $oGuiUnallocatedStudents->decodeId($aStudent['id']);

			$aDataArray['inquiry_course_id'] = [
				'text' => $aDecodedId['inquiry_course_id']
			];
			$aDataArray['program_service_id'] = [
				'text' => $aDecodedId['program_service_id']
			];
			
			$oInquiryJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($aDecodedId['inquiry_course_id']);
			$inquiry = $oInquiryJourneyCourse->getInquiry();
			
			$oProxy = new JourneyCourseProxy($oInquiryJourneyCourse);
			$oProxy->setBlock($block);
			$oProxy->setData($aDataArray);

			$aStudentsArray[$oInquiryJourneyCourse->id] = $oProxy;

		}

		return $aStudentsArray;
	}

	static public function getDescriptionHistory(\Ext_Thebing_School_Tuition_Block $oBlock, \DateTime $dUntil) {

		$dWeekUntil = new \DateTime($oBlock->week);
		$dWeekUntil->modify('+5 weeks');

		$oClass = $oBlock->getClass();
		$oSchool = $oBlock->getSchool();

		$sSql = "
			SELECT
				`ktb`.`id`,
				`ktb`.`week` `week_from`,
			    `ktb`.`description`,
			   	GROUP_CONCAT(`ktbd`.`day`) `block_days`,
			   	getRealDateFromTuitionWeek(
					`ktb`.`week`,
					`ktbd`.`day`,
					:course_startday
				) `block_date`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id`
			WHERE
			    `active` = 1 AND  
				`class_id` = :class_id AND
			    `week` <= :block_week AND
			    `description` != ''
			GROUP BY
				`ktb`.`id`
			ORDER BY
				`week` DESC
			LIMIT
			    20
		";

		$aSql = [
			'class_id' => $oClass->id,
			'block_week' => $oBlock->week,
			'course_startday' => $oSchool->course_startday,
		];

		$aRows = \DB::getQueryRows($sSql, $aSql);

		if(empty($aRows)) {
			return  [];
		}

		static::prepareDates($aRows, $oSchool);

		return $aRows;
	}

	protected static function prepareDates(array &$aRows, \Ext_Thebing_School $oSchool) {

		foreach($aRows as &$aRow) {

			$dWeekFrom = new DateTime($aRow['week_from']);
			$aRow['week_from'] = \Ext_Thebing_Format::LocalDate($dWeekFrom, $oSchool->id);

			$dWeekUntil = clone $dWeekFrom;
			$dWeekUntil->modify('+6 days');
			$dWeekUntil->setTime(23, 59, 59);
			$aRow['week_until'] = \Ext_Thebing_Format::LocalDate($dWeekUntil, $oSchool->id);

			$aRow['week_num'] = $dWeekFrom->format('W');

			$aRow['block_days'] = explode(',', $aRow['block_days']);
			if(count($aRow['block_days']) === 1) {
				$aRow['block_date'] = \Ext_Thebing_Format::LocalDate($aRow['block_date'], $oSchool->id);
			} else {
				$aRow['block_date'] = null;
			}

		}

	}

	/**
	 * @return mixed|string $sLanguage
	 */
	public static function getSelectedOrDefaultLanguage() {

		if(CookieHandler::is('frontendlanguage')) {
			$sLanguage = CookieHandler::get('frontendlanguage');
		} else {
			$aLanguages = \Ext_Thebing_Client::getLanguages();
			$sLanguage = \System::getDefaultInterfaceLanguage($aLanguages);
		}

		return $sLanguage;
	}

}
