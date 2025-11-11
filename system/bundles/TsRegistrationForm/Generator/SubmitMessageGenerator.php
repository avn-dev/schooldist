<?php

namespace TsRegistrationForm\Generator;

use TsRegistrationForm\Proxy\SubmitProxy;
use TsRegistrationForm\Service\InquiryBuilder;

class SubmitMessageGenerator {

	private CombinationGenerator $combination;

	private InquiryBuilder $builder;

	private SubmitProxy $proxy;

	public function __construct(CombinationGenerator $combination, InquiryBuilder $builder) {

		$this->combination = $combination;
		$this->builder = $builder;

	}

	public function generate(): string {

		$this->proxy = new SubmitProxy($this->builder->getInquiry());
		$this->proxy->setLanguage($this->combination->getLanguage());
		$this->proxy->setItems($this->builder->getItems());

		$message = $this->combination->getForm()->getTranslation('success', $this->combination->getLanguage());

		// Text für Überweisungen
		if (
			$this->builder->getPayment() &&
			$this->builder->getPayment()->hasValidInstructions()
		) {
			$message .= '<br><br>'.$this->builder->getPayment()->instructions;
		}

		$this->replacePlaceholders($message);

		$this->appendTemplate($message);

		return $message;

	}

	private function replacePlaceholders(string &$message) {

//		// TODO Entfernen – Irgendeine weitere Logik sollte nur im Smarty-Template stattfinden
//		if (strpos($message, '{amount}') !== false) {
//
//			$amount = $this->proxy->getTotalAmount();
//			$currency = \Ext_Thebing_Currency::getInstance($this->builder->getInquiry()->getCurrency());
//			$currency->bThinspaceSign = true;
//			$amount = \Ext_Thebing_Format::Number($amount, $currency, $this->combination->getSchool(), true, 2);
//			$message = str_replace('{amount}', $amount, $message);
//
//		}

	}

	private function appendTemplate(string &$message) {

		if (empty($this->combination->getCombination()->items_template_submit_success)) {
			return;
		}

		$template = \Ext_TC_Frontend_Template::getInstance($this->combination->getCombination()->items_template_submit_success);
		if (!$template->exist()) {
			return;
		}

		try {

			$smarty = new \SmartyWrapper();
			$smarty->assign('data', $this->proxy);
			$message .= $smarty->fetch('string:'.$template->code);

		} catch (\Throwable $e) {

			// Smarty haut bereits durchgelaufenen Content im Fehlerfall einfach raus
			ob_end_clean();

			$message .= "<script>console.error('Error while executing submit action.');</script>";
			$this->combination->log('Error while executing submit template', [$e->getMessage(), $e->getTraceAsString()]);

		}

	}

}