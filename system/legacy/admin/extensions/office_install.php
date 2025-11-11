<?php

include_once(\Util::getDocumentRoot().'system/includes/admin.inc.php');
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

if(!isset($_SESSION['office']['step']['1']))
{
	$_SESSION['office']['step']['1'] = 0;
}

$sTmp = '';
if(!isset($aConfigData['database']))
{
	$sTmp = '(nicht empfohlen)';
}

$sErrorMessage = '';

//unset($_SESSION['office']); die();

// Cancel the configuration
if(
	isset($_VARS['action'])
		&&
	$_VARS['action'] == 'cancel'
		&&
	$_VARS['cancel'] == 1
)
{
	$_SESSION['office']['step']['0'] = 1;
	$_SESSION['office']['step']['1'] = 1;
	$_SESSION['office']['step']['2'] = 1;
}

// Step 1 / 2 (Personal data)
if(
	isset($_VARS['action'])
		&&
	$_VARS['action'] == 'step_1'
		&&
	(
		!isset($_SESSION['office']['step']['1'])
			||
		$_SESSION['office']['step']['1'] == 0
	)
)
{
	if(trim($_VARS['company']) == '')
	{
		$sErrorMessage = 'Fehler! Bitte geben Sie die Firma an.';
	}
	else if(trim($_VARS['street']) == '')
	{
		$sErrorMessage = 'Fehler! Bitte geben Sie die Strasse an.';
	}
	else if(trim($_VARS['zip']) == '')
	{
		$sErrorMessage = 'Fehler! Bitte geben Sie die PLZ an.';
	}
	else if(trim($_VARS['city']) == '')
	{
		$sErrorMessage = 'Fehler! Bitte geben Sie den Ort an.';
	}

	$_SESSION['office']['data_1']['company']			= $_VARS['company'];
	$_SESSION['office']['data_1']['street']				= $_VARS['street'];
	$_SESSION['office']['data_1']['zip']				= $_VARS['zip'];
	$_SESSION['office']['data_1']['city']				= $_VARS['city'];

	$_SESSION['office']['data_1']['phone']				= $_VARS['phone'];
	$_SESSION['office']['data_1']['fax']				= $_VARS['fax'];
	$_SESSION['office']['data_1']['website']			= $_VARS['website'];
	$_SESSION['office']['data_1']['email']				= $_VARS['email'];

	$_SESSION['office']['data_1']['bank']				= $_VARS['bank'];
	$_SESSION['office']['data_1']['bank_zip']			= $_VARS['bank_zip'];
	$_SESSION['office']['data_1']['bank_number']		= $_VARS['bank_number'];

	$_SESSION['office']['data_1']['register_court']		= $_VARS['register_court'];
	$_SESSION['office']['data_1']['register_number']	= $_VARS['register_number'];
	$_SESSION['office']['data_1']['manager']			= $_VARS['manager'];

	// Set step 1 to OK and go to step 2
	if($sErrorMessage == '')
	{
		__createCustomerDB();
		__setDefaultForms();

		$_SESSION['office']['step']['1'] = 1;
		$_SESSION['office']['step']['2'] = 0;

		// Reload configuration
		$aConfigData = $oOffice->getConfigData();
	}
}

