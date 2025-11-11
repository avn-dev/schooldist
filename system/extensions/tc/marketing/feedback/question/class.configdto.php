<?php

class Ext_TC_Marketing_Feedback_Question_ConfigDTO {

	/**
	 * Abhängigkeit auf 1. Ebene (Beispiel: Schulen und fixe Typen)
	 *
	 * @var bool
	 */
	public $bDependencies = false;

	/**
	 * Abhängigkeit auf 2. Ebene
	 *
	 * @var bool
	 */
	public $bSubDependencies = false;

	/**
	 * In andere Methode reinspringen (hätte man über die Config auch ohne Zweiteilung machen können)
	 *
	 * @var bool
	 */
	public $bDependencyObject = false;

}
