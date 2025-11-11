<?php

class Ext_TC_Gui2_DefaultColumn {

	/**
	 * @var array
	 */
	protected $_aColumns = array();

	protected $sAlias;

	public function __construct($sAlias=null) {

		if(!empty($sAlias)) {
			$this->sAlias = $sAlias;
		}

		$this->_initDefaultColumns();
	}

	protected function getColumnObject() {

		$oColumn = new Ext_Gui2_Head();

		if($this->sAlias !== null) {
			$oColumn->db_alias = $this->sAlias;
		}

		return $oColumn;
	}

	/**
	 * Default Werte vorbereiten
	 */
	protected function _initDefaultColumns() {

		$oColumn = $this->getColumnObject();
		$oColumn->db_column	= 'creator_id';
		$oColumn->title	= L10N::t('Ersteller');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('user_name');
		$oColumn->format = new Ext_Gui2_View_Format_UserName(true);
		$oColumn->sortable = false;
		$oColumn->default = false;
		$this->_setColumn($oColumn,'creator_id');

		$oColumn = $this->getColumnObject();
		$oColumn->db_column = 'created';
		$oColumn->db_type = 'timestamp';
		$oColumn->title = L10N::t('Erstellt');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('date');
		$oColumn->format = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date_DateTime');
		$oColumn->default = false;
		$this->_setColumn($oColumn,'created');

		$oColumn = $this->getColumnObject();
		$oColumn->db_column = 'editor_id';
		$oColumn->title = L10N::t('Bearbeiter');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('user_name');
		$oColumn->format = new Ext_Gui2_View_Format_UserName(true);
		$oColumn->sortable = false;
		$oColumn->default = false;
		$this->_setColumn($oColumn,'editor_id');

		$oColumn = $this->getColumnObject();
		$oColumn->db_column = 'changed';
		$oColumn->db_type = 'timestamp';
		$oColumn->title = L10N::t('Bearbeitet');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('date');
		$oColumn->format = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date_DateTime');
		$oColumn->default = false;
		$this->_setColumn($oColumn,'changed');

	}

	/**
	 * @param $oColumn
	 * @param string $sKey
	 */
	protected function _setColumn($oColumn,$sKey) {
		$this->_aColumns[$sKey] = $oColumn;
	}

	/**
	 * Überschreiben der Standardwerte, wenn $sColumn 'all' ist werden alle überschrieben
	 *
	 * @param string $sColumn
	 * @param array $aConfig
	 */
	public function changeDefaultConfig($sColumn, $aConfig) {

		if($sColumn == 'all') {

			foreach($this->_aColumns as $sKey => $oColumn) {
				$this->setConfig($sKey, $aConfig);
			}

		} else {
			$this->setConfig($sColumn, $aConfig);
		}

	}

	/**
	 * Configwerte setzen
	 *
	 * @param string $sColumn
	 * @param array $aConfig
	 */
	public function setConfig($sColumn, $aConfig) {

		if(isset($this->_aColumns[$sColumn])) {

			$oColumn = $this->_aColumns[$sColumn];

			foreach($aConfig as $sKey => $mValue) {
				if($mValue !== null) {
					$oColumn->$sKey = $mValue;
				}
			}

			$this->_setColumn($oColumn,$sColumn);

		}

	}

	/**
	 * Bearbeiter und Ersteller ausblenden
	 */
	public function hideCreatedFields() {

		$this->unsetColumn('created');
		$this->unsetColumn('creator_id');

	}

	/**
	 * Felder ausblenden
	 * @param string $sKey
	 */
	public function unsetColumn($sKey) {

		if(isset($this->_aColumns[$sKey])) {
			unset($this->_aColumns[$sKey]);
		}

	}

	/**
	 * Wenn ohne join zu der Usertabelle nur die UserId zur Verfügung steht
	 */
	public function getSystemUsersById() {

		// Flag auf true setzen damit die UserObjekte geladen werden
		$oFormatUser = new Ext_Gui2_View_Format_UserName(true);

		$this->changeDefaultConfig('editor_id', array(
			'format' => $oFormatUser,
			'sortable' => 0 // nicht sortiertbar, sonst werden die ID's sortiert und nicht die Namen
		));

		$this->changeDefaultConfig('creator_id', array(
			'sortable' => 0 // nicht sortiertbar, sonst werden die ID's sortiert und nicht die Namen
		));

	}

	/**
	 * Alle Standardspalten eine Gruppe zuweisen
	 *
	 * @param $oGroup
	 */
	public function setColGroupForAll($oGroup) {

		$this->changeDefaultConfig('all', array(
			'group' => $oGroup
		));

	}

	/**
	 * alle Standardspalten einen Alias zuweisen
	 * @param string $sAlias
	 */
	public function setAliasForAll($sAlias) {

		$this->changeDefaultConfig('all', array(
			'db_alias' => $sAlias
		));

	}

	/**
	 * editor_id Spalte verändern
	 *
	 * @param string $sNewUserIdDbColumn
	*/
	public function changeEditorIdDbColumn($sNewUserIdDbColumn) {

		$this->changeDefaultConfig('editor_id', array(
			'db_column' => $sNewUserIdDbColumn
		));

	}

	/**
	 * @return array
	 */
	public function getColumns() {
		return $this->_aColumns;
	}

	/**
	 * @param string $sDbAlias
	 */
	public function addValidUntilColumn($sDbAlias='') {
		
		$oDateFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
		
		$oColumn = new Ext_Gui2_Head();
		$oColumn->db_column	= 'valid_until';
		$oColumn->db_alias = $sDbAlias;
		$oColumn->title = L10N::t('Gültig bis');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('date');
		$oColumn->format = $oDateFormat;
		$oColumn->default = false;
		$this->_setColumn($oColumn,'valid_until');

	}

}