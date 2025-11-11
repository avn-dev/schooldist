<?php

class office_tickets_light_backend {

	function executeHook($strHook, &$mixInput) {
		global $_VARS, $user_data;

		switch($strHook) {
			case "welcome_left":

				/*
				$objTickets = new Ext_Office_Tickets();
				$arrOptions = array("state"=>"new", "assigned_user_id"=>$user_data['id']);

				ob_start();
				$objTickets->writeTicketBox("Neue Tickets", $arrOptions, 0);
				$strContent = ob_get_contents();
				ob_end_clean();

				$mixInput[1]['title'] 		= 'Neue Tickets';
				$mixInput[1]['content'] 	= $strContent;
				*/

				break;
			
			default:
				break;
		}

	}
	
}

\System::wd()->addHook('welcome_left', 'office_tickets');
