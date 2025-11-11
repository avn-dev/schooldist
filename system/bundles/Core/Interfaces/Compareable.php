<?php

namespace Core\Interfaces;

/**
 * <p>
 * Jede Klasse, die dieses Interface implementiert ist vergleichbar.
 * </p>
 */
interface Compareable {

	/**
	 * Vergleicht dieses Objekt mit einem Array aus diesen Objekten.
	 * Der Übergabeparamenter ist ein Array aus Objekten dieser Klasse.
	 */
	public function compareWithArray(array $aObjects);

	/**
	 * Vergleicht dieses Objekt mit einem Objekt.
	 * Der Übergabeparamenter ist ein Objekten dieser Klasse.
	 */
	public function compareWithObject($oObject);
}
