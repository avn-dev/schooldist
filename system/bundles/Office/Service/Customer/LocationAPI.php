<?php

namespace Office\Service\Customer;

use Office\Entity\Customer\Location;
use Office\Service\LogoService;

class LocationAPI {

	/**
	 * Die Rückgabedaten.
	 * @var array
	 */
	private $_aReturnData = array();

	/**
	 * <p>
	 * Gibt ein array mit allen Standorten der Kunden und deren Logos zurück.<br />
	 * Falls Fehler vorhanden sind, wird statt den Standorten ein Fehler
	 * zurückgegeben. Im folgenden eine Liste mit den möglichen Fehlerncodes und
	 * deren Bedeutung:
	 * </p>
	 * <table>
	 * <tr valign="top">
	 * <td>Fehlercode</td>
	 * <td>Bedeutung</td>
	 * </tr>
	 * <tr valign="top">
	 * <td>http_get_parameter</td>
	 * <td><p>
	 * Entweder fehlt der HTTP-Get-Parameter "customer_group_id", oder dessen
	 * Wert ist keine Nummer.
	 * </p></td>
	 * </tr>
	 * <tr valign="top">
	 * <td>no_locations_found</td>
	 * <td><p>
	 * In dieser Kundengruppe sind zwar Kunden vorhanden, doch keiner dieser
	 * Kunden hat einen Standort, der als sichtbarer Stadort gekennzeichnet ist.
	 * </p>
	 * </td>
	 * </tr>
	 * </table>
	 * @param \MVC_Request $oRequest
	 * @return array <p>
	 * Die Standortdaten aller Kunden.
	 * </p>
	 */
	public function getLocations(\MVC_Request $oRequest) {

		// Bereite die Rückgabe vor
		$this->_prepareReturnData($oRequest);

		// Gib das Ergebnis zurück
		return $this->_aReturnData;
	}

	/**
	 * Speichert die Latitude- und Longitude Koordinaten zu einem Standort.
	 * 
	 * @param \MVC_Request $oRequest Das Requiest-Objekt.
	 * @return array Ein Array mit möglichen Fehlern.
	 */
	public function saveLatLng(\MVC_Request $oRequest) {

		$aGetData = $oRequest->getAll();

		$sLocationLatitude = $aGetData['lat'];
		$sLocationLongitude = $aGetData['lng'];
		$iLocationId = $aGetData['location_id'];

		// Wenn etwas fehlt
		if(
			$sLocationLatitude === null ||
			$sLocationLongitude === null ||
			$iLocationId === null	
		) {
			return array('errors' => 'missing_post_data');
		}

		/* @var $oLocation \Office\Entity\Customer\Location */
		$oLocation = \Office\Entity\Customer\Location::getInstance($iLocationId);

		// Wenn eine Koordinate leer ist.
		if(
			$oLocation->latitude === '' ||
			$oLocation->longitude === ''
		) {
			$oLocation->latitude = $sLocationLatitude;
			$oLocation->longitude = $sLocationLongitude;
			$oLocation->save();
		}

	}

	/**
	 * Bereitet die Rückgabe vor und schreibt die Rückgabewerte in das private
	 * Array "$_aReturnData".
	 * 
	 * @param \MVC_Request $oRequest Das Requestobjekt.
	 * @return null Bricht ab, wenn ein Fehler auftritt und gibt nichts zurück.
	 */
	private function _prepareReturnData(\MVC_Request $oRequest) {

		// Die Id der gesuchten Kundengruppe (GET-Parameter)
		$sCustomerGroupId = $oRequest->get('customer_group_id');
		// Wenn keine Grundengruppe angegeben ist oder der Wert keine Zahl ist
		if(
			$sCustomerGroupId === null ||
			!is_numeric($sCustomerGroupId)
		) {
			// Ein Fehler hinzufügen und die Methode abbrechen (mit return)
			$this->_aReturnData['errors'] = 'http_get_parameter';
			return;
		}

		// Alle Sichtbaren Standorte holen
		$aLocations = $this->_getVisibleLoactions($oRequest);

		if(empty($aLocations)) {
			// Ein Fehler hinzufügen und die Methode abbrechen (mit return)
			$this->_aReturnData['errors'][] = 'no_locations_found';
			return;
		}

		$this->_aReturnData['aLocations'] = $aLocations;
	}

