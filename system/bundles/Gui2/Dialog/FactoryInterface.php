<?php

namespace Gui2\Dialog;

/**
 * Mit diesem Interface kann ein Dialog als Klasse – und ohne statische Methode – direkt in einer YML-Datei angegeben werden.
 *
 * Der Konstruktor wird mit DI aufgerufen.
 *
 * @TODO Eine mögliche Erweiterung könnte sein, dass der Dialog durch LazyDialog immer neu erzeugt wird und man dort
 * 		dann immer die aktuelle Entität reingeben könnte. So könnte man den Dialog direkt manipulieren, ohne
 * 		$oDialog->aElements manipulieren zu müssen.
 */
interface FactoryInterface {

	public function create(\Ext_Gui2 $gui): \Ext_Gui2_Dialog;

}