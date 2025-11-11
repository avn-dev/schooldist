<?php

abstract class Ext_TC_Access_Matrix implements Ext_TC_Access_Matrix_Interface {

	// Table
	protected $_sTablePrefix = 'tc_access_matrix_';

	/**
	 * Variablen müssen in der Ableitung definiert werden
	 */
	protected $_sItemTable = '';
	protected $_sItemNameField = '';
	protected $_sItemOrderbyField = '';
	
	// The matrix type
	protected $_sType;

	// User ID
	protected $_iUserID;

	// The master user data
	protected $_aMaster = array();

	// Allowed items
	protected $_aItems;

	// All system groups
	public $aGroups;

	// The matrix data
	public $aMatrix;

	protected $aRight;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 * 
	 * @param string $sType
	 */
	public function __construct($iUserID = null) {
		
		if(
			empty($this->_sItemTable) ||
			empty($this->_sItemNameField) ||
			empty($this->_sItemOrderbyField)
		) {
			throw new Exception('Please define the item table properties!');
		}
		
		if(
			empty($this->_sType)
		) {
			throw new Exception('Please define the item type property!');
		}

		// Hauptuser setzen
		$this->_setMasterUser();
		
		if(!$iUserID) {
			$oUser = System::getCurrentUser();
			$this->_iUserID	= $oUser->id;
		} else {
			$this->_iUserID = (int)$iUserID;
		}

		$this->_loadGroups();

		$this->_loadItems();

		$this->_loadUser();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_aItems = $this->getListByUserRight();

	}

	/* ==================================================================================================== */

	protected function _setMasterUser() {

		$aMasterUsers = Ext_TC_Factory::executeStatic('Ext_TC_User', 'getMasterUser', array(true));

		$this->_aMaster = $aMasterUsers;
		
	}

	/**
	 * Create a owner right
	 * 
	 * @param int $iDataID
	 * @param string $sType
	 */
	final public function createOwnerRight($iDataID) {

		$sSQL = "
			SELECT
				`id`
			FROM
				#table
			WHERE
				`item_id` = :iItemID AND
				`item_type` = :sType
			LIMIT
				1
		";
		$aSQL = array(
			'iItemID'	=> $iDataID,
			'sType'		=> $this->_sType,
			'table'		=> $this->_sTablePrefix.'items'
		);
		$iItemID = DB::getQueryOne($sSQL, $aSQL);

		$oItem = new WDBasic($iItemID, $this->_sTablePrefix.'items');
		$oItem->item_id		= $iDataID;
		$oItem->item_type	= $this->_sType;
		$oItem->save();

		DB::updateJoinData(
			$this->_sTablePrefix.'rights',
			array(
				'item_id' => $oItem->id, 
				'user_id' => \Access_Backend::getInstance()->getUser()->id
			),
			1,
			'right'
		);

	}

	/**
	 * Get items list by user access right
	 */
	final public function getListByUserRight() {

		if(!empty($this->_aItems)) {
			return $this->_aItems;
		}

		$aSystem = $aTitles = $aList = array();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach((array)$this->aMatrix as $iKey => $aValues)
		{
			if($iKey == 0)
			{
				foreach((array)$aValues as $aValue)
				{
					$aTitles[$aValue['id']] = $aValue['name'];
					$aSystem[$aValue['id']] = $aValue['system'] ?? null;
				}

				continue;
			}
			else if($aValues[1]['user_id'] != $this->_iUserID)
			{
				continue;
			}

			foreach((array)$aValues as $aValue) {

				// Zugriff zulassen wenn Recht vorhanden oder der User Masteruser ist
				if(
					(
						isset($aValue['right']) &&
						$aValue['right'] === 1
					) ||
					(
						isset($aValue['item_id']) &&
						isset($aSystem[$aValue['item_id']]) &&
						$aSystem[$aValue['item_id']] == 1
					)
				) {
					$aList[$aValue['item_id']] = $aTitles[$aValue['item_id']];
				}
			}

			break;
		}

		return $aList;

	}


