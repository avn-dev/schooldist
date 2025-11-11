<?php

/**
 * @TODO Entfernen – Wird scheinbar nur noch für Stornobedingungen-Tab in Schule und Agentur benutzt
 */
class Ext_Thebing_Validity
{
	protected $_oGui;
	protected $_sParentType;
	protected $_sItemType;
	protected $_sItemTitle = 'Item';

	public function __construct(&$oGui, $sParentType, $sItemType)
	{
		$this->_oGui			= $oGui;
		$this->_sParentType		= $sParentType;
		$this->_sItemType		= $sItemType;
	}

	public function setItemTitle($sItemTitle)
	{
		$this->_sItemTitle = $sItemTitle;
	}

	public function getValidityGui()
	{
		$oValidityGui = new Ext_Thebing_Gui2(md5('thebing_validity'), 'Ext_Thebing_Validity_Gui2');
		$oValidityGui->query_id_column		= 'id';
		$oValidityGui->query_id_alias		= 'kv';
		$oValidityGui->foreign_key			= 'parent_id';
		$oValidityGui->foreign_key_alias	= 'kv';
		$oValidityGui->parent_hash			= $this->_oGui->hash;
		$oValidityGui->parent_primary_key	= 'id';
		$oValidityGui->load_admin_header	= false;
		$oValidityGui->multiple_selection	= false;
		$oValidityGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Validity();

		if(empty($oValidityGui->gui_description)) {
			$oValidityGui->gui_description = $this->_oGui->gui_description;
		}

		// Neu Anlegen
		$oValidityDialogNew					= $oValidityGui->createDialog($oValidityGui->t('Neuen Eintrag anlegen'), $oValidityGui->t('Neuen Eintrag anlegen'));
		$oValidityDialogNew->sDialogIDTag	= 'VALIDITY_';

		$oValidityDialogNew->setElement($oValidityDialogNew->createRow($this->t($this->_sItemTitle), 'select', array(
			'db_alias'			=> 'kv',
			'db_column'			=> 'item_id',
			'select_options'	=> array(),
			'required'			=> true
		)));

		$oValidityDialogNew->setElement($oValidityDialogNew->createRow($this->t('Bezeichnung'), 'input', array(
			'db_alias'			=> 'kv',
			'db_column'			=> 'description',
			'select_options'	=> array(),
			'required'			=> true
		)));

		$oValidityDialogNew->setElement($oValidityDialogNew->createRow($this->t('Gültig ab'), 'calendar', array(
			'db_alias'=>'kv',
			'db_column'=>'valid_from',
			'format'=>new Ext_Thebing_Gui2_Format_Date(),
			'required'=>true
		)));

		$oValidityDialogNew->setElement($oValidityDialogNew->createRow($this->t('Kommentar'), 'textarea', array(
			'db_alias'=>'kv',
			'db_column'=>'comment'
		)));

		$oValidityDialogNew->width = 850;
		$oValidityDialogNew->height = 300;

		// Editieren
		$oValidityDialogEdit					= $oValidityGui->createDialog($oValidityGui->t('Eintrag "{description}" editieren'), $oValidityGui->t('Eintrag "{description}" editieren'));
		$oValidityDialogEdit->sDialogIDTag	= 'VALIDITY_';

		$oValidityDialogEdit->setElement($oValidityDialogEdit->createRow($this->t($this->_sItemTitle), 'select', array(
			'db_alias'			=> 'kv',
			'db_column'			=> 'item_id',
			'select_options'	=> array(),
			'required'			=> true
		)));

		$oValidityDialogEdit->setElement($oValidityDialogEdit->createRow($this->t('Bezeichnung'), 'input', array(
			'db_alias'			=> 'kv',
			'db_column'			=> 'description',
			'select_options'	=> array(),
			'required'			=> true
		)));

		$oValidityDialogEdit->setElement($oValidityDialogEdit->createRow($this->t('Kommentar'), 'textarea', array(
			'db_alias'=>'kv',
			'db_column'=>'comment'
		)));

		$oValidityDialogEdit->width = 850;
		$oValidityDialogEdit->height = 300;

		$oValidityGui->setWDBasic('Ext_Thebing_Validity_WDBasic');
		$oValidityGui->setTableData('where', array(
			'kv.active'		=> 1,
			'kv.parent_type'	=> $this->_sParentType,
			'kv.item_type'		=> $this->_sItemType,
		));
		$oValidityGui->setTableData('limit', 30);
		$oValidityGui->setTableData('orderby', array('valid_from'=>'DESC'));

		// Buttons
		$oBar			= $oValidityGui->createBar();
		$oBar->width	= '100%';
		/*$oLabelGroup	= $oBar->createLabelGroup($this->t('Bearbeiten'));
		$oBar->setElement($oLabelGroup);*/
		
		if(
			(
				$this->_sParentType == 'school' &&
				Ext_Thebing_Access::hasRight("thebing_admin_schools")
			) ||
			(
				$this->_sParentType == 'agency' &&
				Ext_Thebing_Access::hasRight("thebing_marketing_agencies_edit")
			)
		) {
						
			$oIcon			= $oBar->createNewIcon($this->t('Neuer Eintrag'), $oValidityDialogNew, $this->t('Neuer Eintrag'));
			$oBar->setElement($oIcon);
			$oIcon			= $oBar->createEditIcon($this->t('Editieren'), $oValidityDialogEdit, $this->t('Editieren'));
			$oBar->setElement($oIcon);
			$oIcon			= $oBar->createDeleteIcon($this->t('Löschen'), $this->t('Löschen'));
			$oBar->setElement($oIcon);
		
		} else {
			$oValidityDialogEdit->bReadOnly = true;

			$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('info'), 'openDialog', $this->_oGui->t('Anzeigen'));
			$oIcon->action = 'edit';
			$oIcon->label = $this->_oGui->t('Anzeigen');
			$oIcon->dialog_data = $oValidityDialogEdit;
			$oBar ->setElement($oIcon);	
		}
		
