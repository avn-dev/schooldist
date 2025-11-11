<?php

class Ext_TS_Vat extends Ext_TC_Vat {

	const REFERENCE_DATE_CURRENT = 'current';
	const REFERENCE_DATE_SERVICE_START = 'service_start';
	const REFERENCE_DATE_SERVICE_END = 'service_end';
	
	protected $_aJoinedObjects = array(
		'combinations' => array(
			'class' => 'Ext_TS_Vat_Combination',
			'key' => 'vat_rate_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade',
			'static_key_fields' => [
				'commission_vat'=>null
			]
        )
	);
		
	public function getCombinations() {		
		$aCombinations = $this->getJoinedObjectChilds('combinations', true);	
		return $aCombinations;		
	}

	/**
	 * liefert die zugewiesene Steuer
	 * @param string $sClass
	 * @param int $iObjectId
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @return int
	 */
	public static function getDefaultCombination($sClass, $iObjectId, $oSchool, Ext_TS_Inquiry_Abstract $oInquiry=null, \DateTime $dDate=null, string $sType=null) {

		if($dDate === null) {
			$dDate = new \DateTime;
		}
				
		$aSql = array(
			'country_iso' => $oSchool->country_id,
			'class' => $sClass,
			'class_id' => (int) $iObjectId,
			'date' => $dDate->format('Y-m-d')
		);
		
		// Agenturbuchung und Provisionsgutschrift
		if(
			$oInquiry instanceof Ext_TS_Inquiry_Abstract &&
			$oInquiry->hasAgency() &&
			$sType !== null &&
			$sType === 'creditnote'
		) {

			$oAgency = $oInquiry->getAgency();
			
			if($oSchool->country_id == $oAgency->ext_6) {
				$aSql['commission_vat'] = 'domestic';
			} else {
				$aSql['commission_vat'] = 'abroad';
			}
			
			$sWhereAddon = " AND `tc_vc`.`commission_vat` = :commission_vat ";
			
		} else {
			$sWhereAddon = " AND `tc_vc`.`commission_vat` IS NULL ";
		}
		
		$sSql = "
			SELECT
				`tc_vc`.`vat_rate_id`
			FROM
				`ts_vat_rates_combinations` `tc_vc` INNER JOIN
				`ts_vat_rates_combinations_to_objects` `tc_vc_o` ON
					`tc_vc_o`.`combination_id` = `tc_vc`.`id` AND
					`tc_vc_o`.`class` = :class AND
					`tc_vc_o`.`class_id` = :class_id INNER JOIN
				`tc_vat_rates_values` `tc_vrv` ON
					`tc_vc`.`vat_rate_id` = `tc_vrv`.`rate_id` AND
					`tc_vrv`.`active` = 1 AND
					(
						:date BETWEEN `tc_vrv`.`valid_from` AND `tc_vrv`.`valid_until` OR
						(
							`tc_vrv`.`valid_until` = '0000-00-00' AND
							:date >= `tc_vrv`.`valid_from`
						) OR
						(
							`tc_vrv`.`valid_from` = '0000-00-00' AND
							`tc_vrv`.`valid_until` = '0000-00-00'
						)
					)
			WHERE
				`tc_vc`.`country_iso` = :country_iso AND
				`tc_vc`.`active` = 1
				".$sWhereAddon."
			ORDER BY
				`tc_vrv`.`valid_from` DESC
			LIMIT 1
		";
		
		$iResult = DB::getQueryOne($sSql, $aSql);

		$iDefault = 0;
		if(!empty($iResult)) {
			$iDefault = $iResult;
		}

		// Hook ausführen
        if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {

            $aHookData = array(
				'service_class' => $sClass,
				'service_id' => $iObjectId,
				'inquiry' => $oInquiry,
				'tax_category' => &$iDefault,
				'type' => $sType
			);

			\System::wd()->executeHook('ts_inquiry_document_build_items_tax', $aHookData);
        }

		return $iDefault;
	}
	
	static public function getVATReferenceDateByService(Ext_Thebing_School $oSchool, $oService) {
		
		$sReferenceDate = System::d('ts_vat_reference_date_'.$oSchool->country_id, Ext_TS_VAT::REFERENCE_DATE_CURRENT);
		
		$sDate = date('Y-m-d');
		if($sReferenceDate === Ext_TS_VAT::REFERENCE_DATE_SERVICE_START) {
			$sDate = $oService->from;
		} elseif($sReferenceDate === Ext_TS_VAT::REFERENCE_DATE_SERVICE_END) {
			$sDate = $oService->until;
		}
		
		return new \Carbon\Carbon($sDate);
	}
	
	static public function getVATReferenceDateByDate(Ext_Thebing_School $oSchool, \Carbon\Carbon $dStart, \Carbon\Carbon $dEnd) {
		
		$sReferenceDate = System::d('ts_vat_reference_date_'.$oSchool->country_id, Ext_TS_VAT::REFERENCE_DATE_CURRENT);
		
		if($sReferenceDate === Ext_TS_VAT::REFERENCE_DATE_SERVICE_START) {
			return $dStart;
		} elseif($sReferenceDate === Ext_TS_VAT::REFERENCE_DATE_SERVICE_END) {
			return $dEnd;
		}
		
		return new \Carbon\Carbon();
	}
	
}
