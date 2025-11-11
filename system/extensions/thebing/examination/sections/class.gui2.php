<?php

class Ext_Thebing_Examination_Sections_Gui2 extends Ext_Thebing_Gui2_Data{

	public function getOptionGui() {

		$oInnerGui = $this->_oGui->createChildGui(md5('thebing_tuition_examination_sections_options'));
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'keso';
		$oInnerGui->foreign_key			= 'field_id';
		$oInnerGui->foreign_key_alias	= 'keso';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;
		$oInnerGui->column_sortable		= 0;
		$oInnerGui->row_sortable		= 1;

		$oInnerGui->setWDBasic('Ext_Thebing_Examination_Sections_Option');
		//$oInnerGui->setTableData('where', array('kfsfov.lang_id'=>$sInterfaceLanguage));

		// Dialog
		$oDialog					= $oInnerGui->createDialog($oInnerGui->t('Option "{title}" editieren'), $oInnerGui->t('Neue Option anlegen'));
		$oDialog->width				= 900;
		$oDialog->height			= 650;

		$oDialog->save_as_new_button	= true;
		$oDialog->save_bar_options		= true;
		$oDialog->save_bar_default_option = 'close';
 

		$oDialog->setElement($oDialog->createRow($oInnerGui->t('Titel'), 'input', array(
				'db_alias'			=> '',
				'db_column'			=> 'title',
				'required'			=> 1
		)));
		

		// Buttons
		$oBar			= $oInnerGui->createBar();
		$oBar->width	= '100%';
		/*$oLabelGroup	= $oBar->createLabelGroup($oInnerGui->t('Aktionen'));
		$oBar->setElement($oLabelGroup);*/
		$oIcon			= $oBar->createNewIcon($oInnerGui->t('Neuer Eintrag'), $oDialog, $oInnerGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($oInnerGui->t('Editieren'), $oDialog, $oInnerGui->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($oInnerGui->t('Löschen'), $oInnerGui->t('Löschen'));
		$oBar->setElement($oIcon);
		$oInnerGui->setBar($oBar);

		# START - Leiste 3 #
			$oBar = $oInnerGui->createBar();
			$oBar->width = '100%';
			$oBar->position = 'top';

			$oPagination = $oBar->createPagination(true);
			$oBar ->setElement($oPagination);

			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'title';
		$oColumn->db_alias		= 'keso';
		$oColumn->title			= $this->t('Titel');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oInnerGui->setColumn($oColumn);

		$oInnerGui->addDefaultColumns();
		
		return $oInnerGui;
	}

}