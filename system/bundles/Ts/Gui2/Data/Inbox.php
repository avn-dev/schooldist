<?php

namespace Ts\Gui2\Data;

use Tc\Traits\Gui2\Dialog\WithAccessMatrix;
use Ts\Gui2\Inbox\AccessMatrix;

class Inbox extends \Ext_Thebing_Gui2_Data
{
    use WithAccessMatrix;

    protected function getAccessMatrix(): \Ext_TC_Access_Matrix
    {
        return new AccessMatrix();
    }

    static public function getOrderby()
    {
        return ['position' => 'ASC'];
    }

    static public function getWhere()
    {

        return ['client_id' => \Ext_Thebing_Client::getClientId()];
    }

    static public function getDialog(\Ext_Thebing_Gui2 $oGui)
    {
        //Dialog
        $oDialog = $oGui->createDialog(
            $oGui->t('Inbox "{name}" editieren'),
            $oGui->t('Neue Inbox anlegen'));
        $oDialog->width	 = 900;
        $oDialog->height = 650;
        $oDialog->setElement($oDialog->createRow(
            $oGui->t('Aktiv'), 'checkbox', ['db_alias' => '', 'db_column' => 'status']));
        $oDialog->setElement($oDialog->createRow(
            $oGui->t('Name'), 'input', array('db_alias' => '', 'db_column'=>'name','required' => true)));

        return $oDialog;
    }

    public static function getStatusFilterOptions (\Ext_Thebing_Gui2 $oGui)
    {
        $aStatusFilterOptions = [
            'active' => $oGui->t('Aktiviert'),
            'disabled' => $oGui->t('Deaktiviert'),
        ];

        return $aStatusFilterOptions;
    }

}