// Step 2 / 2 (PDF fonts and backgrounds)
if(
	$_SESSION['office']['step']['1'] == 1
		&&
	$_SESSION['office']['step']['2'] == 0
		&&
	isset($_VARS['action'])
		&&
	$_VARS['action'] == 'step_3'
)
{
	if(
		($_VARS['font']['name'] != '' && $_VARS['font_bold']['name'] == '')
			||
		($_VARS['font']['name'] == '' && $_VARS['font_bold']['name'] != '')
			||
		($_VARS['font']['name'] != '' && $_VARS['font_bold']['name'] != '' && trim($_VARS['font_name']) == '')
	)
	{
		$sErrorMessage = 'Fehler! Bitte geben Sie sowohl die Standard- als auch die fette Schriftart an.';
	}
	// Insert user fonts
	else if(
		$_VARS['font']['name'] != ''
			&&
		$_VARS['font_bold']['name'] != ''
			&&
		trim($_VARS['font_name']) != ''
	)
	{
		$mReturn = classExtension_Office::addFont($_VARS['font_name'], '', $_VARS['font']['tmp_name']);
		if($mReturn == 1)
		{
			$mReturn = classExtension_Office::addFont($_VARS['font_name'], 'b', $_VARS['font_bold']['tmp_name']);
			if($mReturn != 1)
			{
				$sErrorMessage = 'Es ist ein Fehler aufgetreten. Versuchen Sie es bitte nochmal.';
			}
		}
		else
		{
			$sErrorMessage = 'Es ist ein Fehler aufgetreten. Versuchen Sie es bitte nochmal.';
		}
	}
	// Insert standard fonts
	else if(
		$_VARS['font']['name'] == ''
			&&
		$_VARS['font_bold']['name'] == ''
	)
	{
		$aFonts = array(
			"`created` = NOW(), `active` = 1, `file` = 'helvetica', `style` = '', `name` = 'Helvetica'",
			"`created` = NOW(), `active` = 1, `file` = 'helvetica', `style` = 'b', `name` = 'Helvetica'",
			"`created` = NOW(), `active` = 1, `file` = 'helvetica', `style` = 'i', `name` = 'Helvetica'",
			"`created` = NOW(), `active` = 1, `file` = 'helvetica', `style` = 'bi', `name` = 'Helvetica'"
		);
		foreach((array)$aFonts as $sSet)
		{
			$sSQL = "INSERT INTO `office_fonts` SET ".$sSet."";
			DB::executeQuery($sSQL);
		}
	}

	// Set step 2 to OK and go to configuration confirmation
	if($sErrorMessage == '')
	{
		__saveConfiguration();

		$_SESSION['office']['step']['2'] = 1;
		$_SESSION['office']['step']['3'] = 1;
	}
}

// Step 3 (Confirmation)
if(
	(
		$_SESSION['office']['step']['2'] == 1
			&&
		$_SESSION['office']['step']['3'] == 1
	)
		||
	$_SESSION['office']['step']['0'] == 1
)
{
	$sSQL = "INSERT INTO `office_config` SET `key` = 'install_complete', `value` = 1, `client_id` = " . (int)\Core\Handler\SessionHandler::getInstance()->get('office_client_id');
	DB::executeQuery($sSQL);
}

Admin_Html::loadAdminHeader();

?>

<table cellpadding="0" cellspacing="20" style="border:0; width:100%;">
	<tr>
		<td style="vertical-align:top;">

