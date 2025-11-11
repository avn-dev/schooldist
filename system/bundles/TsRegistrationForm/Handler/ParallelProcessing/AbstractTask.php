<?php

namespace TsRegistrationForm\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TsRegistrationForm\Interfaces\RegistrationCombination;

abstract class AbstractTask extends TypeHandler {

	/**
	 * @param array $data
	 * @return RegistrationCombination
	 */
	public function createCombination(array $data): RegistrationCombination {

		/** @var \Ext_TS_Inquiry_Abstract $inquiry */
		$inquiry = \Factory::getInstance($data['object'], $data['object_id']);

		/** @var \Ext_TC_Frontend_Combination $combination */
		$combination = \Factory::getInstance(\Ext_TC_Frontend_Combination::class, $data['combination_id']);

		/** @var RegistrationCombination $generator */
		$generator = $combination->getObjectForUsage();

		$request = new \Illuminate\Http\Request();
		$request->replace(['fields' => ['school' => $inquiry->getSchool()->id]]);

		// corresponding_language: Das Form setzt den Wert automatisch auf die verwendete Sprache
		$generator->initCombination($request, $inquiry->getCustomer()->corresponding_language);

		return $generator;

	}

	/**
	 * @param array $data
	 * @return \Ext_TS_Inquiry
	 */
	public function createObject(array $data): \Ext_TS_Inquiry {

		/** @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Factory::getInstance($data['object'], $data['object_id']);

		if (!$inquiry->exist()) {
			throw new \RuntimeException('Invalid object for form document generation!');
		}

		return $inquiry;

	}

}