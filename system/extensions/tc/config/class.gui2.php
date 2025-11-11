<?php

class Ext_TC_Config_Gui2 extends Ext_TC_Gui2 {

	public $aConfigurations = array();

	protected $bFirstDisplay = true;

	/**
	 * @TODO Sollte zu setOption geändert werden
	 */
	public function setConfigurations($aConfigs) {
		$this->aConfigurations = $aConfigs;
	}
	
	public function display($aOptionalData = array(), $bNoJavaScript=false)
	{
		if ($this->bFirstDisplay) {
			$sRight = $this->getOption('right', 'core_config');

			$this->multiple_selection = 0;
			$this->query_id_column = 'key';
			$this->encode_data = array('key');
			$this->include_jquery = true;
			$this->include_jquery_multiselect = true;
			$this->access = ''; // ['', ''] ist immer false in (Thebing?) hasRight

			$oDialog = $this->createDialog($this->t('Einstellung bearbeiten'));

			if ($sRight) {
				$this->access = [$sRight, ''];
				$oDialog->access = [$sRight, 'edit'];
			}

			$oBar = $this->createBar();
			$oBar->width = '100%';
			$oIcon = $oBar->createEditIcon($this->t('Editieren'), $oDialog, $this->t('Editieren'));
			if ($sRight) {
				$oIcon->access = [[$sRight, 'show'], [$sRight, 'edit']];
			}

			$oBar->setElement($oIcon);
			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);
			$this->setBar($oBar);

			$oColumn = $this->createColumn();
			$oColumn->db_column = 'description';
			$oColumn->db_alias = '';
			$oColumn->title = $this->t('Konfiguration');
			$oColumn->width = Ext_TC_Util::getTableColumnWidth('long_description');
			$oColumn->width_resize = false;
			$this->setColumn($oColumn);

			$oColumn = $this->createColumn();
			$oColumn->db_column = 'value';
			$oColumn->db_alias = '';
			$oColumn->title = $this->t('Wert');
			$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->width_resize = true;
			$this->setColumn($oColumn);

			$this->bFirstDisplay = false;
		}
		
		parent::display($aOptionalData, $bNoJavaScript);
		
	}

}
