<?php
 
class Ext_Office_Tickets_Backend extends GUI_Ajax_Table
{
	/**
	 * Remove a ticket
	 * 
	 * @param int $iRowID
	 */
	public function deleteRow($iRowID)
	{
		$oTicket = new WDBasic($iRowID, 'office_tickets');
		$oTicket->active = 0;
		$oTicket->save();
	}


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

		$aReturn['upload_path'] = Ext_Office_Tickets::getUploadPath(false);
		
		$aReturn['notices'] = (array)$aNotices;

		$aStates = Ext_Office_Tickets::getStates();

		// Set default state
		$aReturn['state'] = 1;

		$iDone = 0;

		// Set last state
		foreach((array)$aNotices as $iKey => $aNotice)
		{
			$aReturn['state'] = $aNotice['state'];

			$aReturn['notices'][$iKey]['created']	= strftime('%x %H:%M', $aNotice['created']);
			$aReturn['notices'][$iKey]['text']		= nl2br($aReturn['notices'][$iKey]['text']);
			$aReturn['notices'][$iKey]['out_state']	= $aStates[$aReturn['notices'][$iKey]['state']];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Files

			$sDir = Util::getDocumentRoot() . Ext_Office_Tickets::$sUploadPath;

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
			if(!empty($aDescription['user']))
			{
				$aReturn['desc_contact'] = $aDescription['user'];
			}
			else
			{
				$aReturn['desc_contact'] = $aDescription['company'];
			}
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
				case 1: // New
				{
					$aButtons[] = array('title' => L10N::t('Kommentieren'), 'state' => 1);
					$aButtons[] = array('title' => L10N::t('In Bearbeitung'), 'state' => 2);
					$aButtons[] = array('title' => L10N::t('Rückfrage'), 'state' => 3);

					break;
				}
				case 2: // In edition
				{
					$aButtons[] = array('title' => L10N::t('Rückfrage'), 'state' => 3);
					$aButtons[] = array('title' => L10N::t('Erledigt'), 'state' => 6);

					break;
				}
				case 3: // Question
				{
					$aButtons[] = array('title' => L10N::t('Rückfrage aufheben'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 3);

					break;
				}
				case 6: // Ready
				{
					$aButtons[] = array('title' => L10N::t('Zurücksetzen'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Abgenommen / geschlossen'), 'state' => 7);

					break;
				}
			}
		}
		else
		{
			switch((int)$aReturn['state'])
			{
				case 1: // New
				{
					$aButtons[] = array('title' => L10N::t('Kommentieren'), 'state' => 1);
					$aButtons[] = array('title' => L10N::t('Rückfrage'), 'state' => 3);
					$aButtons[] = array('title' => L10N::t('In Planung'), 'state' => 9);

					if($aReturn['billing'] == 1)
					{
						$aButtons[] = array('title' => L10N::t('In Bearbeitung'), 'state' => 2);
					}
					else
					{
						$aButtons[] = array('title' => L10N::t('Schätzung/Planung abgegeben'), 'state' => 4, 'inputs' => true);
					}

					break;
				}
				case 2: // In edition
				{
					$aButtons[] = array('title' => L10N::t('Rückfrage'), 'state' => 3);
					$aButtons[] = array('title' => L10N::t('Test-Phase'), 'state' => 8);
					$aButtons[] = array('title' => L10N::t('Erledigt'), 'state' => 6);

					break;
				}
				case 3: // Question
				{
					$aButtons[] = array('title' => L10N::t('Rückfrage aufheben'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 3);

					break;
				}
				case 4: // Costs given
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 4);

					break;
				}
				case 5: // Costs accepted
				{
					$aButtons[] = array('title' => L10N::t('In Bearbeitung'), 'state' => 2);

					break;
				}
				case 6: // Ready
				{
					$aButtons[] = array('title' => L10N::t('Zurücksetzen'), 'state' => $iStateBefore);
					$aButtons[] = array('title' => L10N::t('Abgenommen / geschlossen'), 'state' => 7);

					break;
				}
				case 8: // Testing
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 8);
					$aButtons[] = array('title' => L10N::t('Erledigt'), 'state' => 6);

					break;
				}
				case 9: // In planing
				{
					$aButtons[] = array('title' => L10N::t('Speichern'), 'state' => 9);
					$aButtons[] = array('title' => L10N::t('Schätzung/Planung abgegeben'), 'state' => 4, 'inputs' => true);

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
	public function getProjects()
	{
		// Get customers DB
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'database' LIMIT 1";
		$iCustomerDB = DB::getQueryOne($sSQL);

		// Get customers DB matchcode field
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'field_matchcode' LIMIT 1";
		$sCompanyField = DB::getQueryOne($sSQL);

		$sSQL = "
			SELECT
				`op`.`id`,
				CONCAT(`cdb`.`" . $sCompanyField . "`, ' - ', `op`.`title`) AS `title`
			FROM
				`office_projects` AS `op` INNER JOIN
				`customer_db_" . (int)$iCustomerDB . "` AS `cdb` ON
					`op`.`customer_id` = `cdb`.`id`
			WHERE
				`op`.`active` = 1 AND
				`op`.`closed_date` = 0
			ORDER BY UPPER(CONCAT(`cdb`.`" . $sCompanyField . "`, ' - ', `op`.`title`))
		";
		$aProjects = DB::getQueryData($sSQL);

		return $aProjects;
	}


	public function getStartPageStatsList()
	{
		// Get customers DB
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'database' LIMIT 1";
		$iCustomerDB = DB::getQueryOne($sSQL);

		// Get customers DB company field
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'field_matchcode' LIMIT 1";
		$sCompanyField = DB::getQueryOne($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			SELECT
				`op`.`customer_id`,
				`oti`.`hours`,
				`oti`.`money`,
				`oti`.`project_id`,
				`oti`.`billing`,
				`cdb`.`" . $sCompanyField . "` AS `customer`,
				`op`.`title` AS `project`,
				COALESCE(SUM(UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)), 0) AS `total`,
				COALESCE(SUM((UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)), 0) AS `factored`,
				COALESCE(SUM((UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`)) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100) * (`opc`.`price` / 3600)), 0) AS `price`,
				`otn`.`state`
			FROM
				`office_tickets` AS `oti`				LEFT JOIN
				`office_ticket_notices` AS `otn`			ON
					`oti`.`id` = `otn`.`ticket_id`		AND
					`otn`.`state` = 7					INNER JOIN
				`office_projects` AS `op`					ON
					`oti`.`project_id` = `op`.`id` AND 
					`op`.`closed_date` = 0 INNER JOIN
				`office_project_positions` AS `opp`			ON
					`opp`.`ticket_id` = `oti`.`id`		AND
					`opp`.`project_id` = `op`.`id`		INNER JOIN
				`office_project_categories` AS `opc`		ON
					`opp`.`category_id` = `opc`.`id`	 INNER JOIN
				`customer_db_" . (int)$iCustomerDB . "` AS `cdb` ON
					`op`.`customer_id` = `cdb`.`id`		LEFT OUTER JOIN
				`office_timeclock` AS `ot`					ON
					`opp`.`id` = `ot`.`p2p_id`			AND
					`ot`.`active` = 1					AND
					`ot`.`end` > 0						AND
					`ot`.`action` != 'new'				AND
					`ot`.`action` != 'declined'			LEFT OUTER JOIN
				`office_project_employees` AS `ope`			ON
					`ot`.`p2e_id` = `ope`.`id`			LEFT OUTER JOIN
				`office_employee_contract_data` AS `oecd` ON
					`ope`.`employee_id` = `oecd`.`employee_id` AND
					`oecd`.`active` = 1 AND
					(
						UNIX_TIMESTAMP(`oecd`.`from`) <= UNIX_TIMESTAMP(`ot`.`start`) AND
						(
							UNIX_TIMESTAMP(`oecd`.`until`) = 0 OR
							UNIX_TIMESTAMP(`oecd`.`until`) > UNIX_TIMESTAMP(`ot`.`start`)
						)
					)									LEFT OUTER JOIN
				`office_employee_factors` AS `oef`			ON
					`oef`.`employee_id` = `ope`.`employee_id`	AND
					`oef`.`contract_id` = `oecd`.`id`			AND
					`oef`.`category_id` = `opp`.`category_id`
			WHERE
				`oti`.`cleared` = 0 AND
				`oti`.`active` = 1 AND
				`oti`.`type` = 'ext'
			GROUP BY 
				`oti`.`id`, `otn`.`id`
			ORDER BY
				`customer`,
				`project`
		";
		$aResults = DB::getQueryData($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aStats = array();

		$iTotal = $iFactored = $iPrice = 0;

		foreach((array)$aResults as $iKey => $aValue) {

			$aStats[$aValue['project_id']]['customer']	= $aValue['customer'];
			$aStats[$aValue['project_id']]['project']	= $aValue['project'];

			if($aValue['state'] == 7) {
				
				$aStats[$aValue['project_id']]['count']++;

				(float)$aStats[$aValue['project_id']]['hours']		+= $aValue['hours'];
				(float)$aStats[$aValue['project_id']]['money']		+= $aValue['money'];
				(float)$aStats[$aValue['project_id']]['total']		+= $aValue['total'];
				(float)$aStats[$aValue['project_id']]['factored']	+= $aValue['factored'];
				(float)$aStats[$aValue['project_id']]['price']		+= $aValue['price'];

			} else {
				
				$aStats[$aValue['project_id']]['outstanding_count']++;

				(float)$aStats[$aValue['project_id']]['outstanding_hours']		+= $aValue['hours'];
				(float)$aStats[$aValue['project_id']]['outstanding_money']		+= $aValue['money'];
				(float)$aStats[$aValue['project_id']]['outstanding_total']		+= $aValue['total'];
				(float)$aStats[$aValue['project_id']]['outstanding_factored']	+= $aValue['factored'];
				
				if($aValue['billing'] == 1) {
					(float)$aStats[$aValue['project_id']]['outstanding_price']	+= $aValue['price'];
				} else {
					(float)$aStats[$aValue['project_id']]['outstanding_price']	+= $aValue['money'];
				}

			}

		}

		$aResults = array_values($aResults);

		$aResults[] = array(
			'customer'	=> L10N::t('Gesamt'),
			'project'	=> '',
			'total'		=> $iTotal,
			'factored'	=> $iFactored,
			'price'		=> $iPrice
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oEmployee = new Ext_Office_Employee();

		foreach((array)$aStats as $iKey => $aValue) {
			$aTime = $oEmployee->getFormatedTimes((int)$aValue['total']);
			$aStats[$iKey]['total']		= $aTime['T'];
			$aTime = $oEmployee->getFormatedTimes((int)$aValue['factored']);
			$aStats[$iKey]['factored']	= $aTime['T'];

			$aTime = $oEmployee->getFormatedTimes((int)$aValue['outstanding_total']);
			$aStats[$iKey]['outstanding_total']		= $aTime['T'];
			$aTime = $oEmployee->getFormatedTimes((int)$aValue['outstanding_factored']);
			$aStats[$iKey]['outstanding_factored']	= $aTime['T'];
		}

		return $aStats;

	}


	/**
	 * Returns the list of entries
	 */
	public function getTableListData()
	{
		$aTableData = parent::getTableListData();

		unset($aTableData['pagination']);

		$aKeys = array();
		foreach((array)$this->aHeaderData as $iKey=>$aItem) {
			$aKeys[$aItem['column']] = ($iKey+1);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Format entries

		$aTypes		= Ext_Office_Tickets::getTypes();
		$aStates	= Ext_Office_Tickets::getStates();
		$aSystems	= Ext_Office_Tickets::getSystems();

		$oDate = new WDDate(0);

		foreach((array)$aTableData['data'] as $iKey => $aValue)
		{
			unset($aTableData['icon'][$aValue[0]][3]);
			unset($aTableData['icon'][$aValue[0]][4]);

			$aTableData['icon'][$aValue[0]][3] = 'setLive';

			if($aValue[$aKeys['state']] != 1 && $aValue[$aKeys['state']] != 10)
			{
				unset($aTableData['icon'][$aValue[0]][2]);
			}

			// Checkbox
			if(($aValue[$aKeys['state']] == 6 || $aValue[$aKeys['state']] == 7) && $aValue[$aKeys['system']] == 0)
			{
				$sClick = 'onclick="checkCBs(false, \'flag\');"';
				$aTableData['data'][$iKey][1] = '<input type="checkbox" class="flag" value="' . $aTableData['data'][$iKey][0] . '" id="flag_' . $aTableData['data'][$iKey][0] . '" ' . $sClick . ' />';
			}
			else
			{
				$aTableData['data'][$iKey][1] = '&nbsp;';
			}

			$aTableData['icon'][$aValue[0]] = array_values($aTableData['icon'][$aValue[0]]);

			$oDate->set($aValue[$aKeys['created']], WDDate::DB_TIMESTAMP);

			$aTableData['data'][$iKey][$aKeys['created']]	= strftime('%x %H:%M', $oDate->get(WDDate::TIMESTAMP));
			$aTableData['data'][$iKey][$aKeys['type']]	= $aTypes[$aValue[$aKeys['type']]];
			$aTableData['data'][$iKey][$aKeys['state']]	= $aStates[$aValue[$aKeys['state']]];
			$aTableData['data'][$iKey][$aKeys['system']]	= '<div style="text-align:center;">' . $aSystems[$aValue[$aKeys['system']]] . '</div>';

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get editor name

			$sSQL = "
				SELECT
					CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) AS `name`
				FROM
					`system_user` AS `su` INNER JOIN
					`office_ticket_notices` AS `otn` ON
						`su`.`id` = `otn`.`user_id`
				WHERE
					`otn`.`state` = 2 AND
					`otn`.`ticket_id` = :iTicketID
				ORDER BY `otn`.`id`
				LIMIT 1
			";
			$sUserName = DB::getQueryOne($sSQL, array('iTicketID' => $aTableData['data'][$iKey][0]));

			if(empty($sUserName))
			{
				$sUserName = '';
			}

			$aTableData['data'][$iKey][$aKeys['user_id']] = $sUserName;

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get author name

			$sSQL = "
				SELECT
					IF(
						`su`.`id` IS NULL,
						CONCAT(`oc`.`firstname`, ' ', `oc`.`lastname`),
						CONCAT(`su`.`firstname`, ' ', `su`.`lastname`)
					)  AS `name`
				FROM
					`office_ticket_notices` AS `otn` LEFT JOIN
					`system_user` AS `su` ON
						`su`.`id` = `otn`.`user_id` LEFT JOIN
					`office_contacts` AS `oc` ON
						`oc`.`id` = `otn`.`contact_id`
				WHERE
					`otn`.`state` = 1 AND
					`otn`.`ticket_id` = :iTicketID
				ORDER BY
					`otn`.`created` ASC
				LIMIT 1
			";
			$sUserName = DB::getQueryOne($sSQL, array('iTicketID' => $aTableData['data'][$iKey][0]));

			if(empty($sUserName))
			{
				$sUserName = '';
			}

			$aTableData['data'][$iKey][$aKeys['author_id']] = $sUserName;

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get worked times

			$aTimes = Ext_Office_Tickets::getWorkedTimes($aTableData['data'][$iKey][0]);

			$oEmployee = new Ext_Office_Employee();

			$iSeconds = $aValue[$aKeys['hours']]*3600;
			$mHours = $oEmployee->getFormatedTimes((int)$iSeconds);

			$aTableData['data'][$iKey][$aKeys['hours']] = $mHours['T'];
			
			$mTotal = $oEmployee->getFormatedTimes((int)$aTimes['total']);

			$aTableData['data'][$iKey][$aKeys['h_original']] = $mTotal['T'];
			
			$mTotal = $oEmployee->getFormatedTimes((int)$aTimes['factored']);

			if($iSeconds > 0) { 
				if($aTimes['factored'] > $iSeconds) {
					$aTableData['data'][$iKey][$aKeys['h_factored']] = '';
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= '<span style="color: red;">';
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= $mTotal['T'];
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= '</span>';
				} else {
					$aTableData['data'][$iKey][$aKeys['h_factored']] = '';
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= '<span style="color: green;">';
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= $mTotal['T'];
					$aTableData['data'][$iKey][$aKeys['h_factored']] .= '</span>';
				}
			} else {
				$aTableData['data'][$iKey][$aKeys['h_factored']] = $mTotal['T'];
			}
			
			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get done state

			$sSQL = "
				SELECT `done`
				FROM `office_ticket_notices`
				WHERE
					`ticket_id` = :iTicketID AND
					`active` = 1 AND
					`done` > 0
				ORDER BY `id` DESC
				LIMIT 1
			";
			$iDone = DB::getQueryOne($sSQL, array('iTicketID' => $aTableData['data'][$iKey][0]));

//			if($iDone < 50)
//			{
//				$sColor = 'ff7373';
//			}
//			else if($iDone < 75)
//			{
				$sColor = '67e667';
//			}
//			else
//			{
//				$sColor = 'A9F3A9';
//			}

			$aTableData['data'][$iKey][$aKeys['done']] = '';

			$aTableData['data'][$iKey][$aKeys['done']] .= '<div style="position:relative; width:75px; height:17px; padding:0; background-color:#ff7373;">';
				$aTableData['data'][$iKey][$aKeys['done']] .= '<div style="float:left; position:absolute; top:0; left:0; height:17px; background-color:#' . $sColor . '; width:' . (int)(75 / 100 * (int)$iDone) . 'px;"></div>';
				$aTableData['data'][$iKey][$aKeys['done']] .= '<div style="float:left; position:absolute; top:0; left:0; line-height:17px; color:#555; width:75px; text-align:center;">' . (int)$iDone . '%</div>';
			$aTableData['data'][$iKey][$aKeys['done']] .= '</div>';
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aReturn = array(
			'data'	=> $aTableData
		);

		if(count((array)$aTableData['data']) == 1) {
			$oTicket = new WDBasic($aTableData['data'][0][0], 'office_tickets');
			$aReturn['single_project'] = $oTicket->project_id;
		}

		return $aReturn;
	}

	public function save($aData) {
		global $user_data;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save ticket data

		$oTicket = new Ext_Office_Tickets($aData['id']);

		if(($aData['hours'] > 0 || $aData['money'] > 0) && $oTicket->billing != 1)
		{
			$oTicket->hours = $aData['hours'];
			$oTicket->money = $aData['money'];

			$oTicket->save();
		}

		if($aData['backend_new'] == 1)
		{
			$oTicket->project_id	= $aData['project_id'];
			$oTicket->title			= $aData['title'];
			$oTicket->area			= $aData['area'];
			$oTicket->type			= $aData['type'];
			$oTicket->position		= 999999;
			$oTicket->billing		= $aData['billing'];

			$oTicket->save();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save ticket notice data

		$oPurifier = new HTMLPurifierWrapper('all');

		$oNotice = new WDBasic(0, 'office_ticket_notices');

		$oNotice->ticket_id	= $oTicket->id;
		$oNotice->user_id	= $user_data['id'];
		$oNotice->text		= $oPurifier->purify(rawurldecode($aData['notice']));
		$oNotice->state		= $aData['state'];
		$oNotice->done		= (int)$aData['done'];
		$oNotice->ip		= $_SERVER['REMOTE_ADDR'];

		// If ready
		if($aData['state'] == 6)
		{
			$oNotice->done = 100;
		}

		$oNotice->save();

		if(
			$aData['state'] == 2 || 
			$aData['state'] == 8 || 
			$aData['state'] == 9
		) {
			$this->_addProjectPosition($oTicket, $aData['state']);
		}

		if(
			$aData['state'] == 7
		) {

			if($aData['type'] == 'bug') {
				$oTicket->cleared = 1;
				$oTicket->save();
			}

			DB::updateData('office_project_positions', array('active' => 0), "`ticket_id` = " . $oTicket->id);
		}
		
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save files

		// Hash_SessionID
		$aTemp = explode('_', $aData['hash']);

		if(!empty($_SESSION['gui'][$aTemp[0]])) {

			foreach((array)$_SESSION['gui'][$aTemp[0]] as $iKey => $aFile) {

				$sDir = Util::getDocumentRoot() . Ext_Office_Tickets::$sUploadPath;
				$sDir = $sDir . $oTicket->id . '/' . $oNotice->id . '/';

				Util::checkDir($sDir);

				rename($aFile['tmp_name'], $sDir . $aFile['name']);
				chmod($sDir . $aFile['name'], 0777);

			}

			unset($_SESSION['gui'][$aTemp[0]]);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Send emails to contact persons of the project

		$aConfig = Ext_Office_Config::getInstance();

		if($aData['state'] != 1 || $aConfig['tickets_advices_leaders'])
		{
			$oTicket->send();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $oTicket->id;

	}

	/* ==================================================================================================== */

	/**
	 * Add a position to the project
	 */
	protected function _addProjectPosition($oTicket, $iState)
	{
		// Programing
		$iCategoryID = 1;

		if($iState == 8)
		{
			$iCategoryID = 16; // Testing
		}
		else if($iState == 9)
		{
			$iCategoryID = 4; // Planing
		}

		$sSQL = "
			SELECT 
				*
			FROM 
				`office_project_positions`
			WHERE
				`project_id` = :iProjectID AND
				`ticket_id` = :iTicketID AND
				`category_id` = :iCategoryID
			LIMIT 1
		";
		$aSQL = array(
			'iProjectID'	=> $oTicket->project_id,
			'iTicketID'		=> $oTicket->id,
			'iCategoryID'	=> $iCategoryID
		);
		$mCheck = DB::getQueryRow($sSQL, $aSQL);

		if(empty($mCheck))
		{
			$aAlias = array(
				'project_id'	=> $oTicket->project_id,
				'alias'			=> 'T-' . $oTicket->id . ': ' . $oTicket->title
			);
			DB::insertData('office_project_aliases', $aAlias);

			$iAliasID = DB::fetchInsertID();

			$aPosition = array(
				'created'		=> date('YmdHis'),
				'project_id'	=> $oTicket->project_id,
				'alias_id'		=> $iAliasID,
				'ticket_id'		=> $oTicket->id,
				'category_id'	=> $iCategoryID,
				'title'			=> $oTicket->title . '(T-' . $oTicket->id . ')',
				'planed_amount'	=> $oTicket->hours,
				'price'			=> $oTicket->money,
				'unit'			=> 'Std.'
			);
			DB::insertData('office_project_positions', $aPosition);
		} else {
			
			if($mCheck['active'] != 1) {
				
				$aPosition = array(
					'active'=>1
				);
				DB::updateData('office_project_positions', $aPosition, " `id` = ".(int)$mCheck['id']);
				
			}
			
		}
	}


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
			'iProjectID' => $_VARS['filter_project']
		);

		if(trim($_VARS['filter_search']) != '')
		{
			$sWhere .= " AND (`ot`.`id` = " . (int)$_VARS['filter_search'] . " OR `ot`.`title` LIKE CONCAT('%', :sString, '%') OR `ot`.`area` LIKE CONCAT('%', :sString, '%')) ";

			$aSQL['sString'] = trim($_VARS['filter_search']);

			if(is_numeric($_VARS['filter_search']))
			{
				$sWhere .= " OR `ot`.`id` = " . (int)$_VARS['filter_search'];
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
				$sWhere .= " AND `otn`.`state` = :iState ";

				$aSQL['iState'] = trim($_VARS['filter_state']);
			}
			else if($_VARS['filter_state'] == '1_5')
			{
				$sWhere .= " AND ( ";
					$sWhere .= "(`otn`.`state` = 1 AND (`ot`.`type` = 'bug' OR (`ot`.`type` = 'ext' AND `ot`.`billing` = 1)))";
					$sWhere .= " OR ";
					$sWhere .= " `otn`.`state` = 5 ";
				$sWhere .= " ) ";

				$sAdd = " AND `otn`.`state` != 3 ";
			}
			else if($_VARS['filter_state'] == '2_8')
			{
				$sAdd = " AND (`otn`.`state` = 2 OR `otn`.`state` = 8) ";
			}
			else if($_VARS['filter_state'] == '0_0_0')
			{
				$sAdd = " AND `ot`.`hours` = 0 AND `ot`.`money` = 0 AND `ot`.`billing` = 0 AND `type` = 'ext' ";
			}

			if(is_numeric($_VARS['filter_user']))
			{
				$sWhere .= " AND 2 IN (SELECT DISTINCT `state` FROM `office_ticket_notices` WHERE `ot`.`id` = `ticket_id` AND `user_id` =  " . (int)$_VARS['filter_user'] . " ) ";
			}

			if(is_numeric($_VARS['filter_author']))
			{
				$sWhere .= " AND 1 IN (SELECT DISTINCT `state` FROM `office_ticket_notices` WHERE `ot`.`id` = `ticket_id` AND `user_id` =  " . (int)$_VARS['filter_author'] . " ) ";
			}
		}

		$sSQL = "
			SELECT
				`ot`.*,
				`otn`.`state`
			FROM
				`office_tickets` AS `ot` INNER JOIN
				`office_projects` AS `op` ON
					`ot`.`project_id` = `op`.`id` INNER JOIN
				`office_ticket_notices` AS `otn` ON
					`otn`.`id` = (SELECT MAX(`id`) FROM `office_ticket_notices` WHERE `ticket_id` = `ot`.`id` AND `active` = 1 " . $sAdd . ")
			WHERE
				`ot`.`active` = 1 AND
				`ot`.`project_id` = :iProjectID AND
				`otn`.`state` > 0
			{WHERE}
			ORDER BY `position`
		";
		$sSQL = str_replace('{WHERE}', $sWhere, $sSQL);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aResult;
	}

}
