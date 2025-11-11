<?php

/**
 * Class Ext_TC_System_Font_Gui2_Data
 */
class Ext_TC_System_Font_Gui2_Data extends Ext_TC_Gui2_Data
{

	/**
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @return string
	 * @throws Exception
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null)
    {

		$bParent = false;               

		switch($sError) {
			case 'WRONG_FORMAT':
				$sMessage = '"%s" hat ein falsches Format. Erlaubt sind .ttf Dateien!';
				break;
			default:
				$bParent = true;
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}

		if(!$bParent) {
			$sMessage = $this->t($sMessage);
			$sMessage = sprintf($sMessage, $sLabel);
		}

		return $sMessage;
	}

    public static function getOrderby()
    {
        return['name' => 'DESC'];
    }

    public static function getDialog(Ext_TC_Gui2 $oGui)
    {
        $sDir = Ext_TC_Util::getSecureDirectory().'fonts/';
        $oDialog = $oGui->createDialog(
            $oGui->t('Schriftart "{name}" bearbeiten'),
            $oGui->t('Neue Schriftart anlegen'));
        $oDialog->height = 450;

        $oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
            'db_column'	=> 'name',
            'db_alias'	=> '',
            'required'	=> true
        )));

        $oUpload = new Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Normal'), $oDialog, 'font', '', $sDir, false, ['required'=>true]);
        $oDialog->setElement($oUpload);
        $oUpload = new Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Kursiv'), $oDialog, 'font_i', '', $sDir);
        $oDialog->setElement($oUpload);
        $oUpload = new Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Fett'), $oDialog, 'font_b', '', $sDir);
        $oDialog->setElement($oUpload);
        $oUpload = new Ext_Gui2_Dialog_Upload($oGui, $oGui->t('Fett und Kursiv'), $oDialog, 'font_bi', '', $sDir);
        $oDialog->setElement($oUpload);
        return $oDialog;
    }

    public static function getSelectOptionsUsageFilter()
    {

        return Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsageOptions', array(true));
    }
	
}