		$oValidityGui->setBar($oBar);

		// Paginator
		$oBar						= $oValidityGui->createBar();
		$oBar->width				= '100%';
		$oBar->position				= 'top';
		$oPagination				= $oBar->createPagination();
		$oBar->setElement($oPagination);
		$oLoading					= $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oValidityGui->setBar($oBar);

		$oColumn				= $oValidityGui->createColumn();
		$oColumn->db_column		= 'item_title';
		$oColumn->db_alias		= 'kv';
		$oColumn->title			= $this->t($this->_sItemTitle);
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oValidityGui->setColumn($oColumn);

		$oColumn				= $oValidityGui->createColumn();
		$oColumn->db_column		= 'description';
		$oColumn->db_alias		= 'kv';
		$oColumn->title			= $this->t('Bezeichnung');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oValidityGui->setColumn($oColumn);

		$oColumn				= $oValidityGui->createColumn();
		$oColumn->db_column		= 'valid_from';
		$oColumn->db_alias		= 'kv';
		$oColumn->title			= $this->t('Gültig ab');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oValidityGui->setColumn($oColumn);

		$oColumn				= $oValidityGui->createColumn();
		$oColumn->db_column		= 'valid_until';
		$oColumn->db_alias		= 'kv';
		$oColumn->title			= $this->t('Gültig bis');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oValidityGui->setColumn($oColumn);

		$oColumn				= $oValidityGui->createColumn();
		$oColumn->db_column		= 'comment';
		$oColumn->db_alias		= 'kv';
		$oColumn->title			= $this->t('Kommentar');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('comment');
		$oColumn->width_resize	= false;
		$oValidityGui->setColumn($oColumn);

		$oValidityGui->addDefaultColumns();

		return $oValidityGui;
	}

	public function t($sTranslate)
	{
		return $this->_oGui->t($sTranslate);
	}

}
