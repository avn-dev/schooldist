<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
$oSmarty = new \Cms\Service\Smarty();
$oTickets = new Ext_Office_TicketsLight();

/**
 * save new ticket
 */
if($_VARS['office_tickets']['task'] == 'save') {
	
	$aStates = $oTickets->getStates();
	reset($aStates);
	
	$oCustomer = new Ext_Office_Customer(0, $user_data['id']);

	if($oCustomer->cms_contact) {
		$iUser = $oCustomer->cms_contact;
	} else {
		$iUser = $oConfig->assigned_user_id;
	}

	$aData = array(
					'customer_id'=>(int)$user_data['id'],
					'document_id'=>0,
					'due_date'=>strtotimestamp($_VARS['office_tickets']['due_date'], 1),
					'state'=>key($aStates),
					'priority'=>(int)$_VARS['office_tickets']['priority'],
					'assigned_user_id'=>$iUser,
					'creator_user_id'=>$iUser,
					'headline'=>strip_tags($_VARS['office_tickets']['headline']),
					'description'=>strip_tags($_VARS['office_tickets']['description'])
				);
	$iTicketId = $oTickets->saveTicket($aData);
	$oTickets->sendMessage($iTicketId);

}

if($_VARS['office_tickets']['task'] == 'detail') {
	
	$aTicket = array();
	$aOptions = array();
	$aOptions['customer_id'] = (int)$user_data['id'];
	$aTickets = $oTickets->getTickets($aOptions);
	foreach((array)$aTickets as $aTicket) {
		if($aTicket['id'] == $_VARS['office_tickets']['id']) {
			$aTicket = $oTickets->getTicketDisplay($aTicket);
			$aTicket['comments'] = $oTickets->getComments($aTicket['id']);
			break;
		}
	}
	$oSmarty->assign('aTicket', $aTicket);
	
} else {
	
	/**
	 * show tickets
	 */
	$aOptions = array();
	$aOptions['customer_id'] = (int)$user_data['id'];
	$aTickets = $oTickets->getTickets($aOptions);
	
	foreach((array)$aTickets as $iKey=>$aTicket) {
		$aTickets[$iKey] = $oTickets->getTicketDisplay($aTicket);
		$aTickets[$iKey]['comments'] = count($oTickets->getComments($aTicket['id']));
	}
	
	$aPriorities = $oTickets->getPriorities();
	
	$oSmarty->assign('aTickets', $aTickets);
	$oSmarty->assign('aPriorities', $aPriorities);

}

echo $oSmarty->displayExtension($element_data, false);
