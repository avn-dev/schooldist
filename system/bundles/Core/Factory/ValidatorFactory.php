<?php

namespace Core\Factory;

use Illuminate\Validation;
use Illuminate\Translation;
use Illuminate\Filesystem\Filesystem;

/**
 * https://medium.com/@jeffochoa/using-the-illuminate-validation-validator-class-outside-laravel-6b2b0c07d3a4
 * https://github.com/Laravel-Lang/lang/tree/main/locales
 */
class ValidatorFactory {

	private Validation\Factory $oFactory;

	public function __construct(string $sLang = 'en') {
		$this->oFactory = new Validation\Factory($this->loadTranslator($sLang));
	}

	/**
	 * @return Translation\Translator
	 */
	protected function loadTranslator(string $sLang) {

		$sPath = dirname(dirname(__FILE__)) . '/Resources/lang';

		$oFilesystem = new Filesystem();
		$oLoader = new Translation\FileLoader($oFilesystem, $sPath);
		$oLoader->addNamespace('lang', $sPath);
		$oLoader->load($sLang, 'validation', 'lang');

		$oTranslator = new Translation\Translator($oLoader, $sLang);
		$oTranslator->setFallback('en');

		return $oTranslator;

	}

	/**
	 * @param array $data
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return Validation\Validator
	 */
	public function make(array $data, array $rules, array $messages = [], array $customAttributes = []) {
		$oValidator = $this->oFactory->make($data, $rules, $messages, $customAttributes);
		$this->addExtensions($oValidator);
		return $oValidator;
	}

	/**
	 * @param Validation\Validator $oValidator
	 */
	private function addExtensions(Validation\Validator $oValidator) {

		$oValidator->addExtension('strlen', function($sAttribute, $mValue, $aParameters, $oValidator) {
			return strlen($mValue) <= $aParameters[0]; // Explizit NICHT mb_strlen!
		});

		$oValidator->addExtension('phone_itu', function($sAttribute, $mValue, $aParameters, $oValidator) {
			$oValidate = new \WDValidate();
			$oValidate->check = 'PHONE_ITU';
			$oValidate->value = $mValue;
			return $oValidate->execute();
		});

		$oValidator->addExtension('email_mx', function($sAttribute, $mValue, $aParameters, $oValidator) {
			$oValidate = new \WDValidate();
			$oValidate->check = 'MAIL';
			$oValidate->value = $mValue;
			return $oValidate->execute();
		});

		$oValidator->addExtension('boolean_true', function($sAttribute, $mValue, $aParameters, $oValidator) {
			return $mValue === true;
		});

	}

//	public function __call($sMethod, $aArgs) {
//		return call_user_func_array([$this->oFactory, $sMethod], $aArgs);
//	}

}
