<?php

namespace TsRegistrationForm\Interfaces;

use Tc\Service\Language\Frontend;

/**
 * Gemeinsames Interface für Kombinationen von V2 und V3
 */
interface RegistrationCombination  {

	/**
	 * Form-Objekt der Kombination
	 *
	 * @return \Ext_Thebing_Form
	 */
	public function getForm(): \Ext_Thebing_Form;

	/**
	 * Schul-Objekt der Kombination
	 *
	 * @return \Ext_Thebing_School
	 */
	public function getSchool(): \Ext_Thebing_School;

	/**
	 * Sprach-Objekt der Kombination
	 *
	 * @return Frontend
	 */
	public function getLanguage(): Frontend;

	/**
	 * @return \Ext_TC_Frontend_Log|null
	 */
	public function getFrontendLog(): ?\Ext_TC_Frontend_Log;

	/**
	 * @see \Ext_TC_Frontend_Combination_Abstract::log()
	 *
	 * @param $message
	 * @param array $data
	 * @param bool $error
	 */
	public function log($message, $data = [], $error = true);

}