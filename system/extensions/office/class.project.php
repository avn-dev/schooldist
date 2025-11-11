<?php

/**
 * The office projects class
 */
class Ext_Office_Project extends Ext_Office_Office
{
	/**
	 * The element data
	 */
	protected $_aData = array(
		'id'				=> 0,
		'changed'			=> null,
		'created'			=> null,
		'active'			=> 1,
		'start_date'		=> null,
		'end_date'			=> null,
		'closed_date'		=> null,
		'editor_id'			=> 0,
		'customer_id'		=> 0,
		'offer_id'			=> 0,
		'category_id'		=> 0,
		'conclusion'		=> '',	// Fazit
		'title'				=> '',
		'product_area_id'	=> 0,
		'description'		=> ''
	);

	/**
	 * The required fields
	 */
	protected $_aRequiredFields = array(
		array('field' => 'title', 'type' => 'TEXT'),
		array('field' => 'customer_id', 'type' => 'ID')
	);

	/**
	 * Temporary Project id get from session
	 */
	public $iTempProjectId = null;


	/**
	 * The contact persons
	 */
	public $aContacts = array();


	/**
	 * The final budget
	 */
	public $iBudget = null;


	/**
	 * The project positions
	 */
	public $aPositions = array();


	/**
	 * Returns the analytic data of a project
	 * 
	 * @return array : The analytic data
	 */
	public function getAnalysis() {

		$aAnalysis = array();

		$aAnalysis['budget'] = $this->iBudget;

		// ================================================== // Get the consumed budget

		$sWhere = "AND `op`.`id` = ".(int)$this->_aData['id'];
		$aProjects = $this->getProjectDetails($sWhere, true);
		$aProject = reset($aProjects);
		$aAnalysis['consumed_budget'] = (float)$aProject['consumed_budget'];
		$aAnalysis['costs'] = (float)$aProject['costs'];
		
		$aAnalysis['employees'] = array();
		$aAnalysis['employee_groups'] = array();

		// ================================================== // Get the costs of positions

		$sSQL = "
			SELECT
				`opp`.`id` AS `position_id`,
				`opp`.`project_id`,
				`opp`.`planed_amount`,
				`opc`.`title`,
				`opa`.`alias`
			FROM
				`office_project_positions`	AS `opp` LEFT OUTER JOIN
				`office_project_categories`	AS `opc` ON
					`opp`.`category_id` = `opc`.`id` LEFT OUTER JOIN
				`office_project_aliases`	AS `opa` ON
					`opp`.`alias_id` = `opa`.`id`
			WHERE
				`opp`.`project_id` = :iProjectID
		";
		$aResult = DB::getPreparedQueryData($sSQL, array('iProjectID' => $this->_aData['id']));

		$oEmployee = new Ext_Office_Employee();

		$aEmployeeGroups = $oEmployee->getGroups(true);

		foreach((array)$aResult as $iKey => $aValue) {

			$sSQL = "
				SELECT
					".$this->_getSqlCostsPart()."
					AS `price`,
					SUM(
						IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, UNIX_TIMESTAMP(`ot`.`end`), UNIX_TIMESTAMP(NOW()))
						 - UNIX_TIMESTAMP(`ot`.`start`)
					)
					AS `time`,
					`ot`.`start`,
					IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, `ot`.`end`, NOW()) AS `end`,
					`c`.`id` AS `employee_id`,
					`c`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`,
					`c`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
					`oe`.`reporting_group` `group_id`
				FROM
					`office_timeclock`			AS `ot` INNER JOIN
					`office_project_employees`	AS `ope` ON
						`ot`.`p2e_id` = `ope`.`id` INNER JOIN
					`customer_db_".$this->_aConfig['pro_database']."` AS `c` ON
						`ope`.`employee_id` = `c`.`id` LEFT JOIN
					`office_employees` `oe` ON
						`c`.`id` = `oe`.`id`
				WHERE
					`ot`.`active` = 1 AND
					`ot`.`cleared` != 9 AND
					`ot`.`action` != 'new' AND
					`p2p_id` = :iPositionID
				GROUP BY
					`ope`.`employee_id`
			";
			$aTCs = DB::getPreparedQueryData($sSQL, array('iPositionID' => $aValue['position_id']));

			$aResult[$iKey]['planed_amount'] = $oEmployee->getFormatedTimes($aResult[$iKey]['planed_amount'] * 3600);

			$aResult[$iKey]['price'] = $aResult[$iKey]['time'] = 0;
			foreach((array)$aTCs as $i_Key => $a_Value) {

				$aResult[$iKey]['price']	+= $a_Value['price'];
				$aResult[$iKey]['time']		+= $a_Value['time'];

				$aAnalysis['employees'][$a_Value['employee_id']]['price']	+= $a_Value['price'];
				$aAnalysis['employees'][$a_Value['employee_id']]['time']	+= $a_Value['time'];
				$aAnalysis['employees'][$a_Value['employee_id']]['firstname']	= $a_Value['firstname'];
				$aAnalysis['employees'][$a_Value['employee_id']]['lastname']	= $a_Value['lastname'];
				$aAnalysis['employees'][$a_Value['employee_id']]['employee_id']	= $a_Value['employee_id'];

				$aAnalysis['employee_groups'][$a_Value['employee_id']]['price']	+= $a_Value['price'];
				$aAnalysis['employee_groups'][$a_Value['employee_id']]['time']	+= $a_Value['time'];
				$aAnalysis['employee_groups'][$a_Value['employee_id']]['name']	= $aEmployeeGroups[$a_Value['group_id']];
				$aAnalysis['employee_groups'][$a_Value['employee_id']]['group_id']	= $a_Value['group_id'];

				$aTCs[$i_Key]['time'] = $oEmployee->getFormatedTimes($aTCs[$i_Key]['time']);

			}

			$aResult[$iKey]['time'] = $oEmployee->getFormatedTimes($aResult[$iKey]['time']);

			$aResult[$iKey]['times'] = $aTCs;
		}

		$aAnalysis['times'] = $aResult;

		$aAnalysis['employees'] = array_values($aAnalysis['employees']);
		foreach($aAnalysis['employees'] as &$aEmployeeData) {
			$aEmployeeData['time'] = $oEmployee->getFormatedTimes($aEmployeeData['time']);
		}

		$aAnalysis['employee_groups'] = array_values($aAnalysis['employee_groups']);
		foreach($aAnalysis['employee_groups'] as &$aEmployeeData) {
			$aEmployeeData['time'] = $oEmployee->getFormatedTimes($aEmployeeData['time']);
		}

		return $aAnalysis;

	}

	/**
	 * Returns the list of available categories or activities
	 * 
	 * @param string : The type of category
	 * @return array : The list of categories
	 */
	public function getCategories($sType = 'project')
	{
		if($sType != 'project' && $sType != 'activity')
		{
			throw new Exception('Wrong type of category!');
		}

		$sSQL = "SELECT * FROM `office_project_categories` WHERE `type` = :sType ORDER BY `title`";
		$aResult = DB::getPreparedQueryData($sSQL, array('sType' => $sType));

		return $aResult;
	}


	/**
	 * Return all available editors (Projektleiter)
	 * 
	 * @return array : The array with editors
	 */
	public function getEditors()
	{
		if(!isset($this->_aConfig['pro_database']))
		{
			throw new Exception('Please configure the employees database!');
		}
		if(!isset($this->_aConfig['pro_master_group']))
		{
			throw new Exception('Please define the employees master group!');
		}

		$sSQL = "
			SELECT
				*
			FROM
				`customer_db_".$this->_aConfig['pro_database']."`
			WHERE
				`groups` LIKE '%|".$this->_aConfig['pro_master_group']."|%'
		";
		$aEditors = DB::getQueryData($sSQL);

		return $aEditors;
	}


	/**
	 * Returns a list of employees who are not in the project by group
	 * 
	 * @return array : The list of employees
	 */
	public function getEmployeesListByGroup($iGroup, $sSearch)
	{
		$sSQL = "
			SELECT
				`c`.`id`,
				CONCAT(`c`.`".$this->_aConfig['pro_field_firstname']."`, ' ', `c`.`".$this->_aConfig['pro_field_lastname']."`) AS `name`,
				`c`.`".$this->_aConfig['pro_field_sektion']."` AS `sektion`,
				`c`.`".$this->_aConfig['pro_field_position']."` AS `position`
			FROM
				`customer_db_".$this->_aConfig['pro_database']."` AS `c`
			WHERE
				`c`.`active` = 1
					AND
				`c`.`groups` LIKE '%|".$iGroup."|%'
					AND
				(
					`c`.`".$this->_aConfig['pro_field_lastname']."` LIKE '".$sSearch."%'
						OR
					`c`.`".$this->_aConfig['pro_field_firstname']."` LIKE '".$sSearch."%'
				)
					AND
				`c`.`id` NOT IN
					(
						SELECT
							`employee_id`
						FROM
							`office_project_employees`
						WHERE
							`project_id` = :iProjectID AND
							`active` = 1
					)
			ORDER BY
				`c`.`".$this->_aConfig['pro_field_lastname']."`
			LIMIT
				100
		";
		$aSQL = array(
			'iProjectID'	=> $this->_aData['id'],
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult;
	}


	/**
	 * Returns the activities which are selected in a project
	 * 
	 * @return array : The list with activities
	 */
	public function getProjectActivities()
	{
		$sSQL = "
			SELECT
				`opc`.`id`,
				`opc`.`title`
			FROM
				`office_project_positions`	AS `opp`
					INNER JOIN
				`office_project_categories`	AS `opc`
					ON
				`opp`.`category_id` = `opc`.`id`
			WHERE
				`opp`.`project_id` = :iProjectID
		";
		$aActivities = DB::getQueryPairs($sSQL, array('iProjectID' => $this->_aData['id']));

		return $aActivities;
	}


	/**
	 * Returns the list of employees in a project
	 * 
	 * @return array : The list of employees
	 */
	public function getProjectEmployeesList()
	{
		// Get groups
		$sSQL = "
			SELECT
				`id`, `name`
			FROM
				`customer_groups`
			WHERE
				`db_nr` = ".$this->_aConfig['pro_database']."
		";
		$aGroups = DB::getQueryPairs($sSQL);

		$aResult = array();
		foreach((array)$aGroups as $iKey => $sValue)
		{
			$sSQL = "
				SELECT
					`ope`.`id`,
					`ope`.`group`,
					`ope`.`state`,
					`c`.`id` `employee_id`,
					CONCAT(`c`.`".$this->_aConfig['pro_field_firstname']."`, ' ', `c`.`".$this->_aConfig['pro_field_lastname']."`) AS `name`,
					`c`.`".$this->_aConfig['pro_field_sektion']."` AS `sektion`,
					`c`.`".$this->_aConfig['pro_field_position']."` AS `position`,
					`c`.`email`
				FROM
					`office_project_employees` AS `ope`
						INNER JOIN
					`customer_db_".$this->_aConfig['pro_database']."` AS `c`
						ON
					`c`.`id` = `ope`.`employee_id`
				WHERE
					`ope`.`project_id` = :iProjectID
						AND
					`ope`.`active` = 1
						AND
					`c`.`active` = 1
						AND
					`ope`.`group` = ".$iKey."
			";
			$aEmployees = DB::getPreparedQueryData($sSQL, array('iProjectID' => $this->_aData['id']));

			$aResult[] = array('group' => $sValue, 'employees' => $aEmployees);
		}

		return $aResult;
	}

	/**
	 * Returns the list of employees in a project
	 * 
	 * @return array : The list of employees
	 */
	public function getProjectEmployees($bIncludeInactive=false) {

		$sSql = "
			SELECT
				`c`.`id` `id`,
				CONCAT(`c`.`".$this->_aConfig['pro_field_firstname']."`, ' ', `c`.`".$this->_aConfig['pro_field_lastname']."`) AS `name`
			FROM
				`office_project_employees` AS `ope` INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `c` ON 
					`c`.`id` = `ope`.`employee_id`
			WHERE
				`ope`.`project_id` = :iProjectID AND
				`c`.`active` = 1
		";
		
		if($bIncludeInactive !== true) {
			$sSql .= " AND
				`ope`.`active` = 1
			";
		}

		$aEmployees = DB::getQueryPairs($sSql, array('iProjectID' => $this->_aData['id']));

		return $aEmployees;

	}

	public function getProjectHoursStatsList($iProjectID, $sFrom, $sTill) {

		$oFrom = new WDDate($sFrom, WDDate::DATES);
		$oFrom->set('00:00:00', WDDate::TIMES);

		$oTill = new WDDate($sTill, WDDate::DATES);
		$oTill->set('23:59:59', WDDate::TIMES);

		$aProjects = $this->getRunningProject($sFrom, $sTill);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData = array();

		$iDB = $this->_aConfig['pro_database'];

		foreach((array)$aProjects as $iKey => $aProject)
		{
			$sWhere = "";

			if((int)$iProjectID > 0 && $aProject['id'] != $iProjectID)
			{
				continue;
			}
			else
			{
				$sWhere .= " AND `opp`.`project_id` = :iProjectID ";
			}

			$sSQL = "
				SELECT
					`opp`.`project_id`,
					`opp`.`ticket_id`,
					`ope`.`employee_id`,
					SUM(
						IF(
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
					) AS `factored`,
					CONCAT(`cdb`.`".$this->_aConfig['pro_field_firstname']."`, ' ', `cdb`.`".$this->_aConfig['pro_field_lastname']."`) AS `name`,
					COUNT(`ot_1`.`id`) AS `bugs`,
					COUNT(`ot_2`.`id`) AS `exts`,
					SUM(
						IF(
							COALESCE(`ot_1`.`id`, -1) = -1,
							0,
							IF
							(
								UNIX_TIMESTAMP(`ot`.`end`) = 0,
								UNIX_TIMESTAMP(NOW()),
								UNIX_TIMESTAMP(`ot`.`end`)
							) - UNIX_TIMESTAMP(`ot`.`start`)
						)
					) AS `bugs_total`,
					SUM(
						(
							IF(
								COALESCE(`ot_1`.`id`, -1) = -1,
								0,
								IF
								(
									UNIX_TIMESTAMP(`ot`.`end`) = 0,
									UNIX_TIMESTAMP(NOW()),
									UNIX_TIMESTAMP(`ot`.`end`)
								) - UNIX_TIMESTAMP(`ot`.`start`)
							)
						) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)
					) AS `bugs_factored`,
					SUM(
						IF(
							COALESCE(`ot_2`.`id`, -1) = -1,
							0,
							IF
							(
								UNIX_TIMESTAMP(`ot`.`end`) = 0,
								UNIX_TIMESTAMP(NOW()),
								UNIX_TIMESTAMP(`ot`.`end`)
							) - UNIX_TIMESTAMP(`ot`.`start`)
						)
					) AS `exts_total`,
					SUM(
						(
							IF(
								COALESCE(`ot_2`.`id`, -1) = -1,
								0,
								IF
								(
									UNIX_TIMESTAMP(`ot`.`end`) = 0,
									UNIX_TIMESTAMP(NOW()),
									UNIX_TIMESTAMP(`ot`.`end`)
								) - UNIX_TIMESTAMP(`ot`.`start`)
							)
						) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)
					) AS `exts_factored`
				FROM
					`office_timeclock` AS `ot`						INNER JOIN
					`office_project_positions` AS `opp`					ON
						`opp`.`id` = `ot`.`p2p_id`					INNER JOIN
					`office_project_employees` AS `ope`					ON
						`ope`.`id` = `ot`.`p2e_id`					INNER JOIN
					`customer_db_" . $iDB . "` AS `cdb`					ON
						`ope`.`employee_id` = `cdb`.`id`			LEFT OUTER JOIN
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
					`office_tickets` AS `ot_1`							ON
						`opp`.`ticket_id` = `ot_1`.`id`				AND
						`ot_1`.`type` = 'bug'						LEFT OUTER JOIN
					`office_tickets` AS `ot_2`							ON
						`opp`.`ticket_id` = `ot_2`.`id`				AND
						`ot_2`.`type` = 'ext'						LEFT OUTER JOIN
					`office_employee_factors` AS `oef`					ON
						`oef`.`employee_id` = `ope`.`employee_id`	AND
						`oef`.`contract_id` = `oecd`.`id`			AND
						`oef`.`category_id` = `opp`.`category_id`
				WHERE
					UNIX_TIMESTAMP(`ot`.`start`) < :iTill AND
					(
						UNIX_TIMESTAMP(`ot`.`end`) > :iFrom OR
						UNIX_TIMESTAMP(`ot`.`end`) = 0
					) AND
					`ot`.`active` = 1			AND
					`ot`.`action` != 'new'		AND
					`ot`.`action` != 'declined'
				{WHERE}
				GROUP BY `ope`.`employee_id`
				ORDER BY `name`
			";
			$aSQL = array(
				'iProjectID'	=> $aProject['id'],
				'iFrom'			=> $oFrom->get(WDDate::TIMESTAMP),
				'iTill'			=> $oTill->get(WDDate::TIMESTAMP)
			);
			$sSQL = str_replace('{WHERE}', $sWhere, $sSQL);

			$aResults = DB::getPreparedQueryData($sSQL, $aSQL);

			foreach((array)$aResults as $aResult)
			{
				$aData[$aResult['employee_id']][] = $aResult;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create return data

		$aReturn = array(
			'projects'	=> $aProjects,
			'data'		=> array()
		);

		$aTotal = array(
			'name'			=> L10N::t('Gesamt'),
			'total'			=> 0,
			'factored'		=> 0,
			'bugs'			=> 0,
			'exts'			=> 0,
			'bugs_total'	=> 0,
			'bugs_factored'	=> 0,
			'exts_total'	=> 0,
			'exts_factored'	=> 0
		);

		foreach((array)$aData as $iEmployeeID => $aLines)
		{
			$aOne = array(
				'name'			=> '',
				'total'			=> 0,
				'factored'		=> 0,
				'bugs'			=> 0,
				'exts'			=> 0,
				'bugs_total'	=> 0,
				'bugs_factored'	=> 0,
				'exts_total'	=> 0,
				'exts_factored'	=> 0
			);

			foreach((array)$aLines as $aLine)
			{
				$aOne['name'] = $aLine['name'];

				$aOne['total']			+= $aLine['total'];
				$aOne['factored']		+= $aLine['factored'];
				$aOne['bugs']			+= $aLine['bugs'];
				$aOne['exts']			+= $aLine['exts'];
				$aOne['bugs_total']		+= $aLine['bugs_total'];
				$aOne['bugs_factored']	+= $aLine['bugs_factored'];
				$aOne['exts_total']		+= $aLine['exts_total'];
				$aOne['exts_factored']	+= $aLine['exts_factored'];

				$aTotal['total']			+= $aLine['total'];
				$aTotal['factored']			+= $aLine['factored'];
				$aTotal['bugs']				+= $aLine['bugs'];
				$aTotal['exts']				+= $aLine['exts'];
				$aTotal['bugs_total']		+= $aLine['bugs_total'];
				$aTotal['bugs_factored']	+= $aLine['bugs_factored'];
				$aTotal['exts_total']		+= $aLine['exts_total'];
				$aTotal['exts_factored']	+= $aLine['exts_factored'];
			}

			$aReturn['data'][] = $aOne;
		}

		$aReturn['data'][] = $aTotal;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Format return data

		$oEmployee = new Ext_Office_Employee();

		foreach((array)$aReturn['data'] as $iKey => $aValues)
		{
			foreach((array)$aValues as $sKey => $mValue)
			{
				if($sKey != 'name' && $sKey != 'bugs' && $sKey != 'exts')
				{
					$mValue = $oEmployee->getFormatedTimes($mValue);

					$aReturn['data'][$iKey][$sKey] = $mValue['T'];
				}
			}
		}
//wdmail('as@plan-i.de', 1, print_r($aReturn,1));
		return $aReturn;
	}


	public function getRunningProject($sFrom, $sTill)
	{
		$oFrom = new WDDate($sFrom, WDDate::DATES);
		$oFrom->set('00:00:00', WDDate::TIMES);

		$oTill = new WDDate($sTill, WDDate::DATES);
		$oTill->set('23:59:59', WDDate::TIMES);

		$sSQL = "
			SELECT `op`.`id`, `op`.`title`
			FROM
				`office_projects` AS `op`			INNER JOIN
				`office_project_positions` AS `opp`		ON
					`op`.`id` = `opp`.`project_id`	INNER JOIN
				`office_timeclock` AS `ot`				ON
					`opp`.`id` = `ot`.`p2p_id`
			WHERE
				UNIX_TIMESTAMP(`ot`.`start`) < :iTill AND
				(
					UNIX_TIMESTAMP(`ot`.`end`) > :iFrom OR
					UNIX_TIMESTAMP(`ot`.`end`) = 0
				)
			GROUP BY `op`.`id`
			ORDER BY `op`.`title`
		";
		$aSQL = array(
			'iFrom'	=> $oFrom->get(WDDate::TIMESTAMP),
			'iTill'	=> $oTill->get(WDDate::TIMESTAMP)
		);
		$aProjects = DB::getPreparedQueryData($sSQL, $aSQL);

		return (array)$aProjects;
	}


	/**
	 * Creates new project positions from offer positions
	 * 
	 * @param int : The ID of a document
	 * @return object $this
	 */
	public function getOfferPositions($iDocumentID)
	{
		$sSQL = "SELECT * FROM `office_document_items` WHERE `document_id` = :iID";
		$aDocPositions = DB::getPreparedQueryData($sSQL, array('iID' => $iDocumentID));

		// Format positions
		$aPositions = array();
		foreach((array)$aDocPositions as $iKey => $aValue)
		{
			$aPosition = array(
				'planed_amount'		=> $aValue['amount'],
				'amount'			=> $aValue['amount'],
				'unit'				=> $aValue['unit'],
				'title'				=> $aValue['product'],
				'category_id'		=> 0,
				'doc_position_id'	=> $aValue['id'],
				'price'				=> $aValue['price'],
				'alias'				=> ''
			);
			$aPositions[] = $aPosition;
		}

		$this->aPositions = $aPositions;

		return $this;
	}

	protected function _getSqlCostsPart() {
		
		$sSql = " COALESCE(SUM(
						(
							IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, UNIX_TIMESTAMP(`ot`.`end`), UNIX_TIMESTAMP(NOW()))
							 - UNIX_TIMESTAMP(`ot`.`start`)
						)
						* (
							(
								SELECT oecd.salary FROM  
									`office_project_employees` ope LEFT OUTER JOIN 
									office_employee_contract_data oecd ON
										oecd.employee_id = ope.employee_id AND
										oecd.active = 1									
								WHERE
									ot.p2e_id = ope.id AND
									(
										`ot`.`start` BETWEEN oecd.from AND oecd.until OR
										(
											`ot`.`start` > oecd.from AND
											oecd.until = 0
										)
									)
								LIMIT 1
							)
							/ 3600)
					), 0.00)";
	
		return $sSql;

	}

	protected function getProjectDetails($sWhere, $bExtended=false) {

		$sSql = "
			SELECT
				`op`.*,
				UNIX_TIMESTAMP(`op`.`start_date`) AS `start_date`,
				UNIX_TIMESTAMP(`op`.`end_date`) AS `end_date`,
				UNIX_TIMESTAMP(`op`.`closed_date`) AS `closed_date`,
				`opc`.`title` AS `category`,
				`opb`.`budget`,
                `oproa`.`name` AS `product_area_name`";
		
		if($bExtended === true) {

			$sSql .= ",
					COALESCE(SUM(
						(
							(
								IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, UNIX_TIMESTAMP(`ot`.`end`), UNIX_TIMESTAMP(NOW()))
								- UNIX_TIMESTAMP(`ot`.`start`)
							) / 100 * COALESCE(COALESCE(IF(`oef`.`factor` > 0, `oef`.`factor`, NULL), `oecd`.`factor`), 100)
						)
						* (`opc2`.`price` / 3600)
					), 0.00)
					AS `consumed_budget`,
					".$this->_getSqlCostsPart()."
					AS `costs`";
		
		}
		
		$sSql .= ",
				`c1`.`".$this->_aConfig['field_matchcode']."` AS `company`,
				CONCAT(`c2`.`".$this->_aConfig['pro_field_firstname']."`, ' ', `c2`.`".$this->_aConfig['pro_field_lastname']."`) AS `editor`
			FROM
				`office_projects` AS `op`
            LEFT OUTER JOIN
				`office_project_categories`	AS `opc`
            ON
                `op`.`category_id` = `opc`.`id`
            LEFT OUTER JOIN
				`office_project_budget` AS `opb`
            ON (
                `op`.`id` = `opb`.`project_id` AND
                UNIX_TIMESTAMP(`opb`.`changed`) = 0
            ) INNER JOIN
				`customer_db_".$this->_aConfig['database']."` AS `c1`
            ON
                `c1`.`id` = `op`.`customer_id`
            INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `c2`
            ON
                `c2`.`id` = `op`.`editor_id`
            LEFT OUTER JOIN
				`office_project_positions` opp
            ON
                opp.project_id = op.id
            LEFT OUTER JOIN
				`office_project_categories`	AS `opc2`
            ON
                `opp`.`category_id` = `opc2`.`id`
            LEFT OUTER JOIN
				`office_timeclock` ot
            ON (
                `opp`.`id` = `ot`.`p2p_id` AND
                `ot`.`active` = 1 AND
                `ot`.`cleared` != 9 AND
                `ot`.`action` != 'declined' AND
                `ot`.`action` != 'new'
            ) LEFT OUTER JOIN
				`office_project_employees`	AS `ope`
            ON (
                `ot`.`p2e_id` = `ope`.`id` AND
                `ope`.`project_id` = `op`.`id`
            ) LEFT OUTER JOIN
				`office_employee_contract_data` AS `oecd`
            ON (
                `ope`.`employee_id` = `oecd`.`employee_id` AND
                `oecd`.`active` = 1 AND (
                    `oecd`.`from` <= `ot`.`start` AND (
                        `oecd`.`until` = 0 OR
                        `oecd`.`until` > `ot`.`start`
                    )
                )
            ) LEFT OUTER JOIN
                `office_employee_factors` AS `oef`
            ON (
                `oef`.`employee_id` = `ope`.`employee_id` AND
                `oef`.`contract_id` = `oecd`.`id` AND
                `oef`.`category_id` = `opp`.`category_id`
            ) LEFT OUTER JOIN
                `office_product_areas` `oproa`
            ON
                `op`.`product_area_id` = `oproa`.`id`
			WHERE
				`op`.`active` = 1
                {WHERE}
			GROUP BY
				op.id
			ORDER BY 
				`op`.`title`,
				`company`
		";

		$aProjects = DB::getQueryData(str_replace('{WHERE}', $sWhere, $sSql));

		return $aProjects;

	}

	/**
	 * Returns the list of active projects
	 * 
	 * @return array : The list of active projects
	 */
	public function getProjectsList($sState, $sFrom, $sTo, $sSearch, $sProductAreaId = null) {

		/* ================================================== */ // Prepare WHERE condition

		if($sFrom && $sTo) {
			// Date
			$sFrom	= strtotimestamp($sFrom);
			$sTo	= strtotimestamp($sTo) + ( 60 * 60 * 24) - 1;
			// AND ((s <> F AND T) OR (s < F AND ((e <> F AND T) OR e > T)))
			$sWhere = "";
			$sWhere .= " AND ((`op`.`start_date` BETWEEN " . date('YmdHis', $sFrom) . " AND " . date('YmdHis', $sTo) . ") ";
			$sWhere .= " OR (`op`.`start_date` < " . date('YmdHis', $sFrom);
			$sWhere .= " AND ((`op`.`end_date` BETWEEN " . date('YmdHis', $sFrom) . " AND " . date('YmdHis', $sTo) . ") ";
			$sWhere .= " OR `op`.`end_date` > " . date('YmdHis', $sTo) . ")))";
		}

		// State
		if($sState == 'opened')
		{
			$sWhere .= " AND `op`.`closed_date` = 0 ";
		}

		if($sState == 'closed')
		{
			$sWhere .= " AND `op`.`closed_date` > 0 ";
		}

        if ($sProductAreaId != null && $sProductAreaId != '0' && $sProductAreaId != '') {
            $sWhere .= " AND `op`.`product_area_id` = ".(int)$sProductAreaId;
        }

		// Search string
		if(trim($sSearch) != '')
		{
			$sWhere .= " AND (
                /* search: project name */
                `op`.`title` LIKE '%".$sSearch."%' OR
                /* search: project category name */
                `opc`.`title` LIKE '%".$sSearch."%' OR
                /* search: product area name */
                `oproa`.`name` LIKE '%".$sSearch."%'
            ) ";
		}

		/* ================================================== */

		$bExtended = (bool)Ext_Office_Config::get('projects_show_reporting_list', null, true);
		$aProjects = $this->getProjectDetails($sWhere, $bExtended);

		// Format dates
		foreach((array)$aProjects as $iKey => $aValue)
		{
			$aProjects[$iKey]['start_date'] = date('d.m.Y', $aValue['start_date']);
			$aProjects[$iKey]['end_date'] = date('d.m.Y', $aValue['end_date']);
		}

		return $aProjects;
	}


	/**
	 * Makes a copy of a project
	 * 
	 * @return string : The new project title
	 */
	public function cloneProject()
	{
		/* ================================================== */ // Get / set the project data >>>

		$aProject = $this->_aData;

		$aProject['created']	= date('YmdHis');
		$aProject['title']		= 'Kopie - ' . $aProject['title'];
		$aProject['active']		= 1;
		$aProject['start_date']	= date('YmdHis', $aProject['start_date']);
		$aProject['end_date']	= date('YmdHis', $aProject['end_date']);

		unset(
			$aProject['id'],
			$aProject['changed'],
			$aProject['closed_date'],
			$aProject['offer_id'],
			$aProject['conclusion']
		);

		DB::insertData('office_projects', $aProject);
		$iProjectID = $aProject['id'] = DB::fetchInsertID();

		/* ================================================== */ // Get / set the positions & aliases >>>

		$sSQL = "
			SELECT 
				*
			FROM 
				`office_project_positions`
			WHERE 
				`project_id` = :iProjectID AND
				`active` = 1
		";
		$aPositions = DB::getPreparedQueryData($sSQL, array('iProjectID' => (int)$this->_aData['id']));

		foreach((array)$aPositions as $iKey => $aInsert)
		{
			$aInsert['created']			= date('YmdHis');
			$aInsert['doc_position_id']	= 0;
			$aInsert['project_id']		= $iProjectID;

			if($aInsert['alias_id'] != 0)
			{
				$sSQL = "
					SELECT `alias`
					FROM `office_project_aliases`
					WHERE `id` = :iAliasID
				";
				$sAlias = DB::getQueryOne($sSQL, array('iAliasID' => $aInsert['alias_id']));

				DB::insertData('office_project_aliases', array('project_id' => $iProjectID, 'alias' => $sAlias));

				$aInsert['alias_id'] = DB::fetchInsertID();
			}

			unset($aInsert['id'], $aInsert['changed']);

			DB::insertData('office_project_positions', $aInsert);
		}

		/* ================================================== */ // Get / set the budget >>>

		$sSQL = "
			SELECT *
			FROM `office_project_budget`
			WHERE `project_id` = :iProjectID
			ORDER BY `id` DESC
		";
		$aBudget = DB::getQueryRow($sSQL, array('iProjectID' => $this->_aData['id']));

		$aBudget['created']		= date('YmdHis');
		$aBudget['active']		= 1;
		$aBudget['project_id']	= $iProjectID;

		unset($aBudget['id'], $aBudget['changed']);

		DB::insertData('office_project_budget', $aBudget);

		/* ================================================== */ // Get / set the contact persons >>>

		$sSQL = "
			SELECT `customer_id`, `contact_id`
			FROM `office_project_contacts`
			WHERE `project_id` = :iProjectID
		";
		$aContacts = DB::getQueryPairs($sSQL, array('iProjectID' => $this->_aData['id']));

		foreach((array)$aContacts as $iCustomerID => $iContactID)
		{
			$aContact = array(
				'project_id'	=> $iProjectID,
				'customer_id'	=> $iCustomerID,
				'contact_id'	=> $iContactID
			);
			DB::insertData('office_project_contacts', $aContact);
		}

		/* ================================================== */

		$sSQL = "
			SELECT `employee_id`, `group`, `state`
			FROM `office_project_employees`
			WHERE `project_id` = :iProjectID AND `active` = 1
		";
		$aEmployees = DB::getQueryRows($sSQL, array('iProjectID' => $this->_aData['id']));

		foreach((array)$aEmployees as $aEmployee)
		{
			$aEmployee['created'] = date('YmdHis');
			$aEmployee['active'] = 1;
			$aEmployee['project_id'] = $iProjectID;
			DB::insertData('office_project_employees', $aEmployee);
		}

		/* ================================================== */

		return $aProject;
	}


	/**
	 * Closes a project
	 */
	public function close()
	{
		$this->closed_date = time();

		

		$this->save();
	}


	/**
	 * Saves the element data into the DB
	 * 
	 * @return object $this
	 */
	public function save()
	{
		$iBudget = $this->iBudget;

		// Save or update the project self
		parent::save();

		foreach((array)$_SESSION['project_'.$this->iTempProjectId]['positions'] as $iKey => $aValue)
		{
			$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['project_id'] = $this->_aData['id'];
		}

		$this->iBudget = $iBudget;

		/* ================================================== */ // Link to office document

		if($this->_aData['offer_id'] > 0)
		{
			$aSQL = array(
				'iProjectID'	=> $this->_aData['id'],
				'iDocumentID'	=> $this->_aData['offer_id']
			);
			$sSQL = "
				SELECT
					*
				FROM
					`office_project_document_link`
				WHERE
					`project_id` = :iProjectID
						AND
					`document_id` = :iDocumentID
				LIMIT 1
			";
			$mResult = DB::getQueryOne($sSQL, $aSQL);

			if(!$mResult)
			{
				$aInsert = array(
					'created'		=> date('YmdHis'),
					'document_id'	=> $this->_aData['offer_id'],
					'project_id'	=> $this->_aData['id']
				);
				DB::insertData('office_project_document_link', $aInsert);
			}
		}

		/* ================================================== */ // Manage contact persons

		// Delete all contact persons of project
		$sSQL = "DELETE FROM `office_project_contacts` WHERE `project_id` = " . (int)$this->_aData['id'];
		DB::executeQuery($sSQL);

		// Add all selected contact persons into DB
		foreach((array)$_SESSION['project_'.$this->iTempProjectId]['contacts'] as $iKey => $iValue)
		{
			$aInsert = array(
				'project_id'	=> (int)$this->_aData['id'],
				'customer_id'	=> (int)$this->_aData['customer_id'],
				'contact_id'	=> (int)$iKey
			);
			DB::insertData('office_project_contacts', $aInsert);
		}

		/* ================================================== */ // Manage positions

		$aCheck = $aPositions = array();

		// DELETE
		foreach((array)$_SESSION['project_'.$this->iTempProjectId]['positions'] as $iKey => $aValue)
		{
			if($aValue['id'] > 0 && isset($aValue['task']) && $aValue['task'] == 'delete')
			{
				// Delete position
				$sSQL = "UPDATE `office_project_positions` SET `active` = 0 WHERE `id` = " . (int)$aValue['id'];
				DB::executeQuery($sSQL);

				// Delete alias
//				$sSQL = "SELECT COUNT(`alias_id`) AS `counter` FROM `office_project_positions` WHERE `alias_id` = :iAliasID";
//				$iCount = DB::getQueryOne($sSQL, array('iAliasID' => $aValue['alias_id']));
//				if($iCount == 0)
//				{
//					$sSQL = "UPDATE `office_project_aliases` SET `active` = 0 WHERE `id` = " . (int)$aValue['alias_id'];
//					DB::executeQuery($sSQL);
//				}

				unset($_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]);
			}
			else
			{
				$aPositions[] = $aValue;
			}
		}
		$_SESSION['project_'.$this->iTempProjectId]['positions'] = $aPositions;

		foreach((array)$_SESSION['project_'.$this->iTempProjectId]['positions'] as $iKey => $aValue)
		{

			/* ================================================== */ // Manage aliases

			$sAlias = trim($aValue['alias']);
			unset($aValue['alias']);

			if($aValue['alias_id'] <= 0)
			{
				if(array_key_exists($aValue['doc_position_id'], $aCheck) && $aValue['doc_position_id'] > 0)
				{
					$aValue['alias_id'] = $aCheck[$aValue['doc_position_id']];
					$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['alias_id'] = $aCheck[$aValue['doc_position_id']];
				}
			}

			if(isset($aValue['flag']) && $aValue['flag'] == 'set_alias')
			{
				if($aValue['alias_id'] > 0 && $sAlias != '')
				{
					DB::updateData('office_project_aliases', array('alias' => $sAlias), '`id` = '.(int)$aValue['alias_id']);
				}
				else if($sAlias != '')
				{
					DB::insertData('office_project_aliases', array('alias' => $sAlias, 'project_id' => $this->_aData['id']));
					$aValue['alias_id'] = DB::fetchInsertID();
					$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['alias_id'] = $aValue['alias_id'];

					if($aValue['doc_position_id'] > 0)
					{
						$aCheck[$aValue['doc_position_id']] = $aValue['alias_id'];
					}

					foreach((array)$_SESSION['project_'.$this->iTempProjectId]['positions'] as $i_Key => $a_Value)
					{
						if($a_Value['doc_position_id'] == $aValue['doc_position_id'] && $aValue['doc_position_id'] > 0)
						{
							unset($_SESSION['project_'.$this->iTempProjectId]['positions'][$i_Key]['flag']);
							$_SESSION['project_'.$this->iTempProjectId]['positions'][$i_Key]['alias_id'] = $aValue['alias_id'];
						}
					}
				}
				unset($aValue['flag']);
			}

			if($aValue['alias_id'] <= 0)
			{
				if(array_key_exists($aValue['doc_position_id'], $aCheck))
				{
					$aValue['alias_id'] = $aCheck[$aValue['doc_position_id']];
					$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['alias_id'] = $aValue['alias_id'];
				}
			}

			/* ================================================== */

			// UPDATE
			if((int)$aValue['id'] > 0)
			{
				DB::updateData('office_project_positions', $aValue, '`id` = '.(int)$aValue['id']);

				$iPositionID = (int)$aValue['id'];
			}

			// INSERT
			else
			{
				unset($aValue['id']);

				if(!isset($aValue['project_id']) || (int)$aValue['project_id'] <= 0)
				{
					$aValue['project_id'] 	= (int)$this->_aData['id'];
				}

				$aValue['created']		= date('YmdHis');
				DB::insertData('office_project_positions', $aValue);

				$iPositionID = DB::fetchInsertID();
			}

			$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['id'] = $iPositionID;
			$_SESSION['project_'.$this->iTempProjectId]['positions'][$iKey]['alias'] = $sAlias;
		}

		$_SESSION['project_'.$this->_aData['id']] = $_SESSION['project_'.$this->iTempProjectId];

		if($this->_aData['id'] != $this->iTempProjectId)
		{
			unset($_SESSION['project_'.$this->iTempProjectId]);
		}

		$this->aPositions = $_SESSION['project_'.$this->_aData['id']]['positions'];

		$this->_updateBudget();

		return $this;
	}


	/**
	 * Updates the budget of a project if available
	 */
	protected function _updateBudget()
	{
		// set user_data global // will be needed for user_id
		global $user_data;

		// get last budget entry for this project
		$aSql = array(
			'project_id'	=> (int)$this->_aData['id']
		);
		$sSql = "
			SELECT
				*
			FROM
				`office_project_budget`
			WHERE
				`project_id` = :project_id
			AND
				UNIX_TIMESTAMP(`changed`) = 0
			LIMIT 1
		";
		$mBudget = DB::getQueryRow($sSql, $aSql);

		$iNow = time();
		$bFlag = false;

		if($mBudget && (float)$this->iBudget != (float)$mBudget['budget'])
		{
			// Update old budget
			DB::updateData(
				'office_project_budget',
				array('changed' => date('YmdHis', $iNow)),
				'`id` = '.(int)$mBudget['id']
			);
			$bFlag = true;
		}
		if(!$mBudget || $bFlag)
		{
			// Insert new entry
			$aInsert = array(
				'project_id'	=> (int)$this->_aData['id'],
				'editor_id'		=> $user_data['id'],			// TODO
				'budget'		=> (float)$this->iBudget,
				'created'		=> date('YmdHis')
			);
			DB::insertData('office_project_budget', $aInsert);
		}
	}


	/**
	 * Loads the element data from the DB
	 */
	protected function _loadData()
	{
		parent::_loadData();

		// Get budget
		$aSQL = array('project_id' => $this->_aData['id']);
		$sSQL = "
			SELECT
				*
			FROM
				`office_project_budget`
			WHERE
				`project_id` = :project_id
			AND
				UNIX_TIMESTAMP(`changed`) = 0
			LIMIT 1
		";
		$aBudget = DB::getPreparedQueryData($sSQL, $aSQL);
		$this->iBudget = $aBudget[0]['budget'];

		// Get positions inclusive aliases
		$sSQL = "
			SELECT
				`opp`.*,
				`opa`.`alias`
			FROM
				`office_project_positions` AS `opp`
					LEFT OUTER JOIN
				`office_project_aliases` AS `opa`
					ON
				`opp`.`alias_id` = `opa`.`id`
			WHERE
				`opp`.`project_id` = :project_id AND
				`opp`.`active` = 1
			ORDER BY
				`doc_position_id`
		";
		$aPositions = DB::getPreparedQueryData($sSQL, $aSQL);

		$aCheckDoubleAliases = array();
		foreach((array)$aPositions as $iKey => $aValue)
		{
			if(empty($aPositions[$iKey]['alias']))
			{
				$aPositions[$iKey]['alias'] = '';
			}
			if(!array_key_exists($aValue['doc_position_id'], $aCheckDoubleAliases))
			{
				$aCheckDoubleAliases[$aValue['doc_position_id']] = $aValue['alias_id'];
			}
			else if($aValue['doc_position_id'] > 0)
			{
				$aPositions[$iKey]['alias'] = $aPositions[$iKey]['title'] = '';
			}
		}

		$this->aPositions = $aPositions;
	}
}