<? if($_SESSION['office']['step']['1'] == 0) { ?>

	<h1>Office Konfiguration (Schritt 1 / 2)</h1>

	<div style="color:red; padding-bottom:10px;"><?=$sErrorMessage?></div>

	<p>
		Willkommen im Konfigurationsassistenten von webDynamics Office!
		<br /><br />
		Dieser Assistent führt Sie in zwei Schritten durch die Grundeinstellungen, die Sie benötigen, um mit webDynamics Office arbeiten zu können.
		<br />
		Sollten Sie Probleme bei der Konfiguration haben, so stehen wir Ihnen gerne persönlich unter 0800 / 752 64 33 oder per E-Mail <a href="mailto:info@plan-i.de?subject=webDynamics Office Konfiguration">info@plan-i.de</a> zur Verfügung.
		<br /><br />
	</p>

	<h2 style="margin:3px;">Konfiguration überspringen</h2>
	<form action="" method="post">
		<input type="hidden" name="action" value="cancel">
		<?=printTableStart()?>
			<?=printFormCheckbox('Konfiguration überspringen '.$sTmp, 'cancel', 1)?>
		<?=printTableEnd()?>
		<?=printSubmit("Konfiguration überspringen")?>
	</form>

	<br />
	<h2 style="margin:3px;">Konfiguration fortsetzen</h2>
	<br />

	<form action="" method="post">
		<input type="hidden" name="action" value="step_1">
		<h2 style="margin:3px;">Firmendaten (Pflichtfelder)</h2>
		<?=printTableStart()?>
			<?=printFormText('Firma', 'company', $_SESSION['office']['data_1']['company'])?>
			<?=printFormText('Strasse', 'street', $_SESSION['office']['data_1']['street'])?>
			<?=printFormText('PLZ', 'zip', $_SESSION['office']['data_1']['zip'])?>
			<?=printFormText('Ort', 'city', $_SESSION['office']['data_1']['city'])?>
		<?=printTableEnd()?>
		<h2 style="margin:3px; margin-top:10px;">Kontaktdaten</h2>
		<?=printTableStart()?>
			<?=printFormText('Telefon', 'phone', $_SESSION['office']['data_1']['phone'])?>
			<?=printFormText('Telefax', 'fax', $_SESSION['office']['data_1']['fax'])?>
			<?=printFormText('Webseite', 'website', $_SESSION['office']['data_1']['website'])?>
			<?=printFormText('E-Mail-Adresse', 'email', $_SESSION['office']['data_1']['email'])?>
		<?=printTableEnd()?>
		<h2 style="margin:3px; margin-top:10px;">Bankverbindung</h2>
		<?=printTableStart()?>
			<?=printFormText('Bankname', 'bank', $_SESSION['office']['data_1']['bank'])?>
			<?=printFormText('BLZ', 'bank_zip', $_SESSION['office']['data_1']['bank_zip'])?>
			<?=printFormText('Konto Nr.', 'bank_number', $_SESSION['office']['data_1']['bank_number'])?>
		<?=printTableEnd()?>
		<h2 style="margin:3px; margin-top:10px;">Sonstiges</h2>
		<?=printTableStart()?>
			<?=printFormText('Registergericht', 'register_court', $_SESSION['office']['data_1']['register_court'])?>
			<?=printFormText('Handelsregister Nr.', 'register_number', $_SESSION['office']['data_1']['register_number'])?>
			<?=printFormTextarea('Geschäftsführer', 'manager', $_SESSION['office']['data_1']['manager'])?>
		<?=printTableEnd()?>
		<?=printSubmit("Speichern und weiter zu Schritt 2 / 2")?>
	</form>

<? } else if($_SESSION['office']['step']['1'] == 1 && $_SESSION['office']['step']['2'] == 0) {?>

	<h1>Office Konfiguration (Schritt 2 / 2)</h1>

	<div style="text-align:center; color:red; padding-bottom:10px;"><?=$sErrorMessage?></div>

	<form action="" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="step_3">
		<h2 style="margin:3px;">Schriftarten</h2>
		<p>
			Standardmässig stellt Ihnen das Office die Schriftart "Helvetica" zur Verfügung.
			Sie können jedoch auch Ihre eigene Schriftarten hochladen.
			<br /><br />
			Dies können Sie entweder jetzt oder auch jederzeit später unter "Konfiguration :: Schriftarten" machen.
			<br /><br />
			Tipp: Viele Schriftart-Dateien (*.ttf) befinden sich unter Windows in C:\Windows\Fonts\
		</p>
		<?=printTableStart()?>
			<?=printFormText('Name der Schriftart', 'font_name')?>
			<tr>
				<th>Schriftart (! standard, z.B. 'arial.ttf')*</th>
				<td>
					<input name="font" type="file" size="37" />
				</td>
			</tr>
			<tr>
				<th>Schriftart (! fett, z.B. 'arialb.ttf')*</th>
				<td>
					<input name="font_bold" type="file" size="37" />
				</td>
			</tr>
		<?=printTableEnd()?>
		<p style="font-size:8pt;">
			* Bitte beachten Sie, dass beide Dateien von identischer Schriftart sein müssen.
		</p>
		<br />

		<h2 style="margin:3px; margin-top:10px;">PDF-Hintergrunddateien</h2>
		<p>
			Mit webDynamics Office können Sie Ihre Dokumente individuell gestalten.
			Sie können dafür eigene PDF-Dateien hochladen, die Ihnen als Hintergrund für Ihre Dokumente dienen.
			<br/><br/>
			Dies können Sie auch später unter "Konfiguration :: Formulare" machen.
		</p>
		<?=printTableStart()?>
			<tr>
				<th>PDF Hintergrund, erste Seite</th>
				<td>
					<input name="pdf_first" type="file" size="37" />
				</td>
			</tr>
			<tr>
				<th>PDF Hintergrund, Folgeseiten</th>
				<td>
					<input name="pdf_next" type="file" size="37" />
				</td>
			</tr>
		<?=printTableEnd()?>
		<?=printSubmit("Konfiguration abschließen")?>
	</form>

<? } else if($_SESSION['office']['step']['2'] == 1 && $_SESSION['office']['step']['3'] == 1) { ?>

	<h1>Konfiguration abgeschlossen!</h1>
	<h2>Das Office wurde erfolgreich eingerichtet</h2>
	<p>
		Herzlichen Glückwunsch!
		<br /><br />
		Das <a href="#" onclick="parent.toolbar.openTask('office','?task=update');">Office</a> wurde erfolgreich eingerichtet und kann nun benutzt werden.
		Die Einstellungen dieser Konfiguration können Sie jederzeit unter dem Menüpunkt "Konfiguration" im Office korrigieren, ändern und/oder ergänzen.
		<br /><br />
		Es wurden folgende Vorkonfigurationen durchgeführt:
		<ul>
			<li>Kundendatenbank eingerichtet ("Konfiguration :: Kundendatenbank")</li>
			<li>Einheiten angelegt ("Konfiguration :: Einheiten")</li>
			<li>Ust-Sätze angelegt ("Konfiguration :: Umsatzsteuer")</li>
			<li>Aktivitäten angelegt ("Konfiguration :: Aktivitäten")</li>
			<li>Nummernkreise angelegt ("Konfiguration :: Nummernkreise")</li>
			<li>Zahlungsbedingungen angelegt ("Konfiguration :: Zahlungsbedingungen")</li>
			<li>Textbausteine angelegt ("Konfiguration :: Textbausteine")</li>
			<li>Standardformular eingerichtet ("Konfiguration :: Formulare")</li>
		</ul>
		<br /><br />
		Viel Erfolg wünscht Ihnen das plan-i-Team!
	</p>

<? unset($_SESSION['office']); } else if($_SESSION['office']['step']['0'] == 1) { ?>

	<h1>Konfiguration übersprungen</h1>
	<p>
		Die Konfiguration wurde übersprungen.
		<a href="#" onclick="parent.toolbar.openTask('office','?task=update');">Hier</a> geht es weiter zum Office.
		<br /><br />
		Viel Erfolg wünscht Ihnen das plan-i-Team!
	</p>

<? unset($_SESSION['office']); } ?>

		</td>
	</tr>