	/**
	 * Generate access table HTML code
	 * 
	 * @param string $sDescription
	 * @return string
	 */
	final public function generateHTML($sDescription) {

		$sCode = '';

		$oFormat = new Ext_Gui2_View_Format_UserName();

		$iTableWidth = 200;
		
		foreach($this->aMatrix[0] as $aData) {
			if($this->checkItemAccess($aData['item_id'])) {
				$iTableWidth += 180;
			}
		}

		$sCode .= <<<CSS
<style>
	.matrix-first-col {
		position: sticky;
		left: 0;
		width: 200px;
		/* background-color: #fff; */
	}
	.matrix-section-row > * {
		background-color: rgba(197, 208, 213, .5); /* bg-gray-100/50 */
	}
	.matrix-header {
	    word-break: break-word;
		overflow-wrap: anywhere;
		white-space: normal !important;
		width:150px;
		text-align:center !important;
	}
</style>
CSS;


		$sCode .= '<div style="overflow: auto">'; // Früher war das in .GUIDialogContentDiv drin
		$sCode .= '<table cellspacing="0" cellpadding="4" style="table-layout: fixed; width: '.(int)$iTableWidth.'px" class="table">';

			$sCode .= $this->_writeHeader($sDescription);

			$sCode .= '<tr class="matrix-section-row">';
			$sCode .= '<th class="matrix-first-col">'.L10N::t('Benutzer', $sDescription).'</th>';
			$sCode .= '<td colspan="'.count($this->aMatrix[0]).'"></td>';
			$sCode .= '</tr>';

			foreach((array)$this->aMatrix as $iMatrixKey => $aMatrix)
			{
				if($iMatrixKey == 0)
				{
					continue;
				}

				$sCode .= '<tr>';

				foreach((array)$aMatrix as $iKey => $aData)
				{
					if($iKey == 0)
					{
						$sCode .= '<th class="matrix-first-col">';
							$sCode .= $oFormat->format('', $sCode, $aData);
						$sCode .= '</th>';
					}
					else
					{
						if($this->checkItemAccess($aData['item_id'])) {
							$sColor = Ext_TC_Util::getColor('red');
							if($aData['right'] == 1)
							{
								$sColor = Ext_TC_Util::getColor('green');
							}

							$sCode .= '<td class="text-center" style="background-color:' . $sColor . ';">';

								$sName	= 'save[access][user][' . (int)$aData['item_link_id'] . '][' . (int)$aData['item_id'] . '][' . (int)$aData['user_id'] . ']';
								$sID	= 'access_' . (int)$aData['item_link_id'] . '_' . (int)$aData['item_id'] . '_' . (int)$aData['user_id'];
								$sClass	= 'access_' . (int)$aData['item_link_id'] . '_' . (int)$aData['item_id'];

								$sCode .= '<select class="txt form-control input-sm accessOne" name="' . $sName . '" id="' . $sID . '" data-group-class="'.$sClass.'" data-user-id="'.$aData['user_id'].'">';
									$sSelected = '';
									if($aData['access'] == -1)
									{
										$sSelected = 'selected="selected"';
									}
									$sCode .= '<option value="-1" ' . $sSelected . '></option>';

									$sSelected = '';
									if($aData['access'] == 0)
									{
										$sSelected = 'selected="selected"';
									}
									$sCode .= '<option value="0" ' . $sSelected . '>' . L10N::t('Nein', $sDescription) . '</option>';

									$sSelected = '';
									if($aData['access'] == 1)
									{
										$sSelected = 'selected="selected"';
									}
									$sCode .= '<option value="1" ' . $sSelected . '>' . L10N::t('Ja', $sDescription) . '</option>';
								$sCode .= '</select>';
							$sCode .= '</td>';
						}
					}
				}

				$sCode .= '</tr>';
			}

		$sCode .= '</table>';
		$sCode .= '</div>';

		return $sCode;

	}

