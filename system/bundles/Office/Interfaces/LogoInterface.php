<?php

namespace Office\Interfaces;

interface LogoInterface {

	/**
	 * <p>
	 * Der relative Pfad zu dem Verzeichnis, in dem die Logos gespeichert werden.
	 * Wenn das Verzeichnis nicht existiert, dann wird es beim Speichern der
	 * Klasse erstellt.
	 * </p>
	 * @return string <p>
	 * Der relative Pfad zu dem Verzeichnis, in dem die Logos gepseichert sind.
	 * </p>
	 */
	public function getLogoWebDir();

}