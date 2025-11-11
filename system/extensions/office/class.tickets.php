<?php

class Ext_Office_Tickets extends WDBasic {

	protected $_sTable = 'office_tickets';
	protected $_oProject;

	static public $sUploadPath = 'storage/office/tickets/';
	
	public function send() {
		global $_VARS, $user_data;

		$this->_oProject = new WDBasic($this->project_id, 'office_projects');

		$aTypes		= Ext_Office_Tickets::getTypes();
		$aStates	= Ext_Office_Tickets::getStates();
		$aSystems	= Ext_Office_Tickets::getSystems();

		$sSpacer = "--------------------------------------------------\n\n";

		$oDate		= new WDDate($this->created, WDDate::DB_TIMESTAMP);
		$aTimes		= self::getWorkedTimes($this->id);
		$oEmployee	= new Ext_Office_Employee();
		$mTotal		= $oEmployee->getFormatedTimes((int)$aTimes['total']);
		$mFactored	= $oEmployee->getFormatedTimes((int)$aTimes['factored']);

		$sMail = "";
		$sMail .= L10N::t('Datum:') . " " . strftime('%x %X', $oDate->get(WDDate::TIMESTAMP)) . "\n";
		$sMail .= L10N::t('Titel:') . " " . $this->title . "\n";
		$sMail .= L10N::t('Bereich:') . " " . $this->area . "\n";
		$sMail .= L10N::t('Ersteller:') . " " . "{CREATOR_TO_REPLACE_IN_THIS_MAIL}\n";
		$sMail .= L10N::t('Bearbeiter:') . " " . "{EDITOR_TO_REPLACE_IN_THIS_MAIL}\n";
		$sMail .= L10N::t('h-Brutto:') . " " . $mTotal['T'] . "\n";
		$sMail .= L10N::t('h-Netto:') . " " . $mFactored['T'] . "\n";
		$sMail .= L10N::t('Typ:') . " " . $aTypes[$this->type] . "\n";
		$sMail .= L10N::t('Status:') . " " . "{LAST_STATE_TO_REPLACE_IN_THIS_MAIL}\n";
		$sMail .= L10N::t('System:') . " " . $aSystems[$this->system] . "\n\n";
		$sMail .= $sSpacer;

		$aNotices = $this->getNotices();

		$mEditor = false;

		$aLastNotice = array();
		$aLastFrontendNotice = array();
		$aLastBackendNotice = array();
		$aRecipients = array();
		foreach((array)$aNotices as $iKey => $aNotice)
		{
			if($iKey == 0)
			{
				$sCreator = $aNotice['contact'];
			}

			if($mEditor === false && $aNotice['state'] == 2)
			{
				$mEditor = $aNotice['user'];
			}

			if($aNotice['user_id'] > 0) {
				$sFrom = $aNotice['user'];
				$aLastBackendNotice = $aNotice;

				$sSQL = "
					SELECT
						`email`
					FROM
						`system_user`
					WHERE
						`id` = :iUserID
					LIMIT
						1
				";
				$aSQL = array(
					'iUserID' => $aNotice['user_id']
				);
				$aRecipients[] = DB::getQueryOne($sSQL, $aSQL);

			} else {
				$sFrom = $aNotice['contact'];
				$aLastFrontendNotice = $aNotice;
			}

			if($iKey == 0) {
				$aNotice['text'] = html_entity_decode($aNotice['text'], ENT_QUOTES, 'UTF-8');
				$aNotice['text'] = str_replace("</li>", "<br/>", $aNotice['text']);
				$aNotice['text'] = str_replace("</p>", "<br/>", $aNotice['text']);
				$aNotice['text'] = str_replace("</div>", "<br/>", $aNotice['text']);
				$aNotice['text'] = preg_replace("/<br\s*\/>/", "\n", $aNotice['text']);
				$aNotice['text'] = strip_tags($aNotice['text']);
			}

			$mDone = '';
			if($aNotice['done'] > 0)
			{
				$mDone = ' / ' . L10N::t('Fortschritt:') . ' ' . (int)$aNotice['done'] . '%';
			}

			$sMail .= strftime('%x %X', $aNotice['created']) . ' / ' . $sFrom . ' / ' . L10N::t('Status:') . ' ' . $aStates[$aNotice['state']] . $mDone . "\n\n";
			$sMail .= $aNotice['text'] . "\n\n";
			$sMail .= $sSpacer;

			$aLastNotice = $aNotice;
		}

		$sMail = str_replace('{CREATOR_TO_REPLACE_IN_THIS_MAIL}', $sCreator, $sMail);
		$sMail = str_replace('{EDITOR_TO_REPLACE_IN_THIS_MAIL}', $mEditor, $sMail);
		$sMail = str_replace('{LAST_STATE_TO_REPLACE_IN_THIS_MAIL}', $aStates[$aLastNotice['state']], $sMail);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get ALL project leaders

		$aConfig = Ext_Office_Config::getInstance();

		if($aConfig['tickets_advices_leaders'])
		{
			$sSQL = "
				SELECT
					`c`.`email`
				FROM
					`office_project_employees` AS `ope` INNER JOIN
					`customer_db_" . (int)$aConfig['pro_database'] . "` AS `c` ON
						`ope`.`employee_id` = `c`.`id`
				WHERE
					`ope`.`project_id` = :iPtojectID AND
					`ope`.`group` = :iLeaderGroupID
			";
			$aSQL = array(
				'iPtojectID'		=> $this->_oProject->id,
				'iLeaderGroupID'	=> $aConfig['pro_master_group']
			);
			$aRecipients = (array)DB::getQueryCol($sSQL, $aSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if($aLastNotice['user_id'] > 0) {

			// send ticket to last contact
			$aLeaders = $this->getProjectContacts();
			$aContact = $this->getContact($aLastFrontendNotice['contact_id']);

			foreach((array)$aLeaders as $iKey => $aLeader) {
				$aRecipients[] = $aLeader['email'];
			}

			if(!empty($aContact)) {
				$aRecipients[] = $aContact['email'];
			}

			// Unset own user
			while(in_array($user_data['email'], $aRecipients))
			{
				unset($aRecipients[array_search($user_data['email'], $aRecipients)]);
			}

		} else {

			// send ticket to leader, developer and last editor
			$aLeaders = $this->getProjectLeaders();
			$aEditors = $this->getEditor(false);
			$aUser = $this->getUser($aLastBackendNotice['user_id']);

			foreach((array)$aLeaders as $iKey => $aLeader) {
				$aRecipients[] = $aLeader['email'];
			}

			if(!empty($aEditors))
			{
				foreach($aEditors as $aEditor)
				{
					$aRecipients[] = $aEditor['email'];
				}
			}
			if(!empty($aUser)) {
				$aRecipients[] = $aUser['email'];
			}

		}

		$aRecipients = array_unique($aRecipients);

		$sSubject = L10N::t('Ticket') . ' ' . $this->id . ' :: ' .$this->_oProject->title.' - '.$this->title;

		$oEmail = new Office\Service\Email;
		$oEmail->setSubject($sSubject);
		$oEmail->setText($sMail);

		$aRecipients = array_filter($aRecipients);
		
		$bSuccess = false;
		if(!empty($aRecipients)) {
			$bSuccess = $oEmail->send($aRecipients);
		}

		return $bSuccess;
	}

	public function getEditor($bOne = true) {

		$sLimit = "";

		if($bOne) {
			$sLimit = " LIMIT 1 ";
		}

		$sSQL = "
			SELECT
				`su`.*,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) AS `name`
			FROM
				`system_user` AS `su` INNER JOIN
				`office_ticket_notices` AS `otn` ON
					`su`.`id` = `otn`.`user_id`
			WHERE
				`otn`.`state` = 2 AND
				`otn`.`ticket_id` = :iTicketID
			ORDER BY 
				`otn`.`id`
			" . $sLimit . "
		";
		$aUsers = DB::getPreparedQueryData($sSQL, array('iTicketID' => $this->id));

		return $aUsers;
	}


	public function getEmployees() {

		$sSql = "
			SELECT
				`su`.`id`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) AS `name`
			FROM
				`system_user` AS `su` INNER JOIN
				`office_ticket_notices` AS `otn` ON
					`otn`.`user_id` = `su`.`id`
			GROUP BY
				`su`.`id`
			ORDER BY
				`name`
		";
		$aEmployees = DB::getQueryPairs($sSql);

		return $aEmployees;

	}


