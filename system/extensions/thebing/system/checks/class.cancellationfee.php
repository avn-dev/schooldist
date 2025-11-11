<?php


class Ext_Thebing_System_Checks_CancellationFee extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_stornofee');
		Ext_Thebing_Util::backupTable('kolumbus_stornofee_dynamic');

		$bTableCheck = Util::checkTableExists('kolumbus_cancellation_groups');

		if($bTableCheck){
			return true;
		}

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_cancellation_groups` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `user_id` int(11) NOT NULL,
			  `client_id` int(11) NOT NULL,
			  `active` tinyint(1) NOT NULL,
			  `name` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `client_id` (`client_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";
		$rResGroups = DB::executeQuery($sSql);

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_cancellation_fees` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `user_id` int(11) NOT NULL,
			  `creator_id` int(11) NOT NULL,
			  `active` tinyint(1) NOT NULL,
			  `group_id` int(11) NOT NULL,
			  `name` varchar(255) NOT NULL,
			  `days` smallint(6) NOT NULL,
			  `fee_value` smallint(6) NOT NULL,
			  `fee_type` tinyint(1) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `group_id` (`group_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";

		$rResFees = DB::executeQuery($sSql);

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_validity` (
			  `id` int(11) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `parent_id` int(11) NOT NULL,
			  `parent_type` varchar(255) NOT NULL,
			  `item_id` int(11) NOT NULL,
			  `item_type` varchar(255) NOT NULL,
			  `description` varchar(255) NOT NULL,
			  `valid_from` date NOT NULL,
			  `valid_until` date NOT NULL,
			  `comment` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `parent_id` (`parent_id`),
			  KEY `item_id` (`item_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";

		$rResValidity = DB::executeQuery($sSql);

		if($rResGroups && $rResFees && $rResValidity)
		{
				$sSql = "
					SELECT
						`kst`.*,
						`cdb2`.`ext_1` `school_name`,
						`kage`.`ext_1` `agency_name`
					FROM
						`kolumbus_stornofee` `kst` LEFT JOIN
						`customer_db_2` `cdb2` ON
							`cdb2`.`id` = `kst`.`idSchool` LEFT JOIN
						`kolumbus_agencies` `kage` ON
							`kage`.`id` = `kst`.`agency_id`
					WHERE
						`kst`.`active` = 1
				";

				$aResult = (array)DB::getQueryRows($sSql);

				$aAgencyCache = array();
				$aSchoolCache = array();

				foreach($aResult as $aRowData)
				{
					//Daten
					$iAgencyId					= (int)$aRowData['agency_id'];
					$sAgencyName				= $aRowData['agency_name'];
					$iSchoolId					= (int)$aRowData['idSchool'];
					$sSchoolName				= $aRowData['school_name'];
					$iClientId					= (int)$aRowData['idClient'];

					//Gruppe erstellen/holen
					if($iAgencyId>0)
					{
						//Agenturkunde
						$iItem		= $iAgencyId;
						$aItemCache	= &$aAgencyCache;
						$sItemName	= $sAgencyName;
						$sType		= 'agency';
					}
					else
					{
						//Direktbuchung
						$iItem		= $iSchoolId;
						$aItemCache	= &$aSchoolCache;
						$sItemName	= $sSchoolName;
						$sType		= 'school';
					}

					$bCanAddFee = true;

					if(!array_key_exists($iItem, $aItemCache))
					{
						$aInsertDataGroup = array(
							'active'	=> 1,
							'name'		=> $sItemName,
							'client_id'	=> $iClientId,
						);

						try
						{
							$iCancellationGroupId	= (int)DB::insertData('kolumbus_cancellation_groups', $aInsertDataGroup);
						}
						catch(DB_QueryFailedException $e)
						{
							__pout($e->getMessage());
							$iCancellationGroupId	= 0;
						}

						if($iCancellationGroupId>0)
						{
							$aInsertDataValidity = array(
								'active'		=> 1,
								'parent_id'		=> $iItem,
								'parent_type'	=> $sType,
								'item_id'		=> $iCancellationGroupId,
								'item_type'		=> 'cancellation_group',
								'valid_from'	=> '2008-01-01',
								'valid_until'	=> '0000-00-00',
								'description'	=> $sItemName,
							);

							try
							{
								$mReturn = DB::insertData('kolumbus_validity', $aInsertDataValidity);
							}
							catch(DB_QueryFailedException $e)
							{
								__pout($e->getMessage());
								$mReturn = false;
							}

							if(!$mReturn)
							{
								$bCanAddFee = false;
								__pout("couldn't create validity for $sType : $iItem");
								$oDb = DB::getDefaultConnection();
								__pout($oDb->getLastQuery());
							}
							else
							{
								$aItemCache[$iItem]		= $iCancellationGroupId;
							}
						}
						else
						{
							$bCanAddFee = false;
							__pout("couldn't create cancellation group");
							__pout($iCancellationGroupId);
							$oDb = DB::getDefaultConnection();
							__pout($oDb->getLastQuery());
						}

					}
					else
					{
						$iCancellationGroupId = (int)$aItemCache[$iItem];
					}

					if($bCanAddFee)
					{
						$aInsertData				= $aRowData;

						//Umstellen für insert
						#$aInsertData['client_id']	= $aInsertData['idClient'];
						$aInsertData['group_id']	= $iCancellationGroupId;
						$aInsertData['fee_value']	= $aInsertData['fee'];
						$aInsertData['fee_type']	= $aInsertData['fee_kind'];

						unset(
								$aInsertData['idSchool'],
								$aInsertData['idClient'],
								$aInsertData['agency_id'],
								$aInsertData['agency_name'],
								$aInsertData['school_name'],
								$aInsertData['fee'],
								$aInsertData['fee_kind']
						);

						try
						{
							$mReturn = DB::insertData('kolumbus_cancellation_fees', $aInsertData);
						}
						catch(DB_QueryFailedException $e)
						{
							__pout($e->getMessage());
							$mReturn = false;
						}

						if(!$mReturn)
						{
							__pout("couldn't convert cancellation fees");
							__pout($aInsertData);
							$oDb = DB::getDefaultConnection();
							__pout($oDb->getLastQuery());
						}
					}
				}


				$sSql = "DROP TABLE `kolumbus_stornofee`";
				$rRes = DB::executeQuery($sSql);
				if(!$rRes)
				{
					__pout("couldn't delete old cancellation fee table");
				}

				$sSql = "RENAME TABLE `kolumbus_stornofee_dynamic` TO `kolumbus_cancellation_fees_dynamic`;";
				$rRes = DB::executeQuery($sSql);
				if($rRes)
				{
					$sSql = "ALTER TABLE `kolumbus_cancellation_fees_dynamic` CHANGE `stornofee_id` `cancellation_fee_id` INT( 11 ) NOT NULL";
					$rRes = DB::executeQuery($sSql);
					if(!$rRes)
					{
						__pout("couldn't rename stornofee_id column in dynamic cancellation fee table");
					}
				}
				else
				{
					__pout("couldn't rename dynamic cancellation fee table");
				}
		}
		else
		{
			if(!$rResGroups)
			{
				__pout("couldnt create cancellation group table");
			}
			if(!$rResFees)
			{
				__pout("couldnt create cancellation fee table");
			}
			if(!$rResValidity)
			{
				__pout("couldnt create validity table");
			}
		}


		return true;
	}

	public function getTitle()
	{
		return 'Cancellation Fees Categories';
	}

	public function getDescription()
	{
		return 'Import of default category for cancellation fees.';
	}
}