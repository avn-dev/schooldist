<?php

class Ext_TC_Referrer_Gui2_Data extends Ext_TC_Gui2_Data {

    public function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if($sError === 'REFERRER_IN_USE') {
			$sMessage = $this->t('Der ausgewählte Eintrag wird noch bei Buchungen oder Anfragen verwendet.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}
    public static function getDialog(Ext_TC_Gui2 $oGui)
    {
        $aLanguages	= Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');

        $sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');
        $aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));

        $oWDBasic = Ext_TC_Factory::getClassName('Ext_TC_Referrer');

        $aSubObjectLanguages = Ext_TC_Referrer::getSubObjectsLanguages('tc_r_i18n');

        $oDialog = $oGui->createDialog($oGui->t('Referenz editieren'), $oGui->t('Referenz anlegen'));
        $oDialog->save_as_new_button = true;
        $oDialog->save_bar_options = true;
        $oDialog->aOptions['section'] = 'admin_referrers';

        $oDialog->setElement($oDialog->createRow($sSubObjectLabel, 'select', array(
            'db_alias' => 'tc_r',
            'db_column' => 'objects',
            'multiple' => 5,
            'jquery_multiple' => 1,
            'select_options' => $aSubObjects,
            'required' => 1,
            'searchable' => 1,
            'child_visibility' => $aSubObjectLanguages
        )));

        $oDialog->setElement($oDialog->createI18NRow($oGui->t('Name'), array(
            'db_alias' => 'tc_r_i18n',
            'db_column'=> 'name',
            'i18n_parent_column' => 'referrer_id',
            'required' => true
        ), $aLanguages));

        if(Ext_TC_Util::getSystem() === 'agency') {

            $oDialog->setElement($oDialog->createRow($oGui->t('Textfeld'), 'checkbox', array(
                'set' => 'agency',
				'db_alias' => 'tc_r',
                'db_column' => 'textfields',
                'child_visibility' => array(
                    array(
                        'id' => 'joinedobjectcontainer_fields',
                        'on_values' => array(
                            '1'
                        )
                    )
                )
            )));

            $oJoinContainer = $oDialog->createJoinedObjectContainer('fields', array('min' => 1, 'max' => 20));

            $oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Feld'), 'select', array(
                'db_alias' => 'tc_rf',
                'db_column' => 'field',
                'selection' => new Ext_TC_Referrer_Selection_Field(),
                'class'	=> 'field_select',
                'dependency' => array(
                    array(
                        'db_alias' => 'tc_rf',
                        'db_column' => 'field'
                    )
                ),
                'events' => array(
                    array(
                        'event' => 'change',
                        'function' => 'prepareJoinedObjectLabel',
                        'parameter' => '$(sId)'
                    )
                )
            )));

            $oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Pflichtfeld'), 'checkbox', array(
                'db_alias' => 'tc_rf',
                'db_column' => 'required'
            )));

            $oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Label'), 'input', array(
                'db_alias' => 'tc_rf',
                'db_column' => 'label'
            )));

            $oDialog->setElement($oJoinContainer);

        }
       return $oDialog;
    }
    public static function getOrderby()
    {
        return['tc_r_i18n.name' => 'ASC'];
    }

}
