<?php

namespace TsPrivacy\Interfaces;

interface Purge {

	/**
	 * Methode zum Anonymisieren bzw. wirklichen Löschen des Objekts
	 *
	 * @param bool $bAnonymize
	 */
	public function purge($bAnonymize = false);

}
