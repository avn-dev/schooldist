<?php

namespace Core\Controller;

use Core\Service\RoutingService;

/**
 * <p>
 * Diese Klasse ermöglicht es, ein Routingupdate über einen Link durchzuführen.
 * Um auf die Methoden dieser Klasse zugreifen zu können, wird das
 * "update"-Recht benötigt.
 * </p>
 */
class RoutingController extends \MVC_Abstract_Controller {

	/**
	 * <p>
	 * Variable setzen, um sicher zu stellen, dass der Zugreifer das
	 * update-Recht hat.
	 * <p>
	 * @var string
	 */
	protected $_sAccessRight = 'update';

	/**
	 * <p>
	 * Diese Methode ermöglicht es, ein Composerupdate über den folgenden Link
	 * durchzuführen: <i>.../wdmvc/core/routing/update</i>
	 * </p>
	 */
	public function updateAction(){
		// Ein Updateobjekt instanziieren
		$oRoutingService = new RoutingService();
		// Führe das Update durch und speichere den Erfolg
		$bSuccess = $oRoutingService->buildRoutes();
		
		// Setze ob das Update erfolgreich war oder nicht. Die Rückgabe
		// erfolgt als JSON String.
		if($bSuccess){
			$this->set('success', true);
		} else {
			$this->set('success', false);
		}
	}
}