<?php

namespace TsFrontend\Generator;

use Core\Handler\SessionHandler;
use Ext_TC_Frontend_Combination;
use SmartyWrapper;
use Tc\Service\Language\Frontend;

class PlacementTestGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	protected Frontend $translator;
	protected \Ext_TS_Placementtest $placementtest;

	public function __construct(Ext_TC_Frontend_Combination $oCombination, SmartyWrapper $oSmarty = null)
	{
		parent::__construct($oCombination, $oSmarty);

		$this->translator = new Frontend($this->_oCombination->items_language);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$session = SessionHandler::getInstance();
		$this->_assign('session', $session);
		$key = $this->getKey();

		// Das passiert nicht im Konstruktor, weil erst beim _default()-Aufruf das request-Objekt da ist, was man für getKey() braucht
		$this->placementtest = new \Ext_TS_Placementtest($key);

		if(!$this->checkKey()) {
			return;
		}

		if($this->placementtest->checkIfCommitted()) {
			$session->getFlashBag()->add('error', $this->translator->translate('Test has already been sent off.'));
			return;
		}

		$categories = $this->placementtest->placementtestEntity->getCategories();

		//if no data is found in test
		if(empty($categories)) {
			$session->getFlashBag()->add('error', $this->translator->translate('Sorry, there are no inserts in this placementtest.'));
			return;
		}

		$this->placementtest->saveStartDate();

		$results = $this->_oRequest->input('save');

		// Beim submitten
		if (!empty($results)) {
			$success = $this->placementtest->insertResults($results);

			if($success) {
				//if test was inserted correctly display a send message
				$session->getFlashBag()->add('success', $this->translator->translate('Your test has been committed.'));
				return;
			}

			$session->getFlashBag()->add('error', $this->translator->translate('Something went wrong while committing the test. Please try again.'));

			$missingRequired = $this->placementtest->getMissingRequired();

			if (count($missingRequired) > 0) {
				$session->getFlashBag()->add('error', $this->translator->translate('Required fields are missing.'));
			}

			$this->_assign('results', $results);
			$this->_assign('missingRequired', $missingRequired);
		}

		$this->_assign('categories', $categories);
		$this->_assign('key', $key);

		// Bei success / keinen "wichtigen" Fehlern
		$this->_assign('displayForm', true);
	}

	public function getKey() {
		$key = $this->_oRequest->get('r');

		return \Util::convertHtmlEntities($key);
	}

	public function checkKey() {

		$session = SessionHandler::getInstance();

		if(empty($this->getKey())) {
			$session->getFlashBag()->add('error', $this->translator->translate('Placement test key missing!'));
			return false;
		}

		$wrongKey = $this->placementtest->checkKey();
		if($wrongKey) {
			$session->getFlashBag()->add('error', $this->translator->translate('Wrong placement test key!'));
			return false;
		}

		return true;
	}

}