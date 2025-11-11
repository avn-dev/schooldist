<?php

class Ext_Office_TicketsLight {
	
	public $arrConfig = array();
	protected $_aTables = array();
	
	function __construct() {
		$this->getConfig();
	}

	function getConfig() {
		global $aConfigData;
		
		$strSql = "SELECT * FROM office_ticket_light_config";
		$arrData = DB::getQueryData($strSql);

		foreach((array)$arrData as $arrItem) {
			$this->arrConfig[$arrItem['variable']] = $arrItem['value'];
		}
		
		if(empty($this->arrConfig)) {
			$this->arrConfig['customer_table'] = 'customer_db_'.$aConfigData['database'];
			$this->arrConfig['customer_key'] = 'id';
			$this->arrConfig['customer_value'] = $aConfigData['field_matchcode'];
		}
		
	}

	function getCustomers() {
		
		if(empty($this->_aTables['customers'])) {
			
			$strSql = "SELECT #strKey `key`, #strValue `value` FROM #strCustomerTable ORDER BY #strValue";
			$arrTransfer = array(
								'strCustomerTable'=>$this->arrConfig['customer_table'],
								'strKey'=>$this->arrConfig['customer_key'],
								'strValue'=>$this->arrConfig['customer_value']
								);
			$arrData = DB::getPreparedQueryData($strSql, $arrTransfer);
			$this->_aTables['customers'] = array("0"=>"keine Angabe");
			foreach((array)$arrData as $arrCustomer) {
				$this->_aTables['customers'][$arrCustomer['key']] = $arrCustomer['value'];
			}
			
		}
			
		return $this->_aTables['customers'];
		
	}

	function getTicket($intTicketId) {
		$strSql = "SELECT *, UNIX_TIMESTAMP(changed) changed, UNIX_TIMESTAMP(created) created, UNIX_TIMESTAMP(due_date) due_date FROM office_ticket_light_entries WHERE id = :intTicketId LIMIT 1";
		$arrTransfer = array('intTicketId'=>$intTicketId);
		$arrData = DB::getPreparedQueryData($strSql, $arrTransfer);
		return $arrData[0];
	}

	function getTickets($arrOptions) {
		
		if(!isset($arrOptions['state'])) {
			$arrOptions['state'] = '%';
		}
		if(!isset($arrOptions['creator_user_id'])) {
			$arrOptions['creator_user_id'] = '%';
		}
		if(!isset($arrOptions['assigned_user_id'])) {
			$arrOptions['assigned_user_id'] = '%';
		}
		if(!isset($arrOptions['customer_id'])) {
			$arrOptions['customer_id'] = '%';
		}
		
		$strSql = "
				SELECT 
					ote.*, 
					UNIX_TIMESTAMP(ote.due_date) due_date,
					c.#customer `customer`
				FROM 
					office_ticket_light_entries ote LEFT OUTER JOIN
					#customer_table c ON
						ote.customer_id = c.#customer_key
				WHERE 
					ote.active = 1 AND
					ote.state LIKE :strState AND
					creator_user_id LIKE :creator_user_id AND
					assigned_user_id LIKE :assigned_user_id AND
					customer_id LIKE :customer_id
				ORDER BY 
					ote.due_date ASC
					";
		$arrTransfer = array(
						'strState'=>$arrOptions['state'],
						'creator_user_id'=>$arrOptions['creator_user_id'],
						'assigned_user_id'=>$arrOptions['assigned_user_id'],
						'customer_id'=>$arrOptions['customer_id'],
						'customer_table'=>$this->arrConfig['customer_table'],
						'customer'=>$this->arrConfig['customer_value'],
						'customer_key'=>$this->arrConfig['customer_key']
						);

		$arrData = (array)DB::getPreparedQueryData($strSql, $arrTransfer);

		return $arrData;
	}

	public function getAssignedTickets()
	{
		global $user_data;

		$sSQL = "
			SELECT
				*,
				UNIX_TIMESTAMP(`due_date`) `due_date`
			FROM
				`office_ticket_light_entries`
			WHERE
				`state`				!= 'done'
					AND
				`state`				!= 'closed'
					AND
				`assigned_user_id`	= :iUserID
					AND
				UNIX_TIMESTAMP(`due_date`) <= :iTime
		";
		$aSQL = array('iUserID' => $user_data['id'], 'iTime' => time());
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aResult;
	}

