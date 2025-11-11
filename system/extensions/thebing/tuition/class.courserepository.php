<?php

class Ext_Thebing_Tuition_CourseRepository extends WDBasic_Repository {

	public function getBySchool(Ext_Thebing_School $oSchool, array $aFilter = []) {

		$aSql = [
			'school_id' => (int)$oSchool->id,
			'date' => date('Y-m-d')
		];
		
		$sWhere = "";

		if (!isset($aFilter['combination_selection'])) {
			$sWhere .= " AND only_for_combination_courses = 0 ";
		} else {
			$sWhere .= " AND per_unit != ".\Ext_Thebing_Tuition_Course::TYPE_COMBINATION." AND lessons_fix = 0 ";
		}

		if(isset($aFilter['type'])) {
			$sWhere .= " AND per_unit IN (:type) ";
			$aSql['type'] = $aFilter['type'];
		}

		if(isset($aFilter['category_id'])) {
			$sWhere .= " AND category_id = :category_id ";
			$aSql['category_id'] = (int)$aFilter['category_id'];
		}

		$sSql = "
			SELECT 
				* 
			FROM 
				kolumbus_tuition_courses 
			WHERE 
				active = 1 AND
				school_id = :school_id AND
				(
					`valid_until` = '0000-00-00' OR 
					`valid_until` >= :date
				)
				{$sWhere}
			ORDER BY
				`position`
		";
		
		$aResults = DB::getQueryRows($sSql, $aSql);
		
		$aEntities = array();
		if(is_array($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}
	
	/**
	 * @param bool $bShortName
	 * @return array
	 */
	public function findAllCategoriesForSelect($bShortName = false) {

		/** @var Ext_Thebing_Tuition_Course[] $aTmpCourses */
		$aTmpCourses = parent::findBy(['active' => 1]);

		$aCourses = [];
		if(!empty($aTmpCourses)) {

			$aCourses = [];
			foreach($aTmpCourses as $oCourse) {

				if($bShortName) {
					$sName = $oCourse->getShortName();
				} else {
					$sName = $oCourse->getName();
				}

				$aCourses[$oCourse->id] = $sName;

			}

		}

		return $aCourses;

	}

	/**
	 * Prüfen, ob dieser Kurs aktive Zuweisungen in der Klassenplanung hat
	 *
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 * @return bool
	 */
	public function hasTuitionAllocation(Ext_Thebing_Tuition_Course $oCourse) {

		$sSql = "
			SELECT
				1
			FROM
				`kolumbus_tuition_blocks_inquiries_courses`
			WHERE
				`active` = 1 AND
				`course_id` = :id
		";

		$iCount = DB::getQueryOne($sSql, ['id' => $oCourse->id]);

		return !empty($iCount);

	}

}