</table>

<?=Admin_Html::loadAdminFooter()?>

<?

function __createCustomerDB()
{
	// Data for 'customer_db_config'
	$sSQL = "INSERT INTO `customer_db_config` SET `db_name` = 'Office Kunden', `db_encode_pw` = 0, `multi_login` = 0, `active` = 1";
	DB::executeQuery($sSQL);
	$iNewDB_ID = (int)DB::fetchInsertID();

	// Data for 'customer_db_definition'
	$aDefData = array(
		array('field_nr' => 0, 'name' => 'email',			'type' => 'TEXT',		'required' => 1),
		array('field_nr' => 0, 'name' => 'nickname',		'type' => 'TEXT',		'required' => 1),
		array('field_nr' => 0, 'name' => 'password',		'type' => 'PASSWORD',	'required' => 1),
		array('field_nr' => 0, 'name' => 'changed',	'type' => 'timestamp',	'required' => 0),
		array('field_nr' => 0, 'name' => 'last_login',		'type' => 'timestamp',	'required' => 0),
		array('field_nr' => 0, 'name' => 'changed_by',		'type' => 'INTEGER',	'required' => 0),
		array('field_nr' => 0, 'name' => 'created',			'type' => 'timestamp',	'required' => 0),
		array('field_nr' => 0, 'name' => 'views',			'type' => 'INTEGER',	'required' => 0),
		array('field_nr' => 0, 'name' => 'groups',			'type' => 'groups',		'required' => 0),
		array('field_nr' => 1, 'name' => 'number',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 2, 'name' => 'company',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 3, 'name' => 'addition',		'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 4, 'name' => 'street',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 5, 'name' => 'zip',				'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 6, 'name' => 'city',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 7, 'name' => 'country',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 8, 'name' => 'phone',			'type' => 'TEXT',		'required' => 0),
		array('field_nr' => 9, 'name' => 'fax',				'type' => 'TEXT',		'required' => 0)
	);
	foreach((array)$aDefData as $aData)
	{
		$sSQL = "INSERT INTO `customer_db_definition` SET {SET}";
		$sSET = "";
		foreach((array)$aData as $sKey => $mValue)
		{
			$sSET .= "`".$sKey."` = '".$mValue."', ";
		}
		$sSET .= "`active` = '1', `db_nr` = " . $iNewDB_ID;
		$sSQL = str_replace('{SET}', $sSET, $sSQL);

		DB::executeQuery($sSQL);
	}

	// Create new customer_db_XXX
	$sSQL = "
		CREATE TABLE
			`customer_db_".$iNewDB_ID."`
			(
				`id` int(11) NOT NULL auto_increment,
				`active` tinyint(4) NOT NULL default '0',
				`email` varchar(255) NOT NULL default '',
				`nickname` varchar(255) NOT NULL default '',
				`password` varchar(255) NOT NULL default '',
				`changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				`last_login` timestamp NOT NULL default '0000-00-00 00:00:00',
				`changed_by` int(11) NOT NULL default '0',
				`created` timestamp NOT NULL default '0000-00-00 00:00:00',
				`views` int(11) NOT NULL default '0',
				`groups` text NOT NULL,
				`ext_1` text NOT NULL,
				`ext_2` text NOT NULL,
				`ext_3` text NOT NULL,
				`ext_4` text NOT NULL,
				`ext_5` text NOT NULL,
				`ext_6` text NOT NULL,
				`ext_7` text NOT NULL,
				`ext_8` text NOT NULL,
				`ext_9` text NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `email` (`email`),
				UNIQUE KEY `nickname` (`nickname`)
			)
		ENGINE=MyISAM DEFAULT CHARSET=utf8
	";
	DB::executeQuery($sSQL);

	// Save configuration data
	$aConfig = array(
		"`key` = 'database',		`value` = '".$iNewDB_ID."'",
		"`key` = 'field_number',	`value` = 'ext_1'",
		"`key` = 'field_matchcode',	`value` = 'ext_2'",
		"`key` = 'field_company',	`value` = 'ext_2'",
		"`key` = 'field_addition',	`value` = 'ext_3'",
		"`key` = 'field_address',	`value` = 'ext_4'",
		"`key` = 'field_zip',		`value` = 'ext_5'",
		"`key` = 'field_city',		`value` = 'ext_6'",
		"`key` = 'field_country',	`value` = 'ext_7'",
		"`key` = 'field_phone',		`value` = 'ext_8'",
		"`key` = 'field_fax',		`value` = 'ext_9'"
	);
	foreach((array)$aConfig as $sSet)
	{
		$sSQL = "INSERT IGNORE INTO `office_config` SET ".$sSet."";
		DB::executeQuery($sSQL);
	}
}

function __setDefaultForms()
{
	// Create default units
	$aUnits = array(
		'Stück'		=> 'Stück',
		'pausch.'	=> 'pausch.',
		'PT'		=> 'PT',
		'PS'		=> 'PS'
	);
	$sSQL = "INSERT IGNORE INTO `office_config` SET `key` = 'units', `value` = '".serialize($aUnits)."'";
	DB::executeQuery($sSQL);

	// Create default VATs
	$aVats = array(
		'0'		=> 0,
		'0.07'	=> 7,
		'0.19'	=> 19
	);
	$sSQL = "INSERT INTO `office_config` SET `key` = 'vat', `value` = '".serialize($aVats)."'";
	DB::executeQuery($sSQL);

	// Create default activities
	$aActivities = array(
		'increment'	=> 4,
		'add_1'		=> 'Telefonate',
		'add_2'		=> 'E-Mails',
		'add_3'		=> 'Termine'
	);
	$sSQL = "INSERT INTO `office_config` SET `key` = 'activities', `value` = '".serialize($aActivities)."'";
	DB::executeQuery($sSQL);

	// Create default document numbers
	$aRanges = array(
		'range_'			=> '',
		'range_letter'		=> 0,
		'range_fax'			=> 0,
		'range_offer'		=> 1000,
		'range_account'		=> 1000,
		'range_credit'		=> 1000,
		'range_reminder'	=> 1000,
		'range_contract'	=> 1000
	);
	foreach((array)$aRanges as $sKey => $mValue)
	{
		$sSQL = "INSERT INTO `office_config` SET `key` = '".$sKey."', `value` = '".$mValue."'";
		DB::executeQuery($sSQL);
	}

	// Create default payment terms
	$sSQL = "
		INSERT INTO
			`office_payment_terms`
		SET
			`created`	= NOW(),
			`active`	= 1,
			`title`		= 'Rechnung - 14 Tage',
			`days`		= 14,
			`message`	= 'Bitte zahlen Sie den Rechnungsbetrag bis zum <\#date\#> auf unser Konto ein.',
			`type_flag`	= 2
	";
	DB::executeQuery($sSQL);

	$sSQL = "
		INSERT INTO
			`office_payment_terms`
		SET
			`created`	= NOW(),
			`active`	= 1,
			`title`		= 'Angebot - 61 Tage',
			`days`		= 61,
			`message`	= 'Das Angebot gilt bis <\#date\#>',
			`type_flag`	= 1
	";
	DB::executeQuery($sSQL);

	// Create default office template texts
	$sSQL = "INSERT INTO `office_templates` SET `name` = 'Sehr geehrter Kunde', 	`text` = 'Sehr geehrter Kunde'";
	DB::executeQuery($sSQL);

	$sSQL = "INSERT INTO `office_templates` SET `name` = 'MfG', `text` = 'Mit freundlichen Grüßen'";
	DB::executeQuery($sSQL);
}

function __saveConfiguration()
{
	global $_VARS, $strPdfPath;

	// Get the last font
	$sSQL = "
		SELECT
			MAX(`id`) AS `id`
		FROM
			`office_fonts`
		WHERE
			`style` = ''
	";
	$aResult = DB::getQueryData($sSQL);
	$iFontID = $aResult[0]['id'];

	// Create a standard form
	$sSQL = "
		INSERT INTO
			`office_forms`
		SET
			`created`			= NOW(),
			`active`			= 1,
			`name`				= 'Standard',
			`margin_top`		= 20,
			`margin_right`		= 10,
			`margin_bottom`		= 35,
			`margin_left`		= 24,
			`start_position`	= 105,
			`font_id`			= ".$iFontID."
	";
	DB::executeQuery($sSQL);
	$iFormID = (int)DB::fetchInsertID();





	// Create the text blocks
	$sSetAddon = "`created` = NOW(), `form_id` = ".$iFormID.", `font_color` = '000000', `alignment` = 'L'";

	// Address line
	$sContent =
		$_SESSION['office']['data_1']['company'] . ' - ' .
		$_SESSION['office']['data_1']['street'] . ' - ' .
		$_SESSION['office']['data_1']['zip'] . ' ' . $_SESSION['office']['data_1']['city'];
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 24,
			`position_y`	= 45,
			`width`			= 150,
			`font_id`		= :iFontID,
			`font_size`		= 8,
			`display`		= 'FIRST',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// Customer address
	$sContent = "{CustomerName}\n{CustomerAddress}";
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 24,
			`position_y`	= 51,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 10,
			`display`		= 'FIRST',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// Document subject
	$sContent = "{DocumentSubject}";
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 24,
			`position_y`	= 90,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 10,
			`display`		= 'FIRST',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID + 1);
	DB::executePreparedQuery($sSQL, $aSQL);

	// 1. of bottom blocks
	$sContent = $sContent =
		$_SESSION['office']['data_1']['company'] . "\n" .
		$_SESSION['office']['data_1']['street'] . "\n" .
		$_SESSION['office']['data_1']['zip'] . ' ' . $_SESSION['office']['data_1']['city'];
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 10,
			`position_y`	= 272,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 8,
			`display`		= 'BOTH',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// 2. of bottom blocks
	$sContent = "Bankverbindung:\n" .
		$_SESSION['office']['data_1']['bank'] . "\nBLZ: " .
		$_SESSION['office']['data_1']['bank_zip'] . "\nKonto Nr.: " .
		$_SESSION['office']['data_1']['bank_number'];
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 50,
			`position_y`	= 272,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 8,
			`display`		= 'BOTH',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// 3. of bottom blocks
	$sContent = "Telefon: " .
		$_SESSION['office']['data_1']['phone'] . "\nTelefax: " .
		$_SESSION['office']['data_1']['fax'] . "\nE-Mail: " .
		$_SESSION['office']['data_1']['email'] . "\nInternet: " .
		$_SESSION['office']['data_1']['website'];
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 100,
			`position_y`	= 272,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 8,
			`display`		= 'BOTH',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// 4. of bottom blocks
	$sContent = "Geschäftsführer: " .
		$_SESSION['office']['data_1']['manager'] . "\nRegistergericht: " .
		$_SESSION['office']['data_1']['register_court'] . "\nHandelsregister Nr.: " .
		$_SESSION['office']['data_1']['register_number'];
	$sSQL = "
		INSERT INTO
			`office_forms_items`
		SET
			`position_x`	= 145,
			`position_y`	= 272,
			`width`			= 100,
			`font_id`		= :iFontID,
			`font_size`		= 8,
			`display`		= 'BOTH',
			`content`		= :sContent,
			".$sSetAddon."
	";
	$aSQL = array('sContent' => $sContent, 'iFontID' => $iFontID);
	DB::executePreparedQuery($sSQL, $aSQL);

	// Save PDF background
	if($_VARS['pdf_first']['name'] != '')
	{
		if(move_uploaded_file($_VARS['pdf_first']['tmp_name'], $strPdfPath.$iFormID.'_first.pdf')) {
			// successfull
		}
	}
	if($_VARS['pdf_next']['name'] != '')
	{
		if(move_uploaded_file($_VARS['pdf_next']['tmp_name'], $strPdfPath.$iFormID.'_next.pdf')) {
			// successfull
		}
	}

	return false;
}

?>