	/**
	 * <p>
	 * Gibt die wichtigsten Informationen der als sichtbar gekennzeichneten
	 * Standorte zurück.
	 * </p>
	 * @param \MVC_Request $oRequest
	 * @return array <p>
	 * Die Standorte mit deren wichtigsteln Informationen.
	 * </p>
	 */
	private function _getVisibleLoactions(\MVC_Request $oRequest) {

		// Ein Repository der Standorte Instanziieren
		$oLocationRepository = Location::getRepository();
		// Die Id der gesuchten Kundengruppe
		$iCustomerGroupId = (int) $oRequest->get('customer_group_id');
		// Die Kriterien für die Suche aller sichtbaren Standorte
		$aCriteria = array(
			'visible' => true
		);
		/**
		* Wenn nicht alle sichtbaren Standorte gesucht werden sollen, sondern
		* nur die einer bestimmten Kundengruppe, dann erweitere die Kriterien
		* so, dass auch nur die aktiven Standorte der bestimmten Kundengruppe
		* gesucht werden sollen
		*/
		if($iCustomerGroupId !== 0) {
			// Damit nur die aktiven Standorte einer bestimmten Kundengruppe gesucht werden
			$aCriteria['customer_group_id'] = $iCustomerGroupId;
		}

		// Alle als "Auf der Karte sichtbar" gekennzeichneten Standorte finden
		$aLocations = $oLocationRepository->findBy($aCriteria);

		$aMinimizedLocations = $this->_minimizeLocations($aLocations);

		return $aMinimizedLocations;
	}

	/**
	 * Gibt ein assoziatives Array mit den wichtigsten Daten der Standorte zurück.
	 * 
	 * @param array $aLocations Alle Standorte (\WDBasic-Objekte)
	 * @return array Das neue Array mit den wichtigesten Daten.
	 */
	private function _minimizeLocations($aLocations) {

		$aMinimizedLocations = array();
		foreach ($aLocations as $oLocation) {

			// Die Latitude- und Longitude Koordinaten
			$sLocationLatitude = $oLocation->latitude;
			$sLocationLongitude = $oLocation->longitude;

			// Wenn bereits Latitude - und Longitude-Koordinaten existieren
			if(
				$sLocationLatitude !== '' &&
				$sLocationLongitude !== ''
			){
				// dann füge diese hinzu
				$aMinimizedLocations[$oLocation->id]['latitude'] = $sLocationLatitude;
				$aMinimizedLocations[$oLocation->id]['longitude'] = $sLocationLongitude;
			} else {
				// sonst füge die Stadt, PLZ und das Land hinzu
				$aMinimizedLocations[$oLocation->id]['city'] = $oLocation->city;
				$aMinimizedLocations[$oLocation->id]['zip'] = $oLocation->zip;
				$aMinimizedLocations[$oLocation->id]['country'] = $oLocation->country;
			}

			// Standortlogo
			$sLogoWebPath = '';
			// Standortname
			$sLogoName = '';
			// Wenn dieser Standort ein Anonymer ist, dann benutze die Standarddaten
			$bIsAnonymous = (bool)$oLocation->anonymous;
			if($bIsAnonymous) {
				// Standardname aus der Konfiguration
				$sLogoName = \Ext_Office_Config::get('customers_locations_standard_name_' . $oLocation->customer_group_id);
				// Standardlogo aus der Konfiguration
				$sLogoWebPath = \Ext_Office_Config::get('customers_locations_standard_logo_' . $oLocation->customer_group_id);
			} else {
				// Standortname
				$sLogoName = $oLocation->name;
				// Standortlogo
				$sLogoWebPath = $this->_getLogoWebPath($oLocation);	
			}

			// Füge den Namen hinzu
			$aMinimizedLocations[$oLocation->id]['name'] = $sLogoName;
			// Füge das den relativen Pfad des Logos hinzu
			$aMinimizedLocations[$oLocation->id]['logo'] = $sLogoWebPath;
		}

		return $aMinimizedLocations;
	}

	/**
	 * Gibt den relativen Pfad zum Logo zurück.
	 * 
	 * @param \Office\Entity\Customer\Location $oLocation Das Standort-Entity.
	 * @return string Der relative Pfad zum Logo.
	 */
	private function _getLogoWebPath(Location $oLocation) {

		$oLogoService = new LogoService();
		$sLogoWebPath = $oLogoService->getWebPath($oLocation);

		// Wenn kein Standortlogo vorhanden ist, dann schaue, ob ein Kundenlogo
		// vorhanden ist und benutze es. (Ist null, falls kein Kundenlogo vorhanden ist)
		if ($sLogoWebPath === null) {
			$sLogoWebPath = $this->_getCustomerLogoWebPath($oLocation);
		}

		return $sLogoWebPath;
	}

	/**
	 * Gibt den relativen Pfad zum Kundenlogo zurück.
	 * 
	 * @param \Office\Entity\Customer\Location $oLocation Das Standort-Entity.
	 * @return string	Der relative Pfad des Kundenlogos, falls vorhanden, sonst 
	 *					<b>null</b>.
	 */
	private function _getCustomerLogoWebPath(Location $oLocation) {

		$oOffice = new \Ext_Office_Customer('office_customers', $oLocation->customer_id);
		$oLogoService = new LogoService();
		$sCustomerLogoWebPath = $oLogoService->getWebPath($oOffice);

		return $sCustomerLogoWebPath;
	}

}