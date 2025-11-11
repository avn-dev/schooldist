<?php

namespace Gui2\Dialog;

/**
 * @TODO Wird bisher nicht benutzt und erfordert noch weitere Anpassungen
 */
interface LazyFactoryInterface extends FactoryInterface {

	/**
	 * Dialog nach create() vorbereiten: Entität wurde bereits gesetzt
	 */
	public function prepare(\Ext_Gui2 $gui): void;

}
