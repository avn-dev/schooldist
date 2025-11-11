<?php

namespace Tc\Gui2\Data;

class CommunicationCategory extends \Ext_TC_Gui2_Data
{
    public static function getOrderby()
    {
        return['tc_cc.name' => 'ASC'];
    }
    public static function getWhere()
    {
        return['tc_cc.active' => 1];
    }
    public static function getDialog(\Ext_Gui2 $oGui)
    {

        $oDialog = $oGui->createDialog($oGui->t('Kategorie "{name}" editieren'), $oGui->t('Neue Kategorie anlegen'));

        $oDialog->save_as_new_button = true;
        $oDialog->save_bar_options = true;

        $oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
            'db_alias' => 'tc_cc',
            'db_column' => 'name',
            'required' => true,
        )));

        $oDialog->setElement($oDialog->createRow($oGui->t('Farbe'), 'color', array(
            'db_alias' => 'tc_cc',
            'db_column' => 'code',
            'required' => true
        )));
        return$oDialog;
    }
}