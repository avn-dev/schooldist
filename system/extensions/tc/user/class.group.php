<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TC_User_Group extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_system_user_groups';

	/**
	 * Eine Liste mit Verknüpfungen (1-n)
	 *
	 * array(
	 *		'items'=>array(
	 *				'table'=>'',
	 *				'foreign_key_field'=>'',
	 *				'primary_key_field'=>'id',
	 *				'sort_column'=>'',
	 *				'class'=>'',
	 *				'autoload'=>true,
	 *				'check_active'=>true,
	 *				'delete_check'=>false,
	 *				'static_key_fields'=>array()
	 *			)
	 * )
	 *
	 * foreign_key_field kann auch ein Array sein
	 *
	 * @var <array>
	 */
	protected $_aJoinTables = array(
			'access' => array(
				'table' => 'tc_system_user_groups_to_access',
	 			'foreign_key_field' => 'access_id',
				'primary_key_field' => 'group_id'
			)
		);
	

	
	public static function getDialog($oGui, $bNew = true){

		$oDialog = $oGui->createDialog($oGui->t('Gruppe {name} bearbeiten'), $oGui->t('Neue Gruppe anlegen'));
		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array('db_column' => 'name', 'required' => 1)));
		$oDialog->save_as_new_button  = true;
		$oDialog->save_bar_options   = true;
		$oDialog->save_bar_default_option = 'open';

		return $oDialog;
	}
	
	public static function getGui($sSystem = 'TC'){
		
		$oGui						= new Ext_TC_Gui2(md5('tc_admin_user_group'), 'Ext_TC_User_Group_Gui2');
				
		if($sSystem == 'TA'){
			$oGui->gui_description		= Ext_TA_System_Navigation::tp();
			$oGui->gui_title			= Ext_TA_System_Navigation::t();	
		} else if($sSystem == 'TS'){
			$oGui->gui_description		= Ext_TS_System_Navigation::tp();
			$oGui->gui_title			= Ext_TS_System_Navigation::t();	
		} else {
			$oGui->gui_description		= Ext_TC_System_Navigation::tp();
			$oGui->gui_title			= Ext_TC_System_Navigation::t();	
		}
		
		$oGui->multiple_selection	= 0;
		$oGui->class_js				= 'ModuleGUI';
		$oGui->access				= array('core_admin_usergroup', '');
		$oGui->setWDBasic('Ext_TC_User_Group');

		$oDialogNew = Ext_TC_User_Group::getDialog($oGui);
		$oDialogEdit = Ext_TC_User_Group::getDialog($oGui);
		$oDialogEdit->access = array('core_admin_usergroup', 'edit');
		$oDialogAccess = Ext_TC_User_Gui2::getAccessDialog($oGui, array(), true);

		// Buttons
		$oBar			= $oGui->createBar();

		$oIcon			= $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialogNew, $oGui->t('Neuer Eintrag'));
		$oIcon->access = array('core_admin_usergroup', 'new');
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($oGui->t('Editieren'), $oDialogEdit, $oGui->t('Editieren'));
		$oIcon->access = array(
			array('core_admin_usergroup', 'show'),
			array('core_admin_usergroup', 'edit')
		);
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oIcon->access = array('core_admin_usergroup', 'delete');
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('access'), 'openDialog', $oGui->t('Rechte bearbeiten'), $oGui->t('Rechte bearbeiten'));
		$oIcon->label = $oIcon->title;
		$oIcon->access = array('core_admin_usergroup', 'access');
		$oIcon->dialog_data = $oDialogAccess;
		$oIcon->action = 'access';
		$oBar->setElement($oIcon);
		$oGui->setBar($oBar);

		// Paginator
		$oBar			= $oGui->createBar();
		$oBar->position	= 'top';
		$oPagination	= $oBar->createPagination();
		$oBar->setElement($oPagination);
		$oLoading		= $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

		//Spalten
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'name';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oGui->t('Name');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oGui->setColumn($oColumn);
		$oGui->addDefaultColumns();
		
		return $oGui;
	}

	public static function getList() {
		
		$oUserGroup = new self();
		$aGroups = $oUserGroup->getArrayList(true);

		return $aGroups;
	}
    
    /**
     * check if the group has the given access of the given section
     * @param string $sSection
     * @param string $sAccess
     * @return boolean 
     */
    public function checkAccess($sSection, $sAccess){
        
        $iCount = 0;
        
        if(
          !empty($sSection) &&
          !empty($sAccess)
        ){
            // schauen ob genau das gewünschte recht da ist
            $sSql = " SELECT COUNT(*) FROM 
                        `tc_system_user_groups_to_access` `tc_sug_to_a` INNER JOIN
                        `tc_access_sections` `tc_as` ON
                            `tc_as`.`key` = :section INNER JOIN 
                        `tc_access` `tc_a` ON
                            `tc_a`.`key` = :access AND
                            `tc_a`.`section_id` = `tc_as`.`id`  AND
                            `tc_a`.`id` = `tc_sug_to_a`.`access_id` 
                    WHERE
                        `tc_sug_to_a`.`group_id` = :group_id
                    ";
            $aSql = array(
                        'group_id' => $this->id,
                        'section' => $sSection,
                        'access' => $sAccess
                    );
            $aResult    = DB::getPreparedQueryData($sSql, $aSql);
            $aResult    = reset($aResult);
            $iCount     = (int)reset($aResult);
        } else {
            // Nur Section prüfen und schauen ob mind. 1 Recht der section da ist
            $sSql = " SELECT COUNT(*) FROM 
                        `tc_system_user_groups_to_access` `tc_sug_to_a` INNER JOIN
                        `tc_access_sections` `tc_as` ON
                            `tc_as`.`key` = :section INNER JOIN 
                        `tc_access` `tc_a` ON
                            `tc_a`.`section_id` = `tc_as`.`id`  AND
                            `tc_a`.`id` = `tc_sug_to_a`.`access_id` 
                    WHERE
                        `tc_sug_to_a`.`group_id` = :group_id
                    ";
            $aSql = array(
                        'group_id' => $this->id,
                        'section' => $sSection
                    );
            $aResult    = DB::getPreparedQueryData($sSql, $aSql);
            $aResult    = reset($aResult);
            $iCount     = (int)reset($aResult);
        }
   
        if($iCount > 0){
            return true;
        }
        return false;
    }
	
}