	/**
	 * Write table header
	 * 
	 * @return string
	 */
	final protected function _writeHeader($sDescription)	{

		$aHeader = $this->aMatrix[0];

		$sCode = '';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sCode .= '<tr style="position: sticky; top: 0;">';

		$sCode .= '<th class="matrix-first-col">&nbsp;</th>';

		foreach((array)$aHeader as $aMatrix) {
			if(!$this->checkItemAccess($aMatrix['id'])) {
				continue;
			}

			$sCode .= '<th class="text-center matrix-header" data-scroll-id="'.$aMatrix['id'].'">';
				$sCode .= $aMatrix['name'];
			$sCode .= '</th>';
		}

		$sCode .= '</tr>';

		/*$sCode .= '<tr>';

		$sCode .= '<th class="matrix-first-col">&nbsp;</th>';

		foreach((array)$aHeader as $aMatrix) {

			if(!$this->checkItemAccess($aMatrix['id'])) {
				continue;
			}

			$sCode .= '<th style="text-align:center; width:180px;">';

			$sName	= 'save[access][groups][' . (int)$aMatrix['item_id'] . '][' . (int)$aMatrix['id'] . '][]';
			$sID	= 'access_' . (int)$aMatrix['item_id'] . '_' . (int)$aMatrix['id'];

			$sCode .= '<select class="form-control accessGroup" multiple="multiple" size="4" name="' . $sName . '" id="' . $sID . '">';
			foreach((array)$this->aGroups as $iGroupID => $sGroup) {
				$sSelected = '';
				if(isset($aMatrix['groups'][$iGroupID])) {
					$sSelected = 'selected="selected"';
				}
				$sCode .= '<option value="' . $iGroupID . '" ' . $sSelected . '>' . $sGroup . '</option>';
			}
			$sCode .= '</select>';

			$sCode .= '</th>';
		}

		$sCode .= '</tr>';*/

		$sCode .= '<tr class="matrix-section-row">';
		$sCode .= '<th class="matrix-first-col">'.L10N::t('Gruppen', $sDescription).'</th>';
		$sCode .= '<td colspan="'.count($aHeader).'"></td>';
		$sCode .= '</tr>';

		foreach ($this->aGroups as $iGroupID => $sGroup) {
			$sCode .= '<tr>';

			$sCode .= '<th class="matrix-first-col">'.$sGroup.'</th>';

			foreach ($aHeader as $aMatrix) {
				$sCode .= '<td class="text-center">';
				if ($this->checkItemAccess($aMatrix['id'])) {
					$sName	= 'save[access][groups]['.(int)$aMatrix['item_id'].']['.(int)$aMatrix['id'].'][]';
					$sDependencyClass = 'access_'.(int)$aMatrix['item_id'].'_'.(int)$aMatrix['id'];
					$sCode .= '<input type="checkbox" name="'.$sName.'" '.(isset($aMatrix['groups'][$iGroupID]) ? 'checked' : '').' value="'.$iGroupID.'" class="accessGroup" data-user-class="'.$sDependencyClass.'" data-group-id="'.$iGroupID.'">';
				}

				$sCode .= '</td>';
			}


			$sCode .= '</tr>';

		}

		return $sCode;
	}

