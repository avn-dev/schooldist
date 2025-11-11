<?php

namespace TsActivities\Controller;
use \Core\Handler\SessionHandler as Session;
use TsActivities\Entity\Activity\Price;

class PricesController extends \MVC_Abstract_Controller {

	// TODO Auf response()->view() umstellen und entfernen
	protected $_sViewClass = '\MVC_View_Smarty';

	protected $oSession;

	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);

		$this->oSession = Session::getInstance();
		$this->set('oSession', $this->oSession);

	}

	/**
	 * @throws \Exception
	 */
	public function prices() {

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		if($this->_oRequest->get('currency') != null) {
			$sSelectedCurrency = $this->_oRequest->get('currency');
		} else {
			$sSelectedCurrency = $this->oSession->get('currency');
		}

		if($this->_oRequest->get('season') != 0) {
			$iSelectedSeason = $this->_oRequest->get('season');
		} else {
			$iSelectedSeason = $this->oSession->get('season');
		}

		$aSeasons = $oSchool->getSaisonList(true, 0, 0, 0, 0, 0, 0, 1);

		$oCurrency = new \Ext_Thebing_Currency_Util($oSchool);
		$aCurrency = $oCurrency->getCurrencyList(true);
		$this->set('aSeasons', $aSeasons);
		$this->set('aCurrencies', $aCurrency);

		if(
			!empty($iSelectedSeason) &&
			!empty($sSelectedCurrency)
		) {

			$oSeason = \Ext_Thebing_Marketing_Saison::getInstance($iSelectedSeason);
			$dSeason = new \Core\DTO\DateRange(
				new \Core\Helper\DateTime($oSeason->valid_from),
				new \Core\Helper\DateTime($oSeason->valid_until)
			);

			$oActivityRepository = \TsActivities\Entity\Activity::getRepository();
			$aActivityOptions = $oActivityRepository->getActivitiesBySchool($oSchool, $dSeason);
			$oActivityPriceRepository = Price::getRepository();
			$aPrices = [];
			foreach($aActivityOptions as $iActivityId=>$oActivity) {
				if ($oActivity->isFreeOfCharge()) {
					continue;
				}

				$aCriteria = [
					'activity_id' => $oActivity->id,
					'currency_iso' => $sSelectedCurrency,
					'school_id' => $oSchool->id,
					'saison_id' => $iSelectedSeason // Kriterium muss den Rechtschreibfehler enthalten
				];

				/** @var Price $oPrice */
				$oPrice = $oActivityPriceRepository->findOneBy($aCriteria);

				$aPrices[$oActivity->id] = \Ext_Thebing_Format::Number($oPrice->price);

			}

			$aBillingPeriodArray = \TsActivities\Gui2\Data\ActivityData::getBillingPeriodOptions();

			$this->set('aActivities', $aActivityOptions);
			$this->set('aBillingPeriods', $aBillingPeriodArray);
			$this->set('aPrice', $aPrices);

		}

		$this->set('sRoute', "activities_prices");
		$this->set('iSeasonId', $iSelectedSeason);
		$this->set('iCurrencyId', $sSelectedCurrency);

	}

	/**
	 * @throws \Exception
	 */
	public function saveActivityPrices() {

		\DB::begin(__METHOD__);

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		$sRequestCurrency = $this->_oRequest->get('currency');
		$sRequestSeason = $this->_oRequest->get('season');
		$bError = false;

		if($sRequestCurrency != null) {
			$sSelectedCurrency = $sRequestCurrency;
		} else {
			$sSelectedCurrency = null;

		} if($sRequestSeason != 0) {
			$iSelectedSeason = (int)$sRequestSeason;
		} else {
			$iSelectedSeason = null;
		}

		$oActivityPriceRepository = Price::getRepository();
		$aActivities = $this->_oRequest->input('amount');

		foreach($aActivities as $iActivityId=>$aActivity) {
			$aCriteria = [
				'activity_id' => $iActivityId,
				'currency_iso' => $sRequestCurrency,
				'school_id' => $oSchool->id,
				'saison_id' => $iSelectedSeason
			];

			$oPrice = $oActivityPriceRepository->findOneBy($aCriteria);

			if($oPrice === null) {
				$oPrice = new Price;
				$oPrice->activity_id = $iActivityId;
				$oPrice->school_id = $oSchool->id;
				$oPrice->currency_iso = $sRequestCurrency;
				$oPrice->saison_id = $iSelectedSeason;
			}
			$oPrice->price = \Ext_Thebing_Format::convertFloat($aActivities[$iActivityId][$sSelectedCurrency][$iSelectedSeason]);

			$mValid = $oPrice->validate();

			if($mValid === true) {
				$oPrice->save();
			} else {
				$bError = true;
				break;
			}
		}
		if($this->_oRequest->get('currency') != null) {
			$sSelectedCurrency = $this->_oRequest->get('currency');
		} if($this->_oRequest->get('season') != 0) {
			$iSelectedSeason = $this->_oRequest->get('season');
		}

		//in Flashbag speichern
		$this->oSession->set('currency', $sSelectedCurrency);
		$this->oSession->set('season', $iSelectedSeason);

		if($bError === true) {
			\DB::rollback(__METHOD__);
			$this->oSession->getFlashBag()->add('error', \L10N::t('Änderungen konnten nicht gespeichert werden.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH));
		} else {
			\DB::commit(__METHOD__);
			$this->oSession->getFlashBag()->add('success', \L10N::t('Änderungen wurden erfolgreich übernommen.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH));
		}

		$this->redirect('TsActivities.prices');

	}

}