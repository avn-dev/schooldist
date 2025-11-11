<?php

/**
 * @TODO Redundanz mit Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry und Ext_Thebing_Customer_Search
 *
 * @deprecated
 */
class Ext_Thebing_Examination_Autocomplete extends Ext_Gui2_View_Autocomplete_Abstract {

	public function getOption($aSaveField, $sValue) {

		$oInquiry = Ext_TS_Inquiry::getInstance((int)$sValue);
		$oContact = $oInquiry->getTraveller();
        $sName = $oContact->getName();

		return $sName;
	}

	public function getOptions($sInput, $aSelectedIds, $aSaveField) {
		global $_VARS,$user_data;

		$aOptions		= array();

		$iClientID		= (int)$user_data['client'];
		$oSchool		= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolID		= $oSchool->id;

		$aWhere = array(
			'`ts_i`.`active` = 1',
			'`ts_i`.`canceled` <= 0',
			"`ts_i`.`confirmed` != '0000-00-00'"
		);

		if($oSchool->id > 0) {
			$aWhere[] = " `ts_i_j`.`school_id` = :school_id ";
		}

		$sWhere = implode(' AND ', $aWhere);

		$sSql = "
			SELECT
				`ts_i`.`id`, CONCAT(`tc_c`.`lastname`,' ',`tc_c`.`firstname`) `name`,
				`tc_cn`.`number` `customer_number`,
				`tc_c`.`birthday` `birthday`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN 
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`tc_contacts` `tc_c` ON 
					`tc_c`.`id` = `ts_i_to_c`.`contact_id` INNER JOIN
				`ts_inquiries_journeys`	`ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id`
			WHERE
				".$sWhere."
			HAVING
				/* TODO Das ist nicht gut gelöst */
				`name` LIKE :val OR
				`tc_cn`.`number` LIKE :val
			ORDER BY
				`name` ASC
		";

		$aSql = array(
			'val'			=> '%'.$sInput.'%',
			'school_id'		=> (int)$iSchoolID,
		);

		$aResult	= DB::getPreparedQueryData($sSql, $aSql);

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->getId();

		$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $iSchoolId);

		if(!empty($aResult))  {
			foreach($aResult as $aData) {
				$aOptions[$aData['id']] = $aData['customer_number'].' '.$aData['name'].' ('.L10N::t('Geburtsdatum').': '.$oFormat->format($aData['birthday']).')';
			}
		}

		return $aOptions;

	}
}