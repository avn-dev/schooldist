<?php

class Ext_Thebing_System_Checks_CleanClasses extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Clean classes';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Delete double entries and allocations.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_classes');

		/**
		 * Special delete queries
		 */
		 $sSql = "
			DELETE FROM
				`kolumbus_tuition_blocks`
			WHERE
				`week` = '0000-00-00'
			";
		DB::executeQuery($sSql);

		/**
		 * Clean block database
		 */
		$sSql = "
			SELECT
				`school_id`,
				`teacher_id`,
				`level_id`,
				`room_id`,
				`week`,
				`template_id`
			FROM
				`kolumbus_tuition_blocks`
			WHERE
				active = 1
			GROUP BY
				`school_id`,
				`teacher_id`,
				`level_id`,
				`room_id`,
				`week`,
				`template_id`
			";
		$aBlocks = DB::getQueryRows($sSql);

		foreach((array)$aBlocks as $aBlock) {

			$sSql = "
				SELECT
					ktb.id `id`,
					IF(ktbic.id IS NULL, 0, 1) `has_allocations`
				FROM
					`kolumbus_tuition_blocks` ktb LEFT JOIN
					`kolumbus_tuition_blocks_inquiries_courses` ktbic ON
						ktb.id = ktbic.block_id
				WHERE
					ktb.`school_id` = :school_id AND
					ktb.`teacher_id` = :teacher_id AND
					ktb.`level_id` = :level_id AND
					ktb.`room_id` = :room_id AND
					ktb.`week` = :week AND
					ktb.`template_id` = :template_id
				GROUP BY
					ktb.id
				ORDER BY
					`has_allocations` DESC
				";
			$aSql = array(
				'school_id'=>$aBlock['school_id'],
				'teacher_id'=>$aBlock['teacher_id'],
				'level_id'=>$aBlock['level_id'],
				'room_id'=>$aBlock['room_id'],
				'week'=>$aBlock['week'],
				'template_id'=>$aBlock['template_id']
			);
			$aItems = DB::getQueryRows($sSql, $aSql);

			// Nur erstes Element lassen, den Rest löschen
			array_shift($aItems);

			foreach((array)$aItems as $aItem) {
				if($aItem['has_allocations'] == 0) {
					$sSql = "DELETE FROM `kolumbus_tuition_blocks` WHERE `id` = :id LIMIT 1";
					$aSql = array('id'=>$aItem['id']);
					DB::executePreparedQuery($sSql, $aSql);
				}
			}

		}

		/**
		 * Repair block classes allocations
		 */
		try {
			// Blöcke gruppieren nach MasterID
			$sSql = "
				SELECT
					ktb.*,
					ktbg.master_id,
					ktbg.block_id
				FROM
					`kolumbus_tuition_blocks` `ktb` LEFT JOIN
					`kolumbus_tuition_blocks_grouping` `ktbg` ON
						`ktb`.`id` = `ktbg`.`block_id`
				WHERE
					`ktb`.`active` = 1 AND
					`ktb`.`class_id` = 0
			";
			$aBlocks			= DB::getQueryData($sSql);
			$aBlockDataGrouped	= array();

			foreach((array)$aBlocks as $aBlockData)
			{
				$iMasterId			= (int)$aBlockData['master_id'];

				if(0>=$iMasterId)
				{
					$iMasterId		= $aBlockData['id'];
				}

				$aBlockDataGrouped[$iMasterId][] = $aBlockData;
			}
			// Ende Blöcke gruppieren nach MasterID

			// DB-Objekt holen für InsertID
			$oDb = DB::getDefaultConnection();

			foreach($aBlockDataGrouped as $iMasterId => $aBlockDataMain)
			{

				// in Klasse umwandeln
				$aMasterData	= reset($aBlockDataMain);

				$aSaveData		= array(
					'changed'		=> (string)$aMasterData['changed'],
					'created'		=> (string)$aMasterData['created'],
					'active'		=> 1,
					'school_id'		=> (int)$aMasterData['school_id'],
					'name'			=> (string)$aMasterData['blockname'],
					'start_week'	=> (string)$aMasterData['week'],
					'weeks'			=> 1,
				);

				$iClassId = $oDb->insert('kolumbus_tuition_classes', $aSaveData);
				// Ende in Klasse umwandeln

				if($iClassId > 0)
				{

					$aElementIds = array();
					foreach($aBlockDataMain as $aElementData)
					{
						$aElementIds[] = $aElementData['id'];
					}

					if(!empty($aElementIds))
					{
						// neue KlassenID zuweisen
						$sSql = "
							UPDATE
								`kolumbus_tuition_blocks`
							SET
								`changed` = `changed`,
								`class_id` = :class_id
							WHERE
								`id` IN (:ids)
						";
						$aSql = array(
							'class_id'	=> $iClassId,
							'ids'		=> $aElementIds
						);

						$rRes = DB::executePreparedQuery($sSql, $aSql);
						if(!$rRes)
						{
							__pout('KlassenID('.$iClassId.') konnte nicht zugewiesen werden für die Blöcke '.implode(',', $aElementIds));
						}
						// Ende neue KlassenID zuweisen

						// Kurse übernehmen
						$sSql = "
							SELECT
								DISTINCT `course_id`
							FROM
								`kolumbus_tuition_blocks_courses`
							WHERE
								`block_id` IN (:block_ids)
						";

						$aSql = array(
							'block_ids' => $aElementIds,
						);

						$aCourses = DB::getPreparedQueryData($sSql, $aSql);

						if(!empty($aCourses))
						{
							$sInsertCourses = 'INSERT INTO `kolumbus_tuition_classes_courses` VALUES ';
							foreach((array)$aCourses as $aData)
							{
								$iCourseId	= (int)$aData['course_id'];

								$sInsertCourses .= "($iClassId, $iCourseId),";
							}

							$sInsertCourses = substr($sInsertCourses,0,strlen($sInsertCourses)-1);
							$sInsertCourses .= ';';

							$rRes = DB::executeQuery($sInsertCourses);
							if(!$rRes)
							{
								__pout("Kurse konnten nicht übernommen werden für die KlassenID: $iClassId");
							}
						}
						else
						{
							echo "keine Kurse gefunden für die KlassenID: ".(int)$iClassId."<br/>\n";
						}
						// Ende Kurse übernehmen
					}
					else
					{
						__pout("id in block array leer");
					}

				}
				else
				{
					__pout($aSaveData);
					__pout('InsertID nicht gefunden für Block '.$aMasterData['blockname'].'('.$aMasterData['id'].')');
				}

			}
		} catch(Exception $e) {

		}

		/**
		 * Clean unneeded tables and columns
		 */
		try {
			// Column "blockname" entfernen
			$sSql = 'ALTER TABLE `kolumbus_tuition_blocks` DROP `blockname`';
			$rRes = DB::executeQuery($sSql);
			if(!$rRes)
			{
				__pout('Column "blockname" konnte nicht aus "kolumbus_tuition_blocks" entfernt werden');
			}

			// Tabelle "kolumbus_tuition_blocks_grouping" entfernen
			$sSql = 'DROP TABLE `kolumbus_tuition_blocks_grouping`';
			$rRes = DB::executeQuery($sSql);
			if(!$rRes)
			{
				__pout('Tabelle "kolumbus_tuition_blocks_grouping" konnte nicht entfernt werden');
			}

			// Tabelle "kolumbus_tuition_blocks_courses" entfernen
			$sSql = 'DROP TABLE `kolumbus_tuition_blocks_courses`';
			$rRes = DB::executeQuery($sSql);
			if(!$rRes)
			{
				__pout('Tabelle "kolumbus_tuition_blocks_courses" konnte nicht entfernt werden');
			}
		} catch(Exception $e) {

		}

		// Leere Klassen löschen
		$sSql = "
			SELECT
				DISTINCT `class_id`
			FROM
				`kolumbus_tuition_blocks`
			";
		$aClasses = DB::getQueryCol($sSql);

		$sSql = "
			DELETE FROM
				`kolumbus_tuition_classes`
			WHERE
				`id` NOT IN (:classes) AND
				`user_id` = 0
			";
		$aSql = array('classes'=>$aClasses);
		DB::executePreparedQuery($sSql, $aSql);

		/**
		 * Optimize tables
		 */
		$sSql = "OPTIMIZE TABLE `kolumbus_tuition_classes`";
		$aSql = array('table'=>$sTable);
		DB::executePreparedQuery($sSql, $aSql);
		$sSql = "OPTIMIZE TABLE `kolumbus_tuition_blocks`";
		$aSql = array('table'=>$sTable);
		DB::executePreparedQuery($sSql, $aSql);

		return true;

	}

}
