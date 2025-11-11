<?php

class Ext_Thebing_Email_Layout_Gui2 extends Ext_Thebing_Gui2_Data
{

	use \Tc\Traits\Gui2\ImageTab;
	
	public function getTranslations($sL10NDescription)
    {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['email_preview_note'] = L10N::t('Die E-Mail sollte nicht breiter als 600 Pixel sein.', $sL10NDescription);
		$aData['email_preview_title'] = L10N::t('E-Mail Layout Vorschau', $sL10NDescription);

		return $aData;
	}

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction == 'new' || $sIconAction == 'edit')
		{
			if(isset($aData['tabs'][1]))
			{
				$aData['tabs'][1]['html'] = $this->writeImageTabHTML();
			}
		}

		return $aData;
	}

	public static function getWhere()
	{

		return ['client_id'=>\Ext_Thebing_Client::getClientId()];
	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('E-Mail Layout "{name}"'), $oGui->t('Neues E-Mail Layout'));
		$oDialog->bSmallLabels = true;
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array('db_column'=>'name', 'required'=>1)));

		$sHtmlDescription = $oGui->t('HTML Layout');
		$sHtmlDescription .= '<br/><div class="note">'.$oGui->t('Beispiel: ').'<br/>';
		$sHtmlDescription .= '<pre>'.\Util::convertHtmlEntities(' <html>
            <head>
            </head>
            <body>
                <p>Header</p>
                <p>{email_content}</p>
                <p>{email_signature}</p>
                <p>Footer</p>
            </body>
        </html> ').'</pre>';
		$sHtmlDescription .= '</div>';
		$oTab->setElement($oDialog->createRow($sHtmlDescription, 'textarea', array('db_column'=>'html', 'style'=>'height:380px;', 'id' => 'email_preview_html')));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Vorschau'),
				'button',
				array(
					'onclick'=>"aGUI['".$oGui->hash."'].openPreview();return false;",
					'value'=>$oGui->t('Öffnen')
				)
			)
		);

		$oTab->aOptions['section'] = 'admin_email_layouts';
		$oTab->aOptions['task'] = 'settings';

		$oNotification = $oDialog->createNotification($oGui->t('Bitte verwenden Sie {email_content} als Platzhalter für den E-Mail-Inhalt.'), false, 'info');
		$oTab->setElement($oNotification);
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Bilder'));
		$oTab->setElement("html");
		$oDialog->setElement($oTab);

		return $oDialog;
	}

}