	/**
	 * Save the access data
	 * 
	 * @param array $aData
	 */
	final public function saveAccessData($aData) {

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create new matrix items

		if(isset($aData['user'][0]))
		{
			foreach((array)$aData['user'][0] as $iItemID => $aUsers)
			{
				$aInsert = array(
					'item_id'	=> $iItemID,
					'item_type'	=> $this->_sType
				);
				$iLastID = DB::insertData($this->_sTablePrefix.'items', $aInsert);

				$aData['user'][$iLastID][$iItemID] = $aUsers;

				if(isset($aData['groups'][0][$iItemID]))
				{
					$aData['groups'][$iLastID][$iItemID] = $aData['groups'][0][$iItemID];

					unset($aData['groups'][0][$iItemID]);
				}
			}

			unset($aData['user'][0], $aData['groups'][0]);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save user ACLs

		foreach((array)$aData['user'] as $iMatrixItemID => $aItems)
		{
			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Remove old assigns

			$sSQL = "
				DELETE FROM
					#table
				WHERE
					`item_id` = :iItemID
			";
			$aSQL = array(
				'iItemID' => $iMatrixItemID,
				'table' => $this->_sTablePrefix.'rights'
			);
			DB::executePreparedQuery($sSQL, $aSQL);

			$sSQL = "
				DELETE FROM
					#table
				WHERE
					`item_id` = :iItemID
			";
			$aSQL = array(
				'iItemID' => $iMatrixItemID,
				'table' => $this->_sTablePrefix.'groups'
			);
			DB::executePreparedQuery($sSQL, $aSQL);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			foreach((array)$aItems as $iItemID => $aUsers)
			{
				foreach((array)$aUsers as $iUserID => $iRight)
				{
					if($iRight == -1)
					{
						continue;
					}

					$aInsert = array(
						'item_id'	=> $iMatrixItemID,
						'user_id'	=> $iUserID,
						'right'		=> $iRight
					);
					DB::insertData($this->_sTablePrefix.'rights', $aInsert);
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Assign the groups

		foreach((array)$aData['groups'] as $iMatrixItemID => $aItems)
		{
			foreach((array)$aItems as $iItemID => $aGroups)
			{
				foreach((array)$aGroups as $iKey => $iGroupID)
				{
					$aInsert = array(
						'item_id'	=> $iMatrixItemID,
						'group_id'	=> $iGroupID
					);
					DB::insertData($this->_sTablePrefix.'groups', $aInsert);
				}
			}
		}

		Ext_TC_User::resetAccessCache();
		
	}

	/* ==================================================================================================== */

	/**
	 * Load the ACL
	 * 
	 * @return array
	 */
	final protected function _getACL() {

		$sSQL = "
			SELECT
				CONCAT(`kamr`.`item_id`, '_', `kamr`.`user_id`) AS `key`,
				`kamr`.`right`
			FROM
				#table AS `kamr`
			WHERE
				1
		";
		$aSQL = array(
			'table' => $this->_sTablePrefix.'rights'
		);
		$aACL = DB::getQueryPairs($sSQL, $aSQL);

		return $aACL;

	}
	
	protected function _manipulateLoadItemsSqlParts(&$aSqlParts, &$aSql) {
		
	}

	/**
	 * Load items
	 */
	final protected function _loadItems() {
		
		$aSql = array(
			'type'						=> $this->_sType,
			'table_items'				=> $this->_sItemTable,
			'table_items_name_field'	=> $this->_sItemNameField,
			'table_items_orderby_field' => $this->_sItemOrderbyField,
			'matrix_table_items'		=> $this->_sTablePrefix.'items',
			'matrix_table_groups'		=> $this->_sTablePrefix.'groups'
		);
		
		$aSqlParts = array();
		$aSqlParts['select'] = "
				`items`.`id`,
				`items`.#table_items_name_field `name`,
				`matrix_table_items`.`id` `item_id`,
				GROUP_CONCAT(`matrix_table_groups`.`group_id` SEPARATOR '|') `groups`
			";
		$aSqlParts['from'] = "
				#table_items AS `items`	LEFT OUTER JOIN
				#matrix_table_items AS `matrix_table_items`		ON
					`matrix_table_items`.`item_type` = :type AND
					`matrix_table_items`.`item_id` = `items`.`id`			LEFT OUTER JOIN
				#matrix_table_groups AS `matrix_table_groups`		ON
					`matrix_table_items`.`id` = `matrix_table_groups`.`item_id`
			";
		$aSqlParts['where'] = "
				`items`.`active` = 1
			";
		$aSqlParts['group_by'] = "
				`items`.`id`
			";
		$aSqlParts['order_by'] = "
				`items`.#table_items_orderby_field
			";

		$this->_manipulateLoadItemsSqlParts($aSqlParts, $aSql);
		
		$sSql = "
			SELECT
				".$aSqlParts['select']."
			FROM
				".$aSqlParts['from']."
			WHERE
				".$aSqlParts['where']."
			GROUP BY
				".$aSqlParts['group_by']."
			ORDER BY
				".$aSqlParts['order_by']."
			";
		
		$aEntries = DB::getPreparedQueryData($sSql, $aSql);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach((array)$aEntries as $iKey => $aValue)
		{
			$aEntries[$iKey]['groups'] = preg_split('/\|/', $aValue['groups'], null, PREG_SPLIT_NO_EMPTY);

			// To make the isset()-requests faster
			$aEntries[$iKey]['groups'] = array_flip($aEntries[$iKey]['groups']);

			// The 'true' value needs for javascript
			foreach((array)$aEntries[$iKey]['groups'] as $iGroupID => $i)
			{
				$aEntries[$iKey]['groups'][$iGroupID] = true;
			}
		}

		$this->aMatrix = array(0 => $aEntries);

	}

	/**
	 * Load system user
	 */
	protected function _loadUser() {

		$aACL = $this->_getACL();

		$aUsers = $this->_getUsers();
		
		foreach((array)$aUsers as $iKey => $aValue)
		{
			$aUsers[$iKey]['groups'] = preg_split('/\|/', $aValue['groups'], -1, PREG_SPLIT_NO_EMPTY);

			// To make the isset()-requests faster
			$aUsers[$iKey]['groups'] = array_flip($aUsers[$iKey]['groups']);

			// The 'true' value needs for javascript
			foreach((array)$aUsers[$iKey]['groups'] as $iGroupID => $i) {
				$aUsers[$iKey]['groups'][$iGroupID] = true;
			}

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Combinate the arrays

		// The header data
		$aHeader = $this->aMatrix[0];

		$n = 1;

		// Each every user
		foreach((array)$aUsers as $iKey => $aUser)
		{
			$aLine = array();

			// Each every item
			foreach((array)$aHeader as $aMatrix)
			{
				if(!isset($aLine[0]))
				{
					$aLine[] = array(
						'firstname'		=> $aUser['firstname'],
						'lastname'		=> $aUser['lastname']
					);
				}

				$aInsert = array(
					'item_link_id'	=> $aMatrix['item_id'],
					'item_id'		=> $aMatrix['id'],
					'user_id'		=> $aUser['user_id'],
					'user_groups'	=> $aUser['groups'],
					'access'		=> -1,
					'right'			=> 0
				);

				// Each every item
				foreach((array)$aMatrix['groups'] as $iGroupID => $i)
				{
					// Wenn Gruppe beim User vorhanden
					if(isset($aUser['groups'][$iGroupID])) {
						$aInsert['access']	= -1;
						$aInsert['right']	= 1;

						break;
					}
				}

				// Wenn User Masteruser ist
				if(in_array($aUser['user_id'], $this->_aMaster)) {
					$aInsert['access']	= -1;
					$aInsert['right']	= 1;
				}
				
				// Explizite Rechte überschreiben vorher gesetzte
				if(isset($aACL[$aMatrix['item_id'] . '_' . $aUser['user_id']]))
				{
					if($aACL[$aMatrix['item_id'] . '_' . $aUser['user_id']] == 1)
					{
						$aInsert['access']	= 1;
						$aInsert['right']	= 1;
					}
					else
					{
						$aInsert['access']	= 0;
						$aInsert['right']	= 0;
					}
				}

				$aLine[] = $aInsert;
			}

			$this->aMatrix[$n++] = $aLine;

		}

	}
	
	protected function _getUsers() {

		$aUsers = Ext_TC_Factory::executeStatic('Ext_TC_User', 'getListWithGroups');

		return $aUsers;
		
	}
	
	/**
	 * Load access groups
	 */
	protected function _loadGroups() {
		
		$aGroups = Ext_TC_Factory::executeStatic('Ext_TC_User_Group', 'getList');

		$this->aGroups = $aGroups;

	}

	private function checkItemAccess($iItemId) {

		if(
			in_array($this->_iUserID, $this->_aMaster) ||
			array_key_exists($iItemId, $this->_aItems) ||
			Access::getInstance()->hasRight($this->aRight)
		) {
			return true;
		}

		return false;

	}
	
}