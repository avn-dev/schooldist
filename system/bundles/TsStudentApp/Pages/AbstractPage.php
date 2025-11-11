<?php

namespace TsStudentApp\Pages;

use TsStudentApp\AppInterface;

/**
 * Sobald die Klasse eine refresh()-Methode hat wird automatisch bei dem Seitenaufruf in der App ein request abgeschickt
 * wenn die Seite abgelaufen ist (siehe config.php -> refresh_after)
 *
 * @package TsStudentApp\Pages
 */
abstract class AbstractPage {

	/**
	 * Übersetzungen mitschicken
	 *
	 * @param AppInterface $appInterface
	 * @return array
	 */
	public function getTranslations(AppInterface $appInterface): array {
		return [];
	}

	/**
	 * Farben überschreiben
	 *
	 * @param AppInterface $appInterface
	 * @return array
	 */
	public function getColors(AppInterface $appInterface): array {
		return [];
	}

}
