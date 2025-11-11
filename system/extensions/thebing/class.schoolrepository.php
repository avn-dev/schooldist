<?php

class Ext_Thebing_SchoolRepository extends WDBasic_Repository {

	/**
	 * Gibt zurück ob es mehr als eine aktive Schule gibt
	 *
	 * @return bool
	 */
	public function hasMoreThanOne() {

		$iNumberOfSchools = $this->countActiveSchools();

		if($iNumberOfSchools > 1) {
			return true;
		}

		return false;

	}

	/**
	 * Zählt alle aktiven Schulen hoch und gibt das Ergebnis zurück
	 *
	 * @return int
	 */
	public function countActiveSchools() {
		$oSchools = $this->findBy(['active' => 1]);
		return count($oSchools);
	}

}