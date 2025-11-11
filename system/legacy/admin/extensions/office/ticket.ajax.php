<?php

if($_COOKIE['incms'] == 1) {
	include_once(\Util::getDocumentRoot().'system/legacy/admin/includes/main.inc.php');
}

if($user_data['cms'] == 1 || $user_data['id'] == 0)
{
	Access_Backend::checkAccess('office_tickets');
}
if($user_data['id'] == 0)
{
	die();
}

$aAJAX = array();

/* ==================================================================================================== */

if(isset($_VARS['action']) && $_VARS['action'] == 'set_live')
{
	foreach((array)$_VARS['flags'] as $iTickedID)
	{
		DB::updateData('office_tickets', array('system' => 1), "`id` = " . (int)$iTickedID);
	}

	echo json_encode(true);

	exit();
}

if(isset($_VARS['action']) && $_VARS['action'] == 'save_ticket')
{
	$aErrors = array();

	$iHours = $iMoney = 0;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check errors

	if(($_VARS['backend_new'] == 1 || $user_data['cms'] == 0) && trim($_VARS['title']) == '')
	{
		$aErrors['EMPTY'] = 'title';
	}
	else if(trim($_VARS['notice']) == '')
	{
		$aErrors['EMPTY'] = 'notice';
	}
	else if($user_data['cms'] == 1 && $_VARS['state'] == 4 && $_VARS['lastState'] != 4)
	{
		$oTicket = new WDBasic($_VARS['ticket_id'], 'office_tickets');

		$iHours = (float)str_replace(',', '.', str_replace('.', '', $_VARS['hours']));
		$iMoney = (float)str_replace(',', '.', str_replace('.', '', $_VARS['money']));

		if(empty($iHours) && empty($iMoney) && $oTicket->billing != 1)
		{
			$aErrors['EMPTY'] = 'costs';
		}
	}

	if(!empty($aErrors))
	{
		$aAJAX['errors'] = $aErrors;

		echo json_encode($aAJAX);

		exit();
	}

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save data

	if($user_data['cms'] == 1)
	{
		$oTicket = new Ext_Office_Tickets_Backend(array());
	}
	else
	{
		$oTicket = new Ext_Office_Tickets_Frontend(array());
	}

	$aTicket = array(
		'id'			=> $_VARS['ticket_id'],
		'project_id'	=> $_VARS['project_id'],
		'state'			=> $_VARS['state'],
		'notice'		=> $_VARS['notice'],
		'hours'			=> $iHours,
		'money'			=> $iMoney,

		'title'			=> $_VARS['title'],
		'area'			=> $_VARS['area'],
		'type'			=> $_VARS['type'],
		'billing'		=> $_VARS['billing'],

		'hash'			=> $_VARS['hash'],

		'done'			=> $_VARS['done']
	);

	if($_VARS['billing'] === 0 || $_VARS['billing'] === '0' || $_VARS['billing'] === 1 || $_VARS['billing'] === '1')
	{
		$aTicket['billing'] = (int)$_VARS['billing'];
	}
	else
	{
		$aTicket['billing'] = false;
	}

	if(isset($_VARS['backend_new']) && $_VARS['backend_new'] == 1)
	{
		$aTicket['backend_new'] = true;
	}

	$iID = $oTicket->save($aTicket);

	$aAJAX['id'] = $iID;

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

	echo json_encode($aAJAX);

	exit();
}

/* ==================================================================================================== */

if(isset($_VARS['action']) && $_VARS['action'] == 'sort_tickets')
{
	__out($_VARS);
}

?>