	/**
	 * Return the list of notices by ticket ID
	 */
	public function getNotices()
	{
		// Get customers DB
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'database' LIMIT 1";
		$iCustomerDB = DB::getQueryOne($sSQL);

		// Get customers DB company field
		$sSQL = "SELECT `value` FROM `office_config` WHERE `key` = 'field_company' LIMIT 1";
		$sCompanyField = DB::getQueryOne($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			SELECT
				`otn`.*,
				UNIX_TIMESTAMP(`otn`.`created`) AS `created`,
				CONCAT(`oc`.`firstname`, ' ', `oc`.`lastname`) AS `contact`,
				CONCAT(`su`.`firstname`, ' ', `su`.`lastname`) AS `user`,
				`cdb`.`" . $sCompanyField . "` AS `company`
			FROM
				`office_ticket_notices` AS `otn` LEFT OUTER JOIN
				`office_contacts` AS `oc` ON
					`otn`.`contact_id` = `oc`.`id` LEFT OUTER JOIN
				`system_user` AS `su` ON
					`otn`.`user_id` = `su`.`id` INNER JOIN
				`office_tickets` AS `ot` ON
					`otn`.`ticket_id` = `ot`.`id` INNER JOIN
				`office_projects` AS `op` ON
					`op`.`id` = `ot`.`project_id` INNER JOIN
				`customer_db_" . (int)$iCustomerDB . "` AS `cdb` ON
					`op`.`customer_id` = `cdb`.`id`
			WHERE
				`otn`.`active` = 1 AND
				`otn`.`ticket_id` = :iTicketID
			ORDER BY `otn`.`created`
		";
		$aNotices = DB::getPreparedQueryData($sSQL, array('iTicketID' => $this->id));

		return $aNotices;
	}

	public function getProjectLeaders()
	{
		// Get the employees DB number
		$sSQL = "
			SELECT `value`
			FROM `office_config`
			WHERE `key` = 'pro_database'
		";
		$iEmployeesDB_ID = DB::getQueryOne($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get the project employees

		$sSQL = "
			SELECT
				`cdb`.`email`
			FROM
				`office_tickets` AS `ot` JOIN
				`office_projects` AS `op` ON
					`op`.`id` = `ot`.`project_id` JOIN
				`customer_db_" . (int)$iEmployeesDB_ID . "` AS `cdb` ON
					`cdb`.`id` = `op`.`editor_id`
			WHERE
				`ot`.`id` = :iTicketID
		";
		$aSQL = array(
			'iTicketID' => $this->id
		);
		$aContacts = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aContacts;

	}


	public function getProjectContacts()
	{
		$sSQL = "
			SELECT
				`oc`.`email`
			FROM
				`office_contacts` AS `oc` INNER JOIN
				`office_project_contacts` AS `opc` ON
					`oc`.`id` = `opc`.`contact_id` INNER JOIN
				`office_projects` AS `op` ON
					`op`.`id` = `opc`.`project_id` INNER JOIN
				`office_tickets` AS `ot` ON
					`op`.`id` = `ot`.`project_id`
			WHERE
				`ot`.`id` = :iTicketID
		";
		$aSQL = array(
			'iTicketID' => $this->id
		);
		$aContacts = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aContacts;
	}


	public function getUser($iUserId)
	{
		$oUser = new User($iUserId);

		return $oUser->aData;
	}


	public function getContact($iContactId)
	{
		$oContact = new WDBasic($iContactId, 'office_contacts');

		return $oContact->aData;
	}


	/**
	 * Return available ticket states
	 */
	public static function getStates()
	{
		$aTypes = array(
			0	=> L10N::t('Inaktiv'),
			1	=> L10N::t('Neu'),
			9	=> L10N::t('In Planung'),
			2	=> L10N::t('In Bearbeitung'),
			3	=> L10N::t('Rückfrage'),
			4	=> L10N::t('Schätzung/Planung abgegeben'),
			5	=> L10N::t('Schätzung/Planung akzeptiert'),
			10	=> L10N::t('Schätzung/Planung abgelehnt'),
			8	=> L10N::t('Test-Phase'),
			6	=> L10N::t('Erledigt'),
			7	=> L10N::t('Abgenommen / geschlossen')
		);

		return $aTypes;
	}


	/**
	 * Return available ticket systems
	 */
	public static function getSystems()
	{
		$aSystems = array(
			0 => L10N::t('DEV'),
			1 => L10N::t('LIVE')
		);

		return $aSystems;
	}


	/**
	 * Return available ticket types
	 */
	public static function getTypes()
	{
		$aTypes = array(
			'ext'	=> L10N::t('Erweiterung'),
			'bug'	=> L10N::t('Bug')
		);

		return $aTypes;
	}


	public static function getWorkedTimes($iTicketID)
	{
		$sSQL = "
			SELECT
				SUM(
					IF
					(
						UNIX_TIMESTAMP(`ot`.`end`) = 0,
						UNIX_TIMESTAMP(NOW()),
						UNIX_TIMESTAMP(`ot`.`end`)
					) - UNIX_TIMESTAMP(`ot`.`start`)
				) AS `total`,
				SUM(
					(
						IF
						(
							UNIX_TIMESTAMP(`ot`.`end`) = 0,
							UNIX_TIMESTAMP(NOW()),
							UNIX_TIMESTAMP(`ot`.`end`)
						) - UNIX_TIMESTAMP(`ot`.`start`)
					) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)
				) AS `factored`
			FROM
				`office_timeclock` AS `ot`						INNER JOIN
				`office_project_positions` AS `opp`					ON
					`ot`.`p2p_id` = `opp`.`id`					AND
					`opp`.`ticket_id` = :iTicketID				LEFT OUTER JOIN
				`office_project_employees` AS `ope`					ON
					`ot`.`p2e_id` = `ope`.`id`					LEFT OUTER JOIN
				`office_employee_contract_data` AS `oecd`			ON
					`ope`.`employee_id` = `oecd`.`employee_id`	AND
					`oecd`.`active` = 1							AND
					(
						UNIX_TIMESTAMP(`oecd`.`from`) <= UNIX_TIMESTAMP(`ot`.`start`) AND
						(
							UNIX_TIMESTAMP(`oecd`.`until`) = 0 OR
							UNIX_TIMESTAMP(`oecd`.`until`) > UNIX_TIMESTAMP(`ot`.`start`)
						)
					)											LEFT OUTER JOIN
				`office_employee_factors` AS `oef`					ON
					`oef`.`employee_id` = `ope`.`employee_id`	AND
					`oef`.`contract_id` = `oecd`.`id`			AND
					`oef`.`category_id` = `opp`.`category_id`
			WHERE
				`ot`.`active` = 1					AND
				`ot`.`action` != 'new'				AND
				`ot`.`action` != 'declined'
		";
		$aTimes = DB::getQueryRow($sSQL, array('iTicketID' => $iTicketID));

		return $aTimes;
	}
	
	/**
	 * Gibt den letzten Status zurück
	 * @return int 
	 */
	public function getLastState() {

		$aNotices = $this->getNotices();

		$aTemp = array_reverse($aNotices);

		$iStateBefore = null;
		$iCurrentState = null;
		foreach((array)$aTemp as $aReverse) {

			if($iCurrentState == null) {
				$iCurrentState = $aReverse['state'];
			}

			if($aReverse['state'] != $iCurrentState) {

				$iStateBefore = $aReverse['state'];

				break;

			}

		}

		if($iStateBefore === null) {
			$iStateBefore = $iCurrentState;
		}
		
		return $iStateBefore;

	}
	
	public static function getUploadPath($bIncludeRoot=true) {
		
		$sUploadPath = '';
		
		if($bIncludeRoot === true) {
			$sUploadPath .= Util::getDocumentRoot();
		}
		
		$sUploadPath .= Ext_Office_Tickets::$sUploadPath;
		
		if($bIncludeRoot === true) {
			Util::checkDir($sUploadPath);
		}
		
		return $sUploadPath;
		
	}
	
	/**
	 * Speichert eine Datei zu einer Notiz
	 * 
	 * @param string $sFilePath
	 * @param string $sFileName
	 * @param int $iNoticeId 
	 */
	public function saveFile($sFilePath, $sFileName, $iNoticeId) {

		$sDir = Util::getDocumentRoot() . Ext_Office_Tickets::$sUploadPath;
		$sDir = $sDir . $this->id . '/' . $iNoticeId . '/';

		Util::checkDir($sDir);

		$sFileName = \Util::getCleanFileName($sFileName);
		
		$sTarget = $sDir . $sFileName;
		
		rename($sFilePath, $sTarget);
		chmod($sTarget, 0777);

	}
	
}