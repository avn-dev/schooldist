<?php

class Ext_Thebing_Gui2_DefaultColumn extends Ext_TC_Gui2_DefaultColumn {

	public function __construct($sAlias=null, private bool $useEditorId = false) {

		parent::__construct($sAlias);
	}

	/**
	 * Default Werte vorbereiten
	 */
	protected function _initDefaultColumns() {

		$oColumn = $this->getColumnObject();
		$oColumn->db_column		= 'creator_id';
		$oColumn->title			= L10N::t('Ersteller');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->format		= new Ext_Gui2_View_Format_UserName(true);
		$oColumn->default = false;
		$this->_setColumn($oColumn,'creator_id');

		$oColumn = $this->getColumnObject();
		$oColumn->db_column		= 'created';
		$oColumn->db_type		= 'timestamp';
		$oColumn->title			= L10N::t('Erstellt');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date_DateTime();
		$oColumn->default = false;
		$this->_setColumn($oColumn,'created');

		$oColumn = $this->getColumnObject();
		if($this->useEditorId) {
			$oColumn->db_column = 'editor_id';
		} else {
			$oColumn->db_column		= 'user_id';//Schule benutzt noch als db_column user_id
		}
		$oColumn->title			= L10N::t('Bearbeiter');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->format		= new Ext_Gui2_View_Format_UserName(true);
		$oColumn->default = false;
		$this->_setColumn($oColumn,'editor_id');//key muss aber editor_id bleiben

		$oColumn = $this->getColumnObject();
		$oColumn->db_column		= 'changed';
		$oColumn->db_type		= 'timestamp';
		$oColumn->title			= L10N::t('Verändert');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date_DateTime();
		$oColumn->default = false;
		$this->_setColumn($oColumn,'changed');

	}

}