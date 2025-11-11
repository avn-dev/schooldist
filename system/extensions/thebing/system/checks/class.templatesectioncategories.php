<?php

	class Ext_Thebing_System_Checks_TemplateSectionCategories extends GlobalChecks
	{
		public function executeCheck()
		{
			set_time_limit(3600);
			ini_set("memory_limit", '2048M');

			Ext_Thebing_Util::backupTable('kolumbus_examination_templates_sections');

			$sSql = "
				SELECT
					`kexv`.`id`
				FROM
					`kolumbus_examination_version` AS `kexv` INNER JOIN
					`kolumbus_examination` AS `kex` ON
						`kexv`.`examination_id` = `kex`.`id` AND
						`kex`.`active` = 1 INNER JOIN
					`kolumbus_inquiries_documents` AS `kid` ON
						`kid`.`id` = `kex`.`document_id` AND
						`kid`.`active` = 1 LEFT JOIN
					`kolumbus_inquiries_documents_versions` AS `kidv` ON
						`kidv`.`document_id` = `kid`.`id` AND
						`kexv`.`version_nr` = `kidv`.`version` AND
						`kidv`.`active` = 1
				WHERE
					`kidv`.`id` IS NULL
			";

			try
			{
				$aDeleteIds = (array)DB::getQueryCol($sSql);
			}
			catch(DB_QueryFailedException $e)
			{
				$oMail = new WDMail();
				$oMail->subject = "Examen Import Error";
				$sText = $_SERVER['HTTP_HOST']."\n\n";
				$sText .= $e->getMessage()."\n\n";

				$oMail->text = $sText;
				$oMail->send(array('m.durmaz@thebing.com'));
				
				$aDeleteIds = array();
			}

			if(!empty($aDeleteIds))
			{
				$sSql = "
					DELETE FROM
						`kolumbus_examination_version`
					WHERE
						`id` IN (:delete_ids)
				";

				$aSql = array(
					'delete_ids' => $aDeleteIds,
				);

				try
				{
					DB::executePreparedQuery($sSql, $aSql);
				}
				catch(DB_QueryFailedException $e)
				{
					$oMail = new WDMail();
					$oMail->subject = "Examen Import Error";
					$sText = $_SERVER['HTTP_HOST']."\n\n";
					$sText .= $e->getMessage()."\n\n";

					$oMail->text = $sText;
					$oMail->send(array('m.durmaz@thebing.com'));
				}
			}

			if(Ext_Thebing_Util::ifTableExists('ts_examination_templates_sectioncategories'))
			{
				return true;
			}

			$sSql = "
				CREATE TABLE IF NOT EXISTS `ts_examination_templates_sectioncategories` (
				  `examination_template_id` int(10) unsigned NOT NULL,
				  `examination_sectioncategory_id` int(10) unsigned NOT NULL,
				  `sort_order` smallint(6) NOT NULL,
				  KEY `examination_template_id` (`examination_template_id`),
				  KEY `examination_sectioncategory_id` (`examination_sectioncategory_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			";

			try
			{
				DB::executeQuery($sSql);
			}
			catch(DB_QueryFailedException $e)
			{
				$oMail = new WDMail();
				$oMail->subject = "Examen Import Error";
				$sText = $_SERVER['HTTP_HOST']."\n\n";
				$sText .= $e->getMessage()."\n\n";

				$oMail->text = $sText;
				$oMail->send(array('m.durmaz@thebing.com'));

				return true;
			}

			$sSql = "
				SELECT
					`kexsc`.`id` `category_id`,
					`kexts`.`examination_template_id` `template_id`
				FROM
					`kolumbus_examination_templates_sections` `kexts` INNER JOIN
					`kolumbus_examination_sections` `kexs` ON
						`kexs`.`id` = `kexts`.`examination_section_id` AND
						`kexs`.`active` = 1 INNER JOIN
					`kolumbus_examination_sections_categories` `kexsc` ON
						`kexsc`.`id` = `kexs`.`section_category_id` AND
						`kexsc`.`active` = 1
				ORDER BY
					`kexts`.`sort_order`
			";

			try
			{
				$aResult = DB::getQueryRows($sSql);
			}
			catch(DB_QueryFailedException $e)
			{
				$oMail = new WDMail();
				$oMail->subject = "Examen Import Error";
				$sText = $_SERVER['HTTP_HOST']."\n\n";
				$sText .= $e->getMessage()."\n\n";

				$oMail->text = $sText;
				$oMail->send(array('m.durmaz@thebing.com'));
				return true;
			}

			$aTemp = array();

			foreach($aResult as $aRowData)
			{
				$iCagtegoryId	= (int)$aRowData['category_id'];
				$iTemplateId	= (int)$aRowData['template_id'];
				
				if(!isset($aTemp[$iTemplateId]))
				{
					$aTemp[$iTemplateId] = array();
				}

				if(!in_array($iCagtegoryId, $aTemp[$iTemplateId]))
				{
					$aTemp[$iTemplateId][] = $iCagtegoryId;
				}
			}

			$aConvertErrors = array();

			foreach($aTemp as $iTemplateId => $aCategories)
			{
				$iCounter = 1;
				foreach($aCategories as $iCategoryId)
				{
					$aInsertData = array(
						'examination_template_id'			=> $iTemplateId,
						'examination_sectioncategory_id'	=> $iCategoryId,
						'sort_order'						=> $iCounter,
					);
					try
					{
						DB::insertData('ts_examination_templates_sectioncategories', $aInsertData);
					}
					catch(DB_QueryFailedException $e)
					{
						$aConvertErrors[] = array(
							'message'		=> $e->getMessage(),
							'data'			=> $aInsertData,
						);
					}

					$iCounter++;
				}
			}

			if(!empty($aConvertErrors))
			{
				$oMail = new WDMail();
				$oMail->subject = "Examen Import Error";
				$sText = $_SERVER['HTTP_HOST']."\n\n";
				$sText .= print_r($aConvertErrors,1)."\n\n";

				$oMail->text = $sText;
				$oMail->send(array('m.durmaz@thebing.com'));
			}
			else
			{
				$sSql = "
					DROP TABLE `kolumbus_examination_templates_sections`
				";

				try
				{
					DB::executeQuery($sSql);
				}
				catch(DB_QueryFailedException $e)
				{
					$oMail = new WDMail();
					$oMail->subject = "Examen Import Error";
					$sText = $_SERVER['HTTP_HOST']."\n\n";
					$sText .= $e->getMessage()."\n\n";

					$oMail->text = $sText;
					$oMail->send(array('m.durmaz@thebing.com'));
				}
			}

			return true;
		}

		public function getTitle()
		{
			return 'Transcript Areas Categories Allocation';
		}

		public function getDescription()
		{
			return 'Allocate transcript areas categories with templates.';
		}
	}
