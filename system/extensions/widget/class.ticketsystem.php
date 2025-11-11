<?
class Ext_Widget_Ticketsystem {

	private $_bLogin = false;
	private $_iProject = 0;
	private $_iTicket = 0;
	private $_sStatus = '1';
	private $_aErrors = array();

	public function  __construct() {
		global $user_data;
		
		if($user_data['id'] > 0){
			$this->_bLogin = true;
		} else {
			$this->_aErrors[] = 'Der Login ist Fehlgeschlagen!';
		}

	}

	public function setProject($iProject){
		if($iProject > 0){
			$this->_iProject = $iProject;
		}
	}

	public function setStatus($sStatus){
		$this->_sStatus = $sStatus;
	}

	public function setTicket($iTicket){
		$this->_iTicket = $iTicket;
	}

	public function getCustomerID(){
		global $user_data;
		return $user_data['data']['customer_id'];
	}

	public function getStatusList(){
		$aStates = Ext_Office_Tickets::getStates();
		$aStates[''] = 'Alle';
		return $aStates;
	}

	public function getProjects(){
		$oTemp = new Ext_Office_Tickets_Backend();
		$aResult = $oTemp->getProjects();

		$aProjects = array(0 => '');

		foreach((array)$aResult as $iKey => $aProject)
		{
			$aProjects[$aProject['id']] = $aProject['title'];
		}

		return $aProjects;
	}

	public function getCustomerProjects(){

		$sSQL = "
			SELECT `id`, `title`
			FROM `office_projects`
			WHERE
				`active` = 1 AND
				`customer_id` = :iCustomerID AND
				UNIX_TIMESTAMP(`closed_date`) = 0
			ORDER BY UPPER(`title`)
		";
		$aResult = DB::getPreparedQueryData($sSQL, array('iCustomerID' => $this->getCustomerID()));

		$aProjects = array(0 => '');
		
		foreach((array)$aResult as $iKey => $aProject)
		{
			$aProjects[$aProject['id']] = $aProject['title'];
		}

		return $aProjects;
	}

	public function getTicket($aTickets){
		$aTicket = array();

		if(
			$this->_iTicket > 0 &&
			key_exists($this->_iTicket, $aTickets)
		){
			$oTicket = new Ext_Office_Tickets($this->_iTicket);
			$aTicket['id'] = $oTicket->id;
			$aNotices = $oTicket->getNotices();
			$aStates = $this->getStatusList();
			$aStates[''] = 'Neu';
			foreach((array)$aNotices as $iKey => $aNotice){
				if(empty($aNotices[$iKey]['user'])){
					$aNotices[$iKey]['user'] = 'Unbekanter Benutzer';
				}
				$aNotices[$iKey]['date'] = strftime('am %x um %X', $aNotice['created']);
				$aNotices[$iKey]['status'] = $aStates[$aNotice['state']];
			}
			$aTicket['notices'] = $aNotices;
		}
		
		return $aTicket;
	}

	public function getTickets(){

		$aTickets = array(0 => '');

		if($this->_iProject > 0){
			$aSql = array();
			$sSql = "SELECT 
							`ot`.`id`,
							`ot`.`title`
						FROM
							#table `ot` LEFT JOIN
							`office_ticket_notices` `otn` ON
								`otn`.`ticket_id` = `ot`.`id` AND
								`otn`.`id` = (
									SELECT
										`id`
									FROM
										`office_ticket_notices`
									WHERE
										`ticket_id` = `ot`.`id`	
									ORDER BY
										`id` DESC
									LIMIT 1
								)
						WHERE
							`ot`.`active` = 1 AND
							`ot`.`project_id` = :project_id ";
			
			if($this->_sStatus != ""){
				$sSql .= " AND `otn`.`state` = :status ";
				$aSql['status'] = (int)$this->_sStatus;
			}

			$sSql .= "
						GROUP BY
							`ot`.`id`
						ORDER BY 
							`ot`.`position`";
			
			$aSql['table'] = 'office_tickets';
			$aSql['project_id'] = (int)$this->_iProject;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			foreach((array)$aResult as $aData){
				$aTickets[$aData['id']] = '[T-'.$aData['id'].'] '.$aData['title'];
			}
		}

		return $aTickets;
	}

	public function getJSON(){

		// Initalisieren
		$aTransfer = array();
		$aTransfer['errors'] = array();
		$aTransfer['aStatusList'] = array();
		$aTransfer['aProjects'] = array();
		$aTransfer['aTickets'] = array();
		$aTransfer['aTicket'] = array();
		$aTransfer['login'] = 0;

		if(empty($this->_aErrors)){
			// Flags / IDS
			$aTransfer['sStatus'] = $this->_sStatus;
			$aTransfer['iProject'] = $this->_iProject;

			// Daten
			$aTransfer['aStatusList'] = $this->getStatusList();
			$aTransfer['aProjects'] = $this->getProjects();
			$aTransfer['aTickets'] = $this->getTickets();
			$aTransfer['aTicket'] = $this->getTicket($aTransfer['aTickets']);
		}
		// Werte setzten
		if(empty($this->_aErrors)){
			$aTransfer['login'] = 1;
		} else {
			$aTransfer['errors'] = $this->_aErrors;
		}

		//Rückgabe
		return json_encode($aTransfer);
	}
}