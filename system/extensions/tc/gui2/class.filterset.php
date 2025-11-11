<?php

class Ext_TC_Gui2_Filterset extends Ext_TC_Basic {

	const TRANSLATION_PATH = 'GUI';

	// Tabellenname
	protected $_sTable = 'tc_gui2_filtersets';
	
	protected $_sTableAlias = 'tc_gf';
   
	protected $_aJoinedObjects = array(
        'bars'=> array(
	 			'class'=>'Ext_TC_Gui2_Filterset_Bar',
	 			'key'=>'set_id',
	 			'type'=>'child',
	 			'check_active'=>true,
	 			'cloneable' => true,
				'on_delete' => 'cascade',
				'orderby'=>'position',
	 		)
    );

    /**
     * get all bars
     * @return Ext_TC_Gui2_Filterset_Bar[]
     */
    public function getBars(){
        $aBars = (array)$this->getJoinedObjectChilds('bars');
        return $aBars;
    }
    
    /**
     * get the Basic Dialog for this WDBasic
     * @param Ext_TC_Gui2 $oGui
     * @return Ext_Gui2_Dialog
     */
    public static function getDialog(Ext_TC_Gui2 $oGui){
        
        $aApplications = self::getApplications();
        
        $oDialog = $oGui->createDialog($oGui->t('Filterset "{name}" editieren'), $oGui->t('Neuer Filterset'));
        
        $oTab = $oDialog->createTab($oGui->t('Informationen'));
        
        $oRow = $oDialog->createRow($oGui->t('Name'), 'input', array('db_column' => 'name', 'db_alias' => 'tc_gf'));
        $oTab->setElement($oRow);
        
        $oRow = $oDialog->createRow($oGui->t('Verwendung'), 'select', array(
            'db_column' => 'application', 
            'db_alias' => 'tc_gf', 
            'select_options' => $aApplications, 
            'required' => true,
            'selection' => new Ext_TC_Gui2_Filterset_Selection_Application()
            )
        );
        $oTab->setElement($oRow);
        
        $oDialog->setElement($oTab);
        
        ##
        ## INNER GUIS
        ##
        
        $oTab = $oDialog->createTab($oGui->t('Filterzeilen'));
        
        $oPage = new Ext_Gui2_Page();
        
        $oGenerator = new Ext_Gui2_Factory('tc_gui_filtersets_bars');
        $oBars = $oGenerator->createGui();
        $oPage->setGui($oBars, array('hash' => $oGui->hash, 'foreign_key' => 'set_id',  'parent_primary_key' => 'id', 'reload' => true));
        
        $oGenerator = new Ext_Gui2_Factory('tc_gui_filtersets_bars_elements');
        $oElements = $oGenerator->createGui();
		$oElements->gui_title = 'Filter';
        $oPage->setGui($oElements, array('hash' => $oBars->hash, 'foreign_key' => 'bar_id',  'parent_primary_key' => 'id', 'reload' => true));
        $oTab->setElement($oPage);
        $oDialog->setElement($oTab);
        ##
        ##
        ##
        
        return $oDialog;
    }
    
    /**
     * get all availaible Applications for filtersets
     * @return array 
     */
    public static function getApplications(){
		$aApplications = array();
        return $aApplications;
    }
    
    /**
     * search an filterset by the given application
     * @param string $sApplication
     * @return Ext_TC_Gui2_Filterset 
     */
    public static function search($sApplication){
        $sSql = " SELECT 
                        `id` 
                  FROM 
                    #table 
                  WHERE 
                    `application` = :application AND 
                    `active` = 1 
                    LIMIT 1
                  ";
        $aSql       = array('table' => 'tc_gui2_filtersets', 'application' => $sApplication);
        $iId        = (int)DB::getQueryOne($sSql, $aSql);

        $oSelf      = self::getInstance($iId);
        
        return $oSelf;
    }
}