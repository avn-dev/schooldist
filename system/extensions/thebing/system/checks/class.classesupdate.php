<?php

class Ext_Thebing_System_Checks_Classesupdate extends GlobalChecks
{
	public function executeCheck()
	{

		// prüfen, ob check schon ausgeführt wurde
		if(Ext_Thebing_Util::ifTableExists('kolumbus_tuition_classes'))
		{
			return true;
		}
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks_grouping');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks_courses');

		// Tabelle "kolumbus_tuition_classes" erstellen
		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_tuition_classes` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `user_id` int(11) NOT NULL,
			  `school_id` int(11) NOT NULL,
			  `color_id` int(11) NOT NULL,
			  `name` varchar(255) NOT NULL,
			  `start_week` date NOT NULL,
			  `weeks` tinyint(4) NOT NULL,
			  `level_increase` tinyint(4) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `color_id` (`color_id`),
			  KEY `school_id` (`school_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
		";
		$rRes = DB::executeQuery($sSql);
		if(!$rRes){
			__pout("kolumbus_tuition_classes konnte nicht erstellt werden", true);
		}

		// Tabelle "kolumbus_tuition_classes_courses" erstellen
		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_tuition_classes_courses` (
			  `class_id` int(11) NOT NULL default '0',
			  `course_id` int(11) NOT NULL default '0',
			  PRIMARY KEY  (`class_id`,`course_id`),
			  KEY `course_id` (`course_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";
		$rRes = DB::executeQuery($sSql);
		if(!$rRes){
			__pout("kolumbus_tuition_classes_courses konnte nicht erstellt werden", true);
		}

		// Column "class_id" ergänzen in "kolumbus_tuition_blocks"
		$sSql = "
			ALTER TABLE `kolumbus_tuition_blocks` ADD `class_id` INT NOT NULL AFTER `room_id` ,
			ADD INDEX `class_id` ( `class_id` )
		";
		$rRes = DB::executeQuery($sSql);
		if(!$rRes){
			__pout("column class_id konnte nicht erstellt werden in kolumbus_tuition_blocks", true);
		}

		// ++ kolumbus_tuition_block_grouping eintragen in kolumbus_tuition_classes
		// ++ --> überprüfen ob jeder Block ein Grouping-Eintrag hat
		// ++ Werte aus kolumbus_tuition_blocks_courses in kolumbus_tuition_classes_courses
		// ++ kolumbus_tuition_classes.id -> kolumbus_tuition_blocks.class_id

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
				`ktb`.`active` = 1
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

		return true;

		// Spalte kolumbus_tuition_blocks.blockname entfernen, Tabelle Grouping entfernen
	}

	public function getTitle()
	{
		return 'Update classes';
	}

	public function getDescription()
	{
		return 'Update classes.';
	}

}