	function getTicketDisplay($arrTicketData) {
		
		$arrCustomers = $this->getCustomers();
		$arrStates = $this->getStates();
		$arrPriorities = $this->getPriorities();
		$arrUsers = $this->getUserList();
		
		$arrTicketData['customer'] = $arrCustomers[$arrTicketData['customer_id']];
		$arrTicketData['assigned_user'] = $arrUsers[$arrTicketData['assigned_user_id']];
		$arrTicketData['creator_user'] = $arrUsers[$arrTicketData['creator_user_id']];
		$arrTicketData['priority_color'] = $this->getPriorityColorStyle($arrTicketData['priority']);
		$arrTicketData['priority'] = $arrPriorities[$arrTicketData['priority']];
		$arrTicketData['state'] = $arrStates[$arrTicketData['state']];

		return $arrTicketData;
		
	}

	function getPriorities() {
		$arrPriorities = array();
		$arrPriorities[0] 	= 'keine';
		$arrPriorities[20] 	= 'niedrig';
		$arrPriorities[40] 	= 'normal';
		$arrPriorities[60] 	= 'hoch';
		$arrPriorities[80] 	= 'dringend';
		$arrPriorities[100] = 'sofort';
		return $arrPriorities;
	}

	function getUserList() {

		if(empty($this->_aTables['users'])) {
			
			$objUser = new user_data();
			$arrData = $objUser->get_list();
			$this->_aTables['users'] = array();
			foreach((array)$arrData as $arrItem) {
				$this->_aTables['users'][$arrItem['id']] = $arrItem['firstname']." ".$arrItem['lastname'];
			}
			asort($this->_aTables['users'], SORT_LOCALE_STRING);
			
		}
			
		return $this->_aTables['users'];
	}

	function saveTicket($arrData) {
		global $session_data, $user_data, $objWebDynamics;

		$objDb = DB::getDefaultConnection();

		$arrTransfer = array(
						'intCustomerId'=>(int)$arrData['customer_id'],
						'intDocumentId'=>(int)$arrData['document_id'],
						'intDueDate'=>strtotimestamp($arrData['due_date'], 1),
						'strState'=>(string)$arrData['state'],
						'intPriority'=>(int)$arrData['priority'],
						'intAssignedUserId'=>(int)$arrData['assigned_user_id'],
						'strTitle'=>(string)$arrData['headline'],
						'strDescription'=>(string)$arrData['description']
						);
						
		if(isset($arrData['id']) && $arrData['id'] > 0) {
			$strSql = "
					UPDATE 
						office_ticket_light_entries 
					SET
						changed = NOW(),
						active = 1,
						due_date = :intDueDate,
						state = :strState,
						priority = :intPriority,
						assigned_user_id = :intAssignedUserId,
						document_id = :intDocumentId,
						customer_id = :intCustomerId,
						headline = :strTitle,
						description = :strDescription
					WHERE
						id = :intTicketId
					";
			$arrTransfer['intTicketId'] = $arrData['id'];
			$arrData = $objDb->preparedQueryData($strSql, $arrTransfer);
			$intTicketId = $arrData['id'];
		} else {
			$strSql = "
					INSERT INTO 
						office_ticket_light_entries 
					SET
						changed = NOW(),
						created = NOW(),
						active = 1,
						due_date = :intDueDate,
						state = :strState,
						priority = :intPriority,
						assigned_user_id = :intAssignedUserId,
						creator_user_id = :intCreatorUserId,
						document_id = :intDocumentId,
						customer_id = :intCustomerId,
						headline = :strTitle,
						description = :strDescription
					";
			$arrTransfer['intCreatorUserId'] = (int)$user_data['id'];
			$arrData = $objDb->preparedQueryData($strSql, $arrTransfer);
			$intTicketId = $objDb->getInsertID();
		}

		\System::wd()->executeHook('office_tickets_save_ticket', $arrTransfer);
	
		return $intTicketId;

	}

	function setTicketState($intTicketId, $strState) {
		global $user_data;

		$objDb = DB::getDefaultConnection();

		$arrTransfer = array(
						'strState'=>$strState,
						'intTicketId'=>$intTicketId
						);
						
		$strSql = "
					UPDATE 
						office_ticket_light_entries 
					SET
						state = :strState
					WHERE
						id = :intTicketId
					";
		$arrData = $objDb->preparedQueryData($strSql, $arrTransfer);

	}

