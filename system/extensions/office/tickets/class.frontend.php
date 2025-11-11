<?php

class Ext_Office_Tickets_Frontend extends GUI_Ajax_Table
{
	/**
	 * Get the ticket data
	 */
	public function getEditData($iID)
	{
		$oTicket = new Ext_Office_Tickets($iID);
		$aReturn = $oTicket->aData;
	
		foreach((array)$aReturn as $sKey=>$sValue) {
			$aReturn[$sKey] = \Util::convertHtmlEntities($sValue);
		}
		
		$aNotices = $oTicket->getNotices();

		$aReturn['notices'] = (array)$aNotices;

		// Set default state
		$aReturn['state'] = 1;

		$iDone = 0;

		// Set last state
		foreach((array)$aNotices as $iKey => $aNotice)
		{
			$aReturn['state'] = $aNotice['state'];

			$aReturn['notices'][$iKey]['created']	= strftime('%x %X', $aNotice['created']);
			$aReturn['notices'][$iKey]['text']		= nl2br($aReturn['notices'][$iKey]['text']);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Files

			$sDir = Ext_Office_Tickets::getUploadPath();

			$sPath = $sDir . $iID . '/' . $aNotice['id'];

			$aFiles = array();

			if(is_dir($sPath))
			{
				$rDir = dir($sPath);

			    while($sFile = $rDir->read())
				{
				    if($sFile != '.' && $sFile != '..')
				    {
				    	$aFiles[] = array($iID . '/' . $aNotice['id'] . '/' . $sFile, $sFile);

				    	$_SESSION['access']['media']['secure']['secure/tickets/' . $iID . '/' . $aNotice['id']. '/'][$sFile] = 1;
				    }
				}
				$rDir->close();

				$aReturn['notices'][$iKey]['files'] = $aFiles;
			}

			if(($aNotice['state'] == 2 || $aNotice['state'] == 6) && $aNotice['done'] > 0)
			{
				$iDone = $aNotice['done'];
			}
		}

		if(!empty($iDone))
		{
			$aReturn['done'] = $iDone;
		}

		// Get state before
		if($aReturn['state'] == 3 || $aReturn['state'] == 6)
		{
			$iStateBefore = $aReturn['state'];

			$aTemp = array_reverse($aNotices);

			foreach((array)$aTemp as $aReverse)
			{
				if($aReverse['state'] != $aReturn['state'])
				{
					$iStateBefore = $aReverse['state'];

					break;
				}
			}
		}

		$aDescription = array_shift($aReturn['notices']);

		$aReturn['description']	= $aDescription['text'];
		$aReturn['descr_files']	= $aDescription['files'];

		if(empty($aDescription['contact']))
		{
			$aReturn['desc_contact'] = $aDescription['company'];
		}
		else
		{
			$aReturn['desc_contact'] = $aDescription['contact'];
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Switch buttons

		$aButtons = array();

		if($aReturn['type'] == 'bug')
		{
			switch((int)$aReturn['state'])
			{
				case 0: // Inactiv
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 0);
					$aButtons[] = array('title' => L10N::t('Aktivieren'), 'state' => 1);

					break;
				}
				case 1: // New
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 1);

					break;
				}
				case 2: // In edition
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 2);

					break;
				}
				case 3: // Question
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => $iStateBefore);

					break;
				}
				case 6: // Ready
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Abgenommen / geschlossen'), 'state' => 7);

					break;
				}
			}
		}
		else
		{
			switch((int)$aReturn['state'])
			{
				case 0: // Inactiv
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 0);
					$aButtons[] = array('title' => L10N::t('Aktivieren'), 'state' => 1);

					break;
				}
				case 1: // New
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 1);

					break;
				}
				case 2: // In edition
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 2);

					break;
				}
				case 3: // Question
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => $iStateBefore);

					break;
				}
				case 4: // Costs given
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 1);
					$aButtons[] = array('title' => L10N::t('Schätzung/Planung abgelehnt'), 'state' => 10);
					$aButtons[] = array('title' => L10N::t('Schätzung/Planung akzeptiert'), 'state' => 5);

					break;
				}
				case 5: // Costs accepted
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 5);

					break;
				}
				case 6: // Ready
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Abgenommen / geschlossen'), 'state' => 7);

					break;
				}
				case 8: // Testing
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 2);

					break;
				}
				case 9: // In planing
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 9);

					break;
				}
			}
		}

		$aReturn['buttons'] = $aButtons;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aReturn;
	}


	/**
	 * Return the affected projects list
	 */
	public function getProjects($bIDsOnly = false)
	{
		global $user_data;

		$sSQL = "
			SELECT `id`, `title`
			FROM `office_projects`
			WHERE
				`active` = 1 AND
				`customer_id` = :iCustomerID AND
				UNIX_TIMESTAMP(`closed_date`) = 0
			ORDER BY UPPER(`title`)
		";
		$aProjects = DB::getPreparedQueryData($sSQL, array('iCustomerID' => $user_data['data']['customer_id']));

		if($bIDsOnly)
		{
			$aIDs = array();
			foreach((array)$aProjects as $iKey => $aProject)
			{
				$aIDs[] = $aProject['id'];
			}
			return $aIDs;
		}

		return $aProjects;
	}


	/**
	 * Returns the list of entries
	 */
	public function getTableListData() {

		$aTableData = $this->_getTableList();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Format entries

		$aTypes		= Ext_Office_Tickets::getTypes();
		$aStates	= Ext_Office_Tickets::getStates();
		$aSystems	= Ext_Office_Tickets::getSystems();

		$oDate = new WDDate(0);

		$aReturn = array(
			'data'	=> array()
		);

		foreach((array)$aTableData as $iKey => $aRow) {

			$oDate->set($aRow['created'], WDDate::DB_TIMESTAMP);
			$aRow['created']	= strftime('%x %X', $oDate->get(WDDate::TIMESTAMP));
			
			$oDate->set($aRow['changed'], WDDate::DB_TIMESTAMP);
			$aRow['changed']	= strftime('%x %X', $oDate->get(WDDate::TIMESTAMP));
			
			$aRow['type']	= $aTypes[$aRow['type']];
			$aRow['state_id']	= $aRow['state'];
			$aRow['state']	= $aStates[$aRow['state']];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get worked times

			$aTimes = Ext_Office_Tickets::getWorkedTimes($aRow['id']);

			$oEmployee = new Ext_Office_Employee();

			$mTotal = $oEmployee->getFormatedTimes((int)$aTimes['total']);

			$aRow['time_total'] = $mTotal['T'];

			$mTotal = $oEmployee->getFormatedTimes((int)$aTimes['factored']);

			$aRow['time_factored'] = $mTotal['T'];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get done state

			$sSQL = "
				SELECT 
					`done`
				FROM 
					`office_ticket_notices`
				WHERE
					`ticket_id` = :iTicketID AND
					`active` = 1 AND
					`done` > 0
				ORDER BY 
					`id` DESC
				LIMIT 1
			";
			$iDone = DB::getQueryOne($sSQL, array('iTicketID' => $aRow['id']));

			$aRow['progressbar'] = '';

			$aRow['progressbar'] .= '<div style="position:relative; width:75px; height:20px; padding:0; background-color:#ff7373;">';
			$aRow['progressbar'] .= '<div style="float:left; position:absolute; top:0; left:0; height:20px; background-color:#A9F3A9; width:' . (int)(75 / 100 * (int)$iDone) . 'px;"></div>';
			$aRow['progressbar'] .= '<div style="float:left; position:absolute; top:0; left:0; line-height:20px; color:#555; width:75px; text-align:center;">' . (int)$iDone . '%</div>';
			$aRow['progressbar'] .= '</div>';

			$aReturn['data'][] = $aRow;
			
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(count((array)$aTableData['data']) == 1)
		{
			$oTicket = new WDBasic($aTableData['data'][0][0], 'office_tickets');

			$aReturn['single_project'] = $oTicket->project_id;
		}

		return $aReturn;

	}


	public function save($aData)
	{
		global $user_data;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save ticket data

		$oTicket = new Ext_Office_Tickets($aData['id']);

		if($oTicket->project_id <= 0)
		{
			$oTicket->project_id = $aData['project_id'];
		}

		if(
			empty($aData['id']) &&
			$aData['state'] < 2
		) {
			$oTicket->title			= rawurldecode($aData['title']);
			$oTicket->area			= rawurldecode($aData['area']);
			$oTicket->type			= $aData['type'];
		}

		if(is_numeric($aData['billing']) && $aData['billing'] !== false)
		{
			$oTicket->billing = $aData['billing'];
		}

		if((int)$aData['id'] <= 0)
		{
			$oTicket->position = 999999;
		}

		$oTicket->save();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare saving of ticket notice data

		$iNoticeID = 0;
		if($aData['state'] == 0 || $aData['state'] == 1)
		{
			$sSQL = "SELECT `id` FROM `office_ticket_notices` WHERE `state` = 0 AND `ticket_id` = " . (int)$oTicket->id . " LIMIT 1";
			$iNoticeID = (int)DB::getQueryOne($sSQL);
		}

		if($aData['state'] == 1)
		{
			$sSQL = "SELECT `id` FROM `office_ticket_notices` WHERE `state` = 1 AND `ticket_id` = " . (int)$oTicket->id . " LIMIT 1";
			$iCheck = (int)DB::getQueryOne($sSQL);

			if($iCheck > 0)
			{
				$iNoticeID = 0;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save ticket notice data

		$oPurifier = new HTMLPurifierWrapper('all');

		$oNotice = new WDBasic($iNoticeID, 'office_ticket_notices');

		$oNotice->ticket_id		= $oTicket->id;
		$oNotice->contact_id	= $user_data['id'];
		$oNotice->text			= $oPurifier->purify(rawurldecode($aData['notice']));
		$oNotice->state			= $aData['state'];
		$oNotice->ip			= $_SERVER['REMOTE_ADDR'];

		$oNotice->save();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Close BUG

		if(
			$aData['state'] == 7
		) {

			if($aData['type'] == 'bug') {
				$oTicket->cleared = 1;
				$oTicket->save();
			}

			DB::updateData('office_project_positions', array('active' => 0), "`ticket_id` = " . $oTicket->id);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Reopen a ticket

		if($aData['state'] == 2)
		{
			$sSQL = "SELECT MAX(`id`) FROM `office_ticket_notices` WHERE `ticket_id` = :iTicketID AND `user_id` > 0 LIMIT 1";
			$aSQL = array('iTicketID' => $oTicket->id);
			$iCheckID = DB::getQueryOne($sSQL, $aSQL);

			$oCheckNotice = new WDBasic($iCheckID, 'office_ticket_notices');

			if($oCheckNotice->done == 100)
			{
				$oNotice->done = 95;
				$oNotice->save();
			}

			$oTicket->system = 0;
			$oTicket->save();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save files

		// Hash_SessionID
		$aTemp = explode('_', $aData['hash']);

		if(!empty($_SESSION['gui'][$aTemp[0]]))
		{
			foreach((array)$_SESSION['gui'][$aTemp[0]] as $iKey => $aFile) {

				$sDir = Ext_Office_Tickets::getUploadPath();
				$sDir = $sDir . $oTicket->id . '/' . $oNotice->id . '/';

				Util::checkDir($sDir);

				rename($aFile['tmp_name'], $sDir . $aFile['name']);
				chmod($sDir . $aFile['name'], 0777);

			}

			unset($_SESSION['gui'][$aTemp[0]]);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Send emails to contact persons of the project

		if($oNotice->state > 0)
		{
			$oTicket->send();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $oNotice;

	}


	public function saveSort($aArray)
	{
		$aSqlString = $this->aSqlString;
		$aSql = $this->aConfigData['query_data'][1];
		$iPosition = 1;
		
		foreach((array)$aArray as $key => $aValue)
		{
			$aSqlString['from'] = 'office_tickets';
			if(! is_array($aValue))
			{
				continue;
			}
			foreach((array)$aValue as $iId){
				$sSql = "UPDATE ".$aSqlString['from']." SET #position_ = :position WHERE `id` = :id";
				$aSql['id'] = $iId;
				$aSql['position'] = $iPosition;
				$aSql['position_'] = $this->aLayoutData['sortable_column'];
				DB::executePreparedQuery($sSql,$aSql);
				$iPosition++;
			}
			break;
		}
	}

	/* ==================================================================================================== */

	/**
	 * Check the random string
	 */
	protected function _checkRand()
	{
		if($this->sRandString == "rand_0" || $this->sRandString == NULL)
		{
			$this->sRandString = "rand_".md5(uniqid(rand(), true));
		}
	}


	/**
	 * Returns the list of active entries
	 * 
	 * @return array
	 */
	protected function _getTableList()
	{
		global $_VARS, $user_data;

		$sWhere = $sAdd = "";

		$aSQL = array(
			'iProjectID'	=> $_VARS['filter_project'],
			'iCustomerID'	=> $user_data['data']['customer_id']
		);

		if(trim($_VARS['filter_search']) != '')
		{
			$sWhere .= " AND (`ot`.`id` = " . (int)$_VARS['filter_search'] . " OR `ot`.`title` LIKE CONCAT('%', :sString, '%') OR `ot`.`area` LIKE CONCAT('%', :sString, '%')) ";

			$aSQL['sString'] = trim($_VARS['filter_search']);

			$aProjectIDs = (array)$this->getProjects(true);

			if(is_numeric($_VARS['filter_search']))
			{
				$sWhere .= " OR (`ot`.`id` = " . (int)$_VARS['filter_search'] . " AND `ot`.`project_id` IN (" . implode(',', $aProjectIDs) . "))";
			}
		}

		if(!is_numeric($_VARS['filter_search']))
		{
			if(!empty($_VARS['filter_type']))
			{
				$sWhere .= " AND `ot`.`type` = :sType ";

				$aSQL['sType'] = $_VARS['filter_type'];
			}

			if(!empty($_VARS['filter_cleared']))
			{
				$sWhere .= " AND `ot`.`cleared` = 1 ";
			}
			else
			{
				$sWhere .= " AND `ot`.`cleared` = 0 ";
			}

			if(is_numeric($_VARS['filter_state']))
			{
				$sWhere .= " AND `otn_last`.`state` = :iState ";

				$aSQL['iState'] = trim($_VARS['filter_state']);
			}
			else if($_VARS['filter_state'] == '1_5')
			{
				$sWhere .= " AND ( ";
					$sWhere .= "(`otn_last`.`state` = 1 AND (`ot`.`type` = 'bug' OR (`ot`.`type` = 'ext' AND `ot`.`billing` = 1)))";
					$sWhere .= " OR ";
					$sWhere .= " `otn_last`.`state` = 5 ";
				$sWhere .= " ) ";

				$sAdd = " AND `otn_last`.`state` != 3 ";
			}
		}

		$sSQL = "
			SELECT
				`ot`.*,
				`otn_last`.`state`,
				`otn_last`.`changed`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) AS `editor`,
				IF(
					`su_creator`.`id` IS NULL,
					CONCAT(`oc_creator`.`firstname`, ' ', `oc_creator`.`lastname`),
					CONCAT(`su_creator`.`firstname`, ' ', `su_creator`.`lastname`)
				) `creator`
			FROM
				`office_tickets` AS `ot` INNER JOIN
				`office_projects` AS `op` ON
					`ot`.`project_id` = `op`.`id` INNER JOIN
				`office_ticket_notices` AS `otn_first` ON
					`otn_first`.`id` =
					(
						SELECT MIN(`id`)
						FROM `office_ticket_notices`
						WHERE
							`ticket_id` = `ot`.`id` AND
							`active` = 1
							" . $sAdd . "
					) LEFT JOIN
				`office_ticket_notices` AS `otn_last` ON
					`otn_last`.`id` =
					(
						SELECT MAX(`id`)
						FROM `office_ticket_notices`
						WHERE
							`ticket_id` = `ot`.`id` AND
							`active` = 1
							" . $sAdd . "
					) LEFT JOIN
				`system_user` `su` ON
					`su`.`id` = 
					(
						SELECT
							`user_id`
						FROM
							`office_ticket_notices`
						WHERE
							`state` = 2 AND
							`ticket_id` = `ot`.`id`
						ORDER BY 
							`id`
						LIMIT 1
					) LEFT JOIN 
				`system_user` `su_creator` ON
					`su_creator`.`id` = `otn_first`.`user_id` LEFT JOIN
				`office_contacts` AS `oc_creator` ON
					`oc_creator`.`id` = `otn_first`.`contact_id`
			WHERE
				`ot`.`active` = 1 AND
				`ot`.`project_id` = :iProjectID AND
				`op`.`customer_id` = :iCustomerID
			{WHERE}
			ORDER BY 
				`position`,
				`otn_last`.`changed` DESC
		";
		$sSQL = str_replace('{WHERE}', $sWhere, $sSQL);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aResult;
	}
}

?>