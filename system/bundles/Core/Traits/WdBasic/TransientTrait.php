<?php

namespace Core\Traits\WdBasic;

/**
 * Flüchtige Daten ins Model setzen, die nur für die Dauer des Requests existieren
 *
 * Der Sinn hiervon ist, dass __set() der WDBasic alles blockiert, was es nicht kennt.
 */
trait TransientTrait {

	public $transients = [];

}