	function sendMessage($intTicketId, $bComment=false) {
		global $system_data, $user_data;
		
		$arrTicket = $this->getTicket($intTicketId);
		
		$objAssignedUser = new user_data($arrTicket['assigned_user_id']);
		$objCreatorUser = new user_data($arrTicket['creator_user_id']);
		$objCurrentUser = new user_data($user_data['id']);
		
		if($bComment) {
			if(
				$user_data['id'] == $arrTicket['creator_user_id']
			) {
				$strEmail = $objAssignedUser->get_data('email');
			} else {
				$strEmail = $objCreatorUser->get_data('email');
			}
			$strSubject = $system_data['project_name']." - Ticket (".$arrTicket['id'].") wurde von ".$objCurrentUser->get_data('firstname')." ".$objCurrentUser->get_data('lastname')." kommentiert";
			$strReplyTo = $objCurrentUser->get_data('email');
		} elseif($arrTicket['state'] == 'new') {
			$strSubject = $system_data['project_name']." - Ticket (".$arrTicket['id'].") wurde von ".$objCreatorUser->get_data('firstname')." ".$objCreatorUser->get_data('lastname')." erstellt";
			$strEmail = $objAssignedUser->get_data('email');
			$strReplyTo = $objCreatorUser->get_data('email');
		} elseif($arrTicket['state'] == 'confirmed') {
			$strSubject = $system_data['project_name']." - Ticket (".$arrTicket['id'].") wurde von ".$objAssignedUser->get_data('firstname')." ".$objAssignedUser->get_data('lastname')." bestätigt";
			$strEmail = $objCreatorUser->get_data('email');
			$strReplyTo = $objAssignedUser->get_data('email');
		} elseif($arrTicket['state'] == 'done') {
			$strSubject = $system_data['project_name']." - Ticket (".$arrTicket['id'].") wurde von ".$objAssignedUser->get_data('firstname')." ".$objAssignedUser->get_data('lastname')." erledigt";
			$strEmail = $objCreatorUser->get_data('email');
			$strReplyTo = $objAssignedUser->get_data('email');
		} elseif($arrTicket['state'] == 'closed') {
			$strSubject = $system_data['project_name']." - Ticket (".$arrTicket['id'].") wurde von ".$objCreatorUser->get_data('firstname')." ".$objCreatorUser->get_data('lastname')." geschlossen";
			$strEmail = $objAssignedUser->get_data('email');
			$strReplyTo = $objCreatorUser->get_data('email');
		}

		// Prepare the document data if the ticket is linked with a document
		$sDocument = '---';
		if($arrTicket['document_id'] > 0)
		{
			global $aTypeNames;

			$oDocument = new Ext_Office_Document($arrTicket['document_id']);
			$sDocument = $aTypeNames[$oDocument->type] . ' Nr. '.$oDocument->number . ' vom ' . date('d.m.Y', $oDocument->date);
		}

		$arrTicket = $this->getTicketDisplay($arrTicket);
		$arrComments = $this->getComments($arrTicket['id']);

		$strBody = "\n";
		$strBody .= "Kunde:            ".$arrTicket['customer']."\n";
		$strBody .= "Fälligkeitsdatum: ".strftime("%x %X", $arrTicket['due_date'])."\n";
		$strBody .= "Status:           ".$arrTicket['state']."\n";
		$strBody .= "Priorität:        ".$arrTicket['priority']."\n";
		$strBody .= "Dokument:         ".$sDocument."\n";
		$strBody .= "Bearbeiter:       ".$arrTicket['assigned_user']."\n";
		$strBody .= "Berichtet von:    ".$arrTicket['creator_user']."\n";
		$strBody .= "\n---------------------------------------------------\n\n";
		$strBody .= "Titel:            ".$arrTicket['headline']."\n";
		$strBody .= "Beschreibung:\n	".$arrTicket['description']."\n";

		if(!empty($arrComments)) {
			$strBody .= "\n---------------------------------------------------\n\n";
			$strBody .= "Kommentare\n\n";
			foreach((array)$arrComments as $arrComment) {
				$strBody .= "Von:              ".$arrComment['firstname']." ".$arrComment['lastname']."\n";
				$strBody .= "Datum:            ".strftime("%x %X", $arrComment['created'])."\n";
				$strBody .= "Kommentar:\n".$arrComment['comment']."\n\n";
			}
		}

		wdmail($strEmail, $strSubject, $strBody, false, false, false, $strReplyTo);

	}

