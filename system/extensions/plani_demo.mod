<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$oSmarty = new \Cms\Service\Smarty();

if(isset($oConfig->type))
{
	if(isset($_VARS['confirmation'])) {
		$sFlag = 'confirmation';
	} else {
		$sFlag = 'form';
	}

	$aCustomerData	= array();
	$sErrorMessage	= '';

	if(isset($_VARS['task']) && $_VARS['task'] == 'get_demo_data')
	{
		$aCustomerData = array(
			'lastname'		=> $_VARS['lastname'],
			'firstname'		=> $_VARS['firstname'],
			'email'			=> $_VARS['email'],
			'username'		=> $_VARS['email'],
			'password'		=> \Util::generateRandomString(6),
			'company'		=> $_VARS['company'],
			'street'		=> $_VARS['street'],
			'zip'			=> $_VARS['zip'],
			'city'			=> $_VARS['city'],
			'phone'			=> $_VARS['phone']
		);

		if(
			!empty($_VARS['lastname'])
				&&
			!empty($_VARS['firstname'])
				&&
			!empty($_VARS['email'])
		)
		{
			$sFlag = __createUser($aCustomerData, $oConfig->type, $oConfig->email_text);
		}
		else
		{
			$sErrorMessage = 'Bitte füllen Sie alle Pflichtfelder aus.';
		}
	}

	$oSmarty->assign('aCustomer', $aCustomerData);
	$oSmarty->assign('sErrorMessage', $sErrorMessage);
	$oSmarty->assign('sFlag', $sFlag);
}

$oSmarty->displayExtension($element_data);

function __createUser($aCustomerData, $sType, $sText)
{
	global $oConfig, $_VARS;

	$aFiles = array();

	if($sType == 'office')
	{
		// Connect to demo DB
		$oConnection = DB::createConnection('cms_office', 'localhost', 'cms_office', 'uvu4des1', 'cms_office');
		$aFiles[\Util::getDocumentRoot().'media/downloads/webdynamics_office.pdf'] = \Util::getDocumentRoot().'media/downloads/webdynamics_office.pdf';
	}
	if($sType == 'cms')
	{
		// Connect to demo DB
		$oConnection = DB::createConnection('cms_cms', 'localhost', 'cms_cms', 'repihi21', 'cms_cms');
		$aFiles[\Util::getDocumentRoot().'media/downloads/webdynamics_cms.pdf'] = \Util::getDocumentRoot().'media/downloads/webdynamics_cms.pdf';
	}

	$sSQL = "
		INSERT INTO
			`system_user`
		SET
			`active`		= 1,
			`created`		= NOW(),
			`tab_data`		= 3,
			`toolbar_size`	= 32,
			`role`			= 2,
			`lastname`		= :lastname,
			`firstname`		= :firstname,
			`email`			= :email,
			`username`		= :username,
			`password`		= MD5(:password),
			`company`		= :company,
			`street`		= :street,
			`zip`			= :zip,
			`city`			= :city,
			`phone`			= :phone
	";

	try {
		$oConnection->preparedQuery($sSQL, $aCustomerData);
	} catch (Exception $e) {
		wdmail('info@plan-i.de', 'Ihre Demo-Zugangsdaten - Fehler', print_r($_VARS, 1)."\n\n".print_r($_SERVER, 1));
		return 'error';
	}

	// Send mail
	$sText = str_replace(
		array('{Username}', '{Password}'),
		array($aCustomerData['username'], $aCustomerData['password']),
		$sText
	);
	wdmail($aCustomerData['email'], 'Ihre Demo-Zugangsdaten', $sText, '', $aFiles);
	wdmail('info@plan-i.de', 'CC: Ihre Demo-Zugangsdaten', $sText."\n\n".print_r($_VARS, 1)."\n\n".print_r($_SESSION, 1)."\n\n".print_r($_SESSION, 1)."\n\n".print_r($_SERVER, 1), '', $aFiles);

	return 'confirmation';
}

?>