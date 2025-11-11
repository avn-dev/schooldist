<?php

/**
 * E-Mail Layouts GUI2-Data Ableitung
 */
class Ext_TC_Communication_Template_Email_Layout_Gui2_Data extends Ext_TC_Gui2_Data
{

	public function getTranslations($sL10NDescription)
    {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['email_preview_note'] = L10N::t('Die E-Mail sollte nicht breiter als 600 Pixel sein.', $sL10NDescription);
		$aData['email_preview_title'] = L10N::t('E-Mail Layout Vorschau', $sL10NDescription);

		return $aData;
	}

	public static function getOrderby()
	{

		return ['tc_ctel.name' => 'ASC'];
	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{

		$oDialog = $oGui->createDialog($oGui->t('E-Mail-Layout "{name}" editieren'), $oGui->t('E-Mail-Layout anlegen'));
		$oDialog->bSmallLabels = true;
		$oDialog->save_as_new_button		= true;

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_ctel',
			'db_column' => 'name',
			'required' => true
		)));

		$oNotification = $oDialog->createNotification(
			$oGui->t('Verfügbare Platzhalter:'),
			$oGui->t('Platzhalter für den E-Mail-Inhalt: {email_content} <br>
					Platzhalter für die Benutzersignatur: {email_signature}'),
			'info',
		);
		$oDialog->setElement($oNotification);

		$sHtmlDescription = $oGui->t('HTML Layout');
		$sHtmlDescription .= '<br /><div class="note">'.$oGui->t('Beispiel: ').'<br/>';
		$sHtmlDescription .= '<pre>'.\Util::convertHtmlEntities('<html>
	<head>
	</head>
	<body>
		<p>Header</p>
		<p>{email_content}</p>
		<p>Footer</p>
	</body>
</html>').'</pre>';
		$sHtmlDescription .= '</div>';

		$oDialog->setElement($oDialog->createRow($sHtmlDescription, 'textarea', array(
			'db_alias' => 'tc_ctel',
			'db_column' => 'html',
			'style' => 'height: 380px;',
			'id' => 'email_preview_html'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Vorschau'), 'button', array(
			'onclick' => "aGUI['".$oGui->hash."'].openLayoutPreview(); return false;",
			'value' => $oGui->t('Öffnen')
		)));

		return $oDialog;
	}

}
