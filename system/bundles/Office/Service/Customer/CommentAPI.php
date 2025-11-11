<?php

namespace Office\Service\Customer;

use Office\Entity\Customer\Comment;
use Office\Service\LogoService;

class CommentAPI {

	/**
	 * Die Rückgabedaten.
	 * @var array
	 */
	private $_aReturnData = array();

	/**
	 * <p>
	 * Gibt ein array mit allen Kommentaren der Kunden und deren Logos zurück.<br />
	 * Falls Fehler vorhanden sind, wird statt den Kommentaren ein Fehler
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
	 * <td>no_comments_found</td>
	 * <td><p>
	 * In dieser Kundengruppe sind zwar Kunden vorhanden, doch keiner dieser
	 * Kunden hat einen Kommentar, der als sichtbarer Stadort gekennzeichnet ist.
	 * </p>
	 * </td>
	 * </tr>
	 * </table>
	 * @return array <p>
	 * Die Kommentardaten aller Kunden.
	 * </p>
	 */
	public function getComments(\MVC_Request $oRequest) {
		// Bereite die Rückgabe vor
		$this->_prepareReturnData($oRequest);

		// Gib das Ergebnis zurück
		return $this->_aReturnData;
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
		if (
				$sCustomerGroupId === null ||
				!is_numeric($sCustomerGroupId)
		) {
			// Ein Fehler hinzufügen und die Methode abbrechen (mit return)
			$this->_aReturnData['errors'] = 'http_get_parameter';
			return;
		}

		// Alle Sichtbaren Kommentare hinzufügen
		$this->_fillReturnDataWithComments($oRequest);

		if (empty($this->_aReturnData['aComments'])) {
			// Ein Fehler hinzufügen und die Methode abbrechen (mit return)
			$this->_aReturnData['errors'][] = 'no_comments_found';
			return;
		}

		// Kommentar für die Box hinzufügen
		$this->_fillReturnDataWithCommentForBox($oRequest);
	}

	/**
	 * Gibt ein assoziatives Array mit den wichtigsten Daten der Kommentare zurück.
	 * 
	 * @param array $aComments Alle Kommentare (\WDBasic-Objekte)
	 * @return array Das neue Array mit den wichtigesten Daten.
	 */
	private function _fillReturnDataWithComments(\MVC_Request $oRequest) {
		// Die Kriterien für die Suche aller sichtbaren Kommentare
		$aCriteria = $this->_getCriteria($oRequest);

		// Alle sichtbaren Kommentare finden
		$aComments = Comment::getRepository()->findBy($aCriteria);

		// Kommentare dem Rückgabewert hinzufügen
		foreach ($aComments as $oComment) {
			
			$oCustomer = new \Ext_Office_Customer('office_customers', $oComment->customer_id);

			$oLogoService = new LogoService();
			$sCustomerLogoWebPath = $oLogoService->getWebPath($oCustomer);

			$this->_aReturnData['aComments'][$oComment->position]['company'] = $oCustomer->company;
			$this->_aReturnData['aComments'][$oComment->position]['text'] = $oComment->text;
			$this->_aReturnData['aComments'][$oComment->position]['logo'] = $sCustomerLogoWebPath;
		}
	}

	private function _fillReturnDataWithCommentForBox(\MVC_Request $oRequest) {
		// Die Kriterien für die Suche aller sichtbaren Kommentare
		$aCriteria = $this->_getCriteria($oRequest);
		// Nur die als in der Box sichtbar gekennzeichneten
		$aCriteria['box'] = true;

		// Alle sichtbaren kommentare und die die in der box sein können finden
		$aComments = Comment::getRepository()->findBy($aCriteria);

		$aCommentsForBox = array();
		foreach ($aComments as $oComment) {

			$oCustomer = new \Ext_Office_Customer('office_customers', $oComment->customer_id);

			$oLogoService = new LogoService();
			$sCustomerLogoWebPath = $oLogoService->getWebPath($oCustomer);

			$aCommentsForBox[] = array(
				$oComment->id => array(
					'company' => $oCustomer->company,
					'text' => $oComment->text,
					'logo' => $sCustomerLogoWebPath
				)
			);
		}
		
		// Füge zufällig ein Kommentar hinzu
		if(!empty($aCommentsForBox)){
			$this->_aReturnData['commentForBox'] = $aCommentsForBox[rand(0, count($aCommentsForBox) - 1)];
		}
	}

	private function _getCriteria(\MVC_Request $oRequest) {
		// Die Id der gesuchten Kundengruppe
		$iCustomerGroupId = (int) $oRequest->get('customer_group_id');
		// Die Kriterien für die Suche aller sichtbaren Kommentare
		$aCriteria = array('visible' => true);
		/**
		 * Wenn nicht alle sichtbaren Kommentare gesucht werden sollen, sondern
		 * nur die einer bestimmten Kundengruppe, dann erweitere die Kriterien
		 * so, dass auch nur die sichtbaren Kommentare der bestimmten Kundengruppe
		 * gesucht werden sollen
		 */
		if ($iCustomerGroupId !== 0) {
			// Damit nur die aktiven Kommentare einer bestimmten Kundengruppe gesucht werden
			$aCriteria['customer_group_id'] = $iCustomerGroupId;
		}

		return $aCriteria;
	}

}