	function getStates() {
		
		$arrStates = array();
		$arrStates['new'] 		= 'neu';
		$arrStates['confirmed'] = 'bestätigt';
		$arrStates['done'] 		= 'erledigt';
		$arrStates['closed'] 	= 'geschlossen';

		return $arrStates;
	}

	function writeTicketBox($strHeadline, $arrOptions, $bolFullbox=1) {
		
		$arrTickets = $this->getTickets($arrOptions);
		
		$arrPriorities = $this->getPriorities();
		
		if($bolFullbox) {
		?>
			<div class="infoBox">
				<h1><?=$strHeadline?></h1>
				<div class="infoBoxAdditional">(<?=count($arrTickets)?> Ticket<?=((count($arrTickets)!=1)?"s":"")?>)</div>
				<p>
		<?
		}
		?>
					<table cellspacing=0 cellpadding=0 border=0 width="95%">
                  		<tr>
                  			<th width="15%">Priorität</th>
                  			<th width="45%">Titel</th>
                  			<th width="25%">Kunde</th>
                  			<th width="15%">Fälligkeit</th>
                  		</tr>
<?
					foreach((array)$arrTickets as $arrTicket) {
						if (strlen($arrTicket['headline']) < 1) { $arrTicket['headline'] = '<i>keine Angabe</i>'; }
						else { $arrTicket['headline'] = \Util::convertHtmlEntities($arrTicket['headline']); }
?>
						<tr>
							<td <?=$this->getPriorityColorStyle($arrTicket['priority'])?>><?=$arrPriorities[$arrTicket['priority']]?></td>
							<td><a href="/admin/extensions/office_tickets_light.html?task=detail&id=<?=$arrTicket['id']?>"><?=$arrTicket['headline']?></a></td>
							<td><?=$arrTicket['customer']?></td>
							<td <?=(($arrTicket['due_date'] <= time())?'style="color: red;"':"")?> align="right"><?=strftime("%x", $arrTicket['due_date'])?></td>
						</tr>
<?
					}
?>
                </table>
<?
		if($bolFullbox) {
?>
              </p>
            </div>
		
		<?
		}
	}

	function getPriorityColorStyle($intPriority) {

		$arrColors = array();
		$arrColors[0] = 'style="color: lime;"';
		$arrColors[20] = 'style="color: green;"';
		$arrColors[40] = '';
		$arrColors[60] = 'style="color: orange;"';
		$arrColors[80] = 'style="color: darkorange;"';
		$arrColors[100] = 'style="color: red;"';

		\System::wd()->executeHook('office_tickets_get_colorstyle', $arrColors);

		if (array_key_exists($intPriority, $arrColors)) {
			return $arrColors[$intPriority];
		} else {
			return false;
		}

	}

	public function getComments($iTicketId) {
		
		$sSql = "
					SELECT 
						otc.*,
						su.firstname,
						su.lastname,
						UNIX_TIMESTAMP(`otc`.`created`) `created`
					FROM
						office_ticket_light_comments otc LEFT OUTER JOIN
						system_user su ON
							`otc`.`user_id` = `su`.`id`
					WHERE
						`otc`.`ticket_id` = :ticket_id AND
						`otc`.`active` = 1
					ORDER BY
						`otc`.`created` ASC
					";
		$aSql = array('ticket_id'=>(int)$iTicketId);
		$aComments = DB::getPreparedQueryData($sSql, $aSql);
		return $aComments;
		
	} 

	public function addComment($iTicketId, $sComment) {
		global $user_data;
		
		$sSql = "
					INSERT INTO
						office_ticket_light_comments
					SET
						`changed` = NOW(),
						`created` = NOW(),
						`active` = 1,
						`ticket_id` = :ticket_id,
						`user_id` = :user_id,
						`comment` = :comment
					";
		$aSql = array(
						'ticket_id'=>(int)$iTicketId,
						'user_id'=>(int)$user_data['id'],
						'comment'=>(string)$sComment
					);
		DB::executePreparedQuery($sSql, $aSql);

	}

}
