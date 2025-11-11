<?php

namespace TsActivities\Service;

use DateTime;
use TsActivities\Entity\Activity;

class Amount {

	/**
	 * @var array
	 */
	public $aErrors = [];

	/**
	 * @param $oJourneyActivity
	 * @return \Ts\Model\Price
	 */
	public function calculate(\Ext_TS_Inquiry_Journey_Activity $oJourneyActivity, \Ext_TS_Inquiry $oInquiry = null) {

		if ($oInquiry === null) {
			$oInquiry = $oJourneyActivity->getInquiry();
			$oJourney = $oJourneyActivity->getJourney();
		} else {
			$oJourney = $oInquiry->getJourney();
		}

		$iCurrencyId = $oInquiry->getCurrency();
		$oCurrency = \Ext_Thebing_Currency::getInstance($iCurrencyId);
		$oSchool = $oJourney->getSchool();

		$aSeasons = \Ext_Thebing_Saison_Search::bySchoolAndTimestamp(
			$oSchool->id,
			$oJourneyActivity->from,
			0,
			null,
			null,
			false,
			false,
			false,
			false,
			false,
			false,
			true
		);

		if($aSeasons === false) {
			$this->aErrors['activity_season_not_found'][] = (int)$oJourneyActivity->id;
			return null;
		}

		$oSeason = \Ext_Thebing_Marketing_Saison::getInstance(reset($aSeasons)['id']);

		$aSql = [
			'activity_id' => $oJourneyActivity->activity_id,
			'saison_id' => $oSeason->id,
			'currency_iso' => $oCurrency->iso4217,
//			'journey_activity_id' => $oJourneyActivity->id
		];

		$fAmount = $this->calculateBillingPrice($oJourneyActivity, $aSql);

		$oAmount = new \Ts\Model\Price;
		$oAmount->setPrice($fAmount);
		$oAmount->setCurrency($oCurrency);
		$oAmount->setSeason($oSeason);

		return $oAmount;
	}

    public function calculateForInquiry(\Ext_TS_Inquiry $oInquiry, Activity $oActivity, DateTime $dStart, int $iBlocks) {

		$oJourneyActivity = new \Ext_TS_Inquiry_Journey_Activity();
        $oJourneyActivity->activity_id = $oActivity->getId();
        $oJourneyActivity->from = $dStart->format('Y-m-d');
        $oJourneyActivity->weeks = 1;
        $oJourneyActivity->blocks = $iBlocks;

//		$oInquiry->getJourney()->setJoinedObjectChild('activities', $oJourneyActivity);

		return $this->calculate($oJourneyActivity, $oInquiry);
    }

	/**
	 * @param array $aSql
	 * @return float
	 */
	private function getAmount($aSql) {

		$sSql = "
			SELECT 
				`price`
			FROM 
				`ts_activities_prices`
			WHERE 
				`activity_id` = :activity_id AND
			    `saison_id` = :saison_id AND 
			    `currency_iso` = :currency_iso
		";

		$fAmount = (float)\DB::getQueryOne($sSql, $aSql);
		return $fAmount;

	}

	/**
	 * @param \Ext_TS_Inquiry_Journey_Activity $oJourneyActivity
	 * @param array $aSql
	 * @return float
	 */
	private function calculateBillingPrice(\Ext_TS_Inquiry_Journey_Activity $oJourneyActivity, $aSql) {

		$oActivity = $oJourneyActivity->getActivity();
		if ($oActivity->isFreeOfCharge()) {
			// Aktivität wird bei den Preiseneinstellungen nicht mehr angezeigt, aber es könnte trotzdem ein Preis gespeichert worden sein
			return 0;
		}

		$fAmount = $this->getAmount($aSql);

		if($oActivity->billing_period == "payment_per_week") {
			$fAmount = $fAmount * $oJourneyActivity->weeks;
		} else {
			$fAmount = $fAmount * $oJourneyActivity->blocks;
		}

		return $fAmount;
	}

}
