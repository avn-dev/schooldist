<?php

namespace Tc\Controller;

class ZendeskController extends \MVC_Abstract_Controller {

	protected $_sViewClass = '\MVC_View_Smarty';

	public function sso() {

		$sSystem = \Ext_TC_Util::getSystem();

		$oConfig = \Factory::getInstance('Ext_TC_Config');

		$bSkipForm = false;
		
		$sOrganization = $this->_oRequest->get('organization');
		
		// Firmennamen speichern
		if(!empty($sOrganization)) {
			\Ext_TC_Factory::executeStatic('Ext_TC_Util', 'saveClientName', array($sOrganization));	
			$bSkipForm = true;
			$sClientName = $sOrganization;
		} else {
			$sClientName = \Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getClientName');
		}
		//------------------------------------------------------------------------------

		// ZenDesk Daten
		$mZenDeskID = $oConfig->getValue('zendesk_id');

		if(
			!$bSkipForm &&
			(
				empty($mZenDeskID) ||
				$mZenDeskID == 0 ||
				strlen($sClientName) <= 2
			)
		) {

			$this->set('bMissingName', true);
			$this->set('sClientName', $sClientName);

		} else {

			if(empty($mZenDeskID)) {
				$mZenDeskID = 0;
			}

			$iUser = (int)$this->_oAccess->id;
			$oUser = \Ext_TC_User::getInstance($iUser);

			// ZenDesk-Api ansprechen
			$oZenDesk = new \Ext_TC_ZenDesk_Sync($oUser, $sSystem);
			
			try {
				$oZenDesk->sync($sClientName, $mZenDeskID);
			} catch (\Exception $e) {
				$oZenDesk->getErrorCollection()->add('An error occured ('.$e->getMessage().').');
			}

			$oErrorCollection = $oZenDesk->getErrorCollection();

			if($oErrorCollection->isEmpty()) {

				$mZenDeskID = $oZenDesk->getZenDeskId();
				$sClientName = $oZenDesk->getZenDeskOrganizationName();

				// Config aktualisieren
				$oConfig->set('zendesk_id', $mZenDeskID);		
				$oConfig->save();
				// Falls Organisation über Autocomplete gesucht wurde, stimmt evtl der Name nicht ganz 
				\Ext_TC_Factory::executeStatic('Ext_TC_Util', 'saveClientName', array($sClientName));

				// Gibt es eine Weiterleitung auf eine andere Url?
				$sReturnTo = $this->_oRequest->input('r', null);
				// Weiterleitung (SSO mit JWT)		
				$oZenDesk->sso($sReturnTo);

			} else {

				$aErrors = [];
				foreach($oErrorCollection as $oError) {
					$aErrors[] = (string)$oError;
				}
				$this->set('aErrors', $aErrors);

			}

		}

	}
	
}
