<?php

namespace Ts\Controller;

use \Core\Handler\SessionHandler;

class ApiController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	/**
	 * Ein weiteres Erbe
	 */
	public function handleLegacyPlacementtest() {

		$oRequest = $this->getRequest();

		/* @var \Ext_Thebing_School $school */
		$school = \Ext_Thebing_School::query()
			->where('sMd5', $oRequest->get('KEY'))
			->get()
			->first();

		// "Fake" Template und Combination erzeugen und damit den Placementtest laden (Abwärtskompatibilität)
		$template = new \Ext_TC_Frontend_Template();
		$template->use_default_template = '1';
		$template->usage = 'placementtest';

		$combination = new \Ext_TS_Frontend_Combination();
		$combination->items_language = $school->getLanguage();
		$combination->usage = 'placementtest';

		// Wichtig ist hier, das irgend etwas drin steht, der Wert ist aber egal
		$oRequest->add(['X-Originating-URI' => 'legacy_fake']);

		$smarty = new \SmartyWrapper();

		$mContent = $combination->generateContent($smarty, $template, $oRequest);
		echo $mContent;

		#require(\Util::getDocumentRoot().'system/extensions/kolumbus_placementtest.php');

		die;

	}

}
