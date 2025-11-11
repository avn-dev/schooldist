<?php

class Ext_Thebing_System_Checks_SectionsCategories extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_examination_sections');

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_examination_sections_categories` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `user_id` int(11) default NULL,
			  `active` tinyint(1) NOT NULL,
			  `school_id` int(11) NOT NULL,
			  `name` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `school_id` (`school_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
		";

		$rRes = DB::executeQuery($sSql);

		if($rRes)
		{
			$sSql = "ALTER TABLE
						`kolumbus_examination_sections`
					ADD COLUMN
						`section_category_id` INT NOT NULL AFTER `school_id`,
					ADD INDEX
						`section_category_id` (`section_category_id`);
			";

			$rRes = DB::executeQuery($sSql);

			if($rRes)
			{
				$sSql = "
					SELECT
						*
					FROM
						`kolumbus_examination_sections`
					WHERE
						`active` = 1
				";

				$aResult		= (array)DB::getQueryRows($sSql);
				$aCategoryCache = array();

				foreach($aResult as $aRowData)
				{
					$iSchoolId	= (int)$aRowData['school_id'];
					$iSectionId	= (int)$aRowData['id'];

					if($iSchoolId <= 0 || $iSectionId <= 0)
					{
						__pout("no school_id or transcript area_id");
						continue;
					}

					if(!array_key_exists($iSchoolId,$aCategoryCache))
					{
						$mInsert = (int)DB::insertData('kolumbus_examination_sections_categories', array(
							'active'		=> 1,
							'school_id'		=> $iSchoolId,
							'name'			=> 'Import'
						));

						$iCategoryId = $mInsert;
					}
					else
					{
						$iCategoryId = $aCategoryCache[$iSchoolId];
					}

					if($iCategoryId>0)
					{
						$aCategoryCache[$iSchoolId] = $iCategoryId;

						$mReturn = DB::updateData('kolumbus_examination_sections', array('section_category_id' => $iCategoryId), 'id = '.$iSectionId);
						if(!$mReturn)
						{
							__pout("couldnt update transcript area: $iSectionId");
						}
					}
					else
					{
						__pout("couldnt create category for school: $iSchoolId");
					}
				}
			}
			else
			{
				__pout("couldnt modify transcripts area table");
			}
		}
		else
		{
			__pout("couldnt create transcripts area category table");
		}


		return true;
	}

	public function getTitle()
	{
		return 'Transcript areas categories';
	}

	public function getDescription()
	{
		return 'Allocate transcript areas to the new import category.';
	}
}