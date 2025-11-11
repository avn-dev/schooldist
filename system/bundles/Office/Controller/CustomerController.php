<?php

namespace Office\Controller;

use Office\Service\Customer\LocationAPI;
use Office\Service\Customer\CommentAPI;

class CustomerController extends \MVC_Abstract_Controller{

	/**
	 * Login für diesen Controller ausschalten.
	 */
	protected $_sAccessRight = false;

	/**
	 * Gibt ein String (JSON) mit allen Standorten der Kunden und deren Logos zurück.
	 * Im HTTP-GET-Parameter sollte die Id der Kundengruppe angegeben sein.<br />
	 * Beispiel eines Aufrufs von außen: 
	 * <b>http://framework.dev.box/wdmvc/office/customer/locations?customer_group_id=1</b>
	 * 
	 * @return string JSON
	 */
	public function getLocationsAction(){
		header('Access-Control-Allow-Origin: *');

		$oLocationApi = new LocationAPI();
		$aLocations = $oLocationApi->getLocations($this->_oRequest);
		foreach($aLocations as $sName => $aLocation){
			$this->set($sName, $aLocation);
		}
	}

	/**
	 * Speichert die Standort-Daten ein.
	 * @todo Prefix der Funtion ist "get", warum?
	 */
	public function getLocationAction(){
		header('Access-Control-Allow-Origin: *');

		$oLocationApi = new LocationAPI();
		$oLocationApi->saveLatLng($this->_oRequest);
	}
	
	/**
	 * Gibt ein String (JSON) mit allen Kommentaren der Kunden zurück.
	 * Im HTTP-GET-Parameter sollte die Id der Kundengruppe angegeben sein.<br />
	 * Beispiel eines Aufrufs von außen: 
	 * <b>http://framework.dev.box/wdmvc/office/customer/comments?customer_group_id=1</b>
	 * 
	 * @return string JSON
	 */
	public function getCommentsAction(){
		header('Access-Control-Allow-Origin: *');

		$oCommentApi = new CommentAPI();
		$aComments = $oCommentApi->getComments($this->_oRequest);
		foreach($aComments as $sName => $aComment){
			$this->set($sName, $aComment);
		}
	}
	
	public function getCheckAction() {
		
		$sNumbers = $this->_oRequest->get('numbers');
		$fMinimumAmount = (float)$this->_oRequest->get('minimum_amount');
		$sSubjectFilter = (string)$this->_oRequest->get('subject_filter');
		$aRevenueAccountFilter = (array)$this->_oRequest->input('revenue_account_filter');

		$aNumbers = explode(",", $sNumbers);

		$oExtensionDaoOffice = new \Ext_Office_Dao();
		
		$aCustomers = $oExtensionDaoOffice->getCustomersByNumbers($aNumbers);
		
		$aReceivables = $oExtensionDaoOffice->getReceivables();

		$aDueList = array();
		foreach($aReceivables as $aReceivable) {
			if(!empty($aReceivable['due_date'])) {
				
				// Filter Mindestbetrag
				if(
					!empty($fMinimumAmount) &&
					$aReceivable['receivable'] < $fMinimumAmount
				) {
					continue;
				}

				// Filter Betreff
				if(
					!empty($sSubjectFilter) &&
					strpos($aReceivable['subject'], $sSubjectFilter) === false
				) {
					continue;
				}

				// Filter Erlöskonto
				if(!empty($aRevenueAccountFilter)) {

					$aRevenueAccounts = explode(',', $aReceivable['revenue_accounts']);

					$aIntersect = array_intersect($aRevenueAccounts, $aRevenueAccountFilter);
					if(empty($aIntersect)) {
						continue;
					}

				}
				
				if(isset($aDueList[$aReceivable['customer_id']])) {
					$aDueList[$aReceivable['customer_id']] = min((int)$aDueList[$aReceivable['customer_id']], (int)$aReceivable['due_date']);
				} else {			
					$aDueList[$aReceivable['customer_id']] = (int)$aReceivable['due_date'];
				}
			}
		}

		$aReturn = array();

		// Alle Kunden auf überfällige Rechnungen checken
		foreach($aCustomers as $aCustomer) {
			if(isset($aDueList[$aCustomer['id']])) {
				
				$oDate = new \WDDate($aDueList[$aCustomer['id']]);
			
				// ist die Fälligkeit schon 21 Tage vorbei?
				$oDate->add(28, \WDDate::DAY);

				$iDayDiff = $oDate->getDiff(\WDDate::DAY);
				
				$aReturn[$aCustomer['number']] = $iDayDiff;
				
			}
		}

		$this->set('customer_due_info', $aReturn);
		
		\Log::getLogger('office', 'api')->addInfo('Check payments', [$aNumbers, $aReturn]);
		
	}
	
}