<?php

class Ext_TC_Gui2_Filterset_Bar extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_gui2_filtersets_bars';
	
	protected $_sTableAlias = 'tc_gfb';
    
	protected $_aJoinTables = array(
        'usergroups' => array(
            'table' => 'tc_gui2_filtersets_bars_to_usergroups',
            'foreign_key_field' => 'group_id',
            'primary_key_field' => 'bar_id',
            'class' => 'Ext_TC_User_Group',
            'autoload' => true,
	 		'cloneable' => true
        )
    );
    
    protected $_aJoinedObjects = array(
        'elements'=> array(
	 			'class'=>'Ext_TC_Gui2_Filterset_Bar_Element',
	 			'key'=>'bar_id',
	 			'type'=>'child',
	 			'check_active'=>true,
	 			'cloneable' => true,
				'on_delete' => 'cascade',
	 			'orderby'=>'position',
	 			'orderby_set'=>true
	 		)
    );
 
    /**
     * get all elements
     * @return Ext_TC_Gui2_Filterset_Bar_Element[]
     */
    public function getElements(){
        $aBars = (array)$this->getJoinedObjectChilds('elements');
        return $aBars;
    }

    /**
     * get the Basic Dialog for this WDBasic
     * @param Ext_TC_Gui2 $oGui
     * @return Ext_Gui2_Dialog
     */
    public static function getDialog(Ext_TC_Gui2 $oGui){
        global $user_data;
		//todo sauber lösen ggf. mit Factory
        if($user_data['client'] > 0){
			$oAccess = Ext_Thebing_Access::getInstance();
			$aUsergroups = $oAccess->getAccessGroups();
		} else {
			$aUsergroups = Ext_TC_User_Group::getList();
		}
        
        $oDialog = $oGui->createDialog($oGui->t('Zeile {name} bearbeiten'), $oGui->t('Neuer Zeile'));
        
        $oRow = $oDialog->createRow($oGui->t('Name'), 'input', array('db_column' => 'name', 'db_alias' => 'tc_gfb'));
        $oDialog->setElement($oRow);
        
        $oRow = $oDialog->createRow($oGui->t('Benutzergruppen'), 'select', array(
            'db_column' => 'usergroups', 
            'db_alias' => 'tc_gfb', 
            'select_options' => $aUsergroups, 
            'required' => true,
            'multiple' => 5, 
            'jquery_multiple' => 1,
            'searchable' => 1
            )
        );
        $oDialog->setElement($oRow);
        
        return $oDialog;
    }
}