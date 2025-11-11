<?php

/**
 *
 * generiert layout innerhalb eines Dialogfensters ( zweiter Tab )
 *
 * @author Christian Felix
 * @created 24.01.11
 *
 */
class Ext_Thebing_Tuition_Module_Gui2 extends Ext_Thebing_Gui2_Data{

	/**
	 *  Das Layout und Daten des zweiten Tabs werden hier generiert
	 * 
	 */
	public function getLevelGui() {

		$sInterfaceLanguage = $this->_oGui->getOption('interface_language');
		$aSchoolLanguages	= $this->_oGui->getOption('school_languages');

		
		$oGui = new Ext_Thebing_Gui2(md5('thebing_tuition_modules_levels'));
		$oGui->gui_description		= $this->_oGui->gui_description;
		$oGui->query_id_column		= 'id';
		$oGui->query_id_alias		= 'ktml';
		$oGui->foreign_key			= 'module_id';
		$oGui->foreign_key_alias	= 'ktml';
		$oGui->parent_primary_key	= 'id';
		$oGui->parent_hash			= $this->_oGui->hash;
		$oGui->load_admin_header	= false;
		$oGui->multiple_selection	= false;

		$oGui->setWDBasic('Ext_Thebing_Tuition_Module_Level');
		$oGui->setTableData('where', array('active' => 1));
		$oGui->setTableData('limit', 30);
		$oGui->setTableData('orderby', array('name_'.$sInterfaceLanguage => 'ASC'));

		$oBar = $oGui->createBar();
		$oBar->width = '100%';
	

		//Dialogbox: Anlegen von neuen Levels/Niveaus

		$sName = $oGui->t('Niveau "{name}" bearbeiten');
		$sName = str_replace("{name}", "{name_".$sInterfaceLanguage."}", $sName);
		$oDialog = $oGui->createDialog($sName, $oGui->t('Niveau anlegen')); //todo
		$oDialog->width = 700;
		$oDialog->height = 300;


		foreach((array)$aSchoolLanguages as $sCode	=>	$sLanguage)
		{
			$oDialog->setElement($oDialog->createRow($oGui->t("Name"). ' ('.$sLanguage.')', 'input', array(
				'db_alias'	=>	'ktml',
				'db_column'	=>	'name_'.$sCode,
				'required'	=>	1
			)));

			//$aSearchFields[] = 'name_' . $sCode;  //todo: suchfeld
		}
	
		//Aktionen (Anlegen/Edit/Löschen) werden weitere benötigt?
		/*$oLabel		 = $oBar->createLabelGroup($oGui->t('Aktionen'));
		$oBar->setElement($oLabel);*/

		$oIconNew	 = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
		$oIconEdit	 = $oBar->createEditIcon($oGui->t('Editieren'), $oDialog, $oGui->t('Editieren'));
		$oIconDelete = $oBar->createDeleteIcon($oGui->t('Löschen'),$oGui->t('Löschen'));

		$oBar->setElement($oIconNew);
		$oBar->setElement($oIconEdit);
		$oBar->setElement($oIconDelete);

		$oGui->setBar($oBar);


		//Paginator
		$oBar							= $oGui->createBar();
		$oBar->width					= '100%';
		$oBar->position					= 'top';
		$oPagination					= $oBar->createPagination();
		$oBar->setElement($oPagination);
		$oLoading                       = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

	
		//$oGui->

		//Columns

		$oColumn					 = $oGui->createColumn();
		$oColumn->db_column		  	 = 'name_'.$sInterfaceLanguage;
		$oColumn->title			  	 = $oGui->t('Name');
		$oColumn->width				 = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize		 = true;
		$oGui->setColumn($oColumn);

		$oGui->addDefaultColumns();  // add default columns

		return $oGui;
	}

}