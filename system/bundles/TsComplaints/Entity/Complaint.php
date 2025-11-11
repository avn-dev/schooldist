<?php

namespace TsComplaints\Entity;

use \TcComplaints\Entity\Complaint as TcComplaints_Entity_Complaint;

/**
 * Class Complaint
 * @package TsComplaints\Entity
 *
 * @property int id
 * @property string changed
 * @property string created
 * @property string active
 * @property int creator_id
 * @property int editor_id
 * @property int inquiry_id
 * @property int category_id
 * @property int sub_category_id
 * @property int complaint_id
 * @property int latest_comment_id
 * @property string type
 * @property int type_id
 * @property string|null $complaint_date
 */
class Complaint extends TcComplaints_Entity_Complaint {

	/**
	 * @return \Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return \Ext_TS_Inquiry::getInstance($this->inquiry_id);
	}

	/**
	 * Gibt den Dialog Titel der Beschwerden zurück
	 * @param $sName
	 * @param TcComplaints_Entity_Complaint $oComplaint
	 * @return string
	 */
	public static function getDialogTitle($sName, TcComplaints_Entity_Complaint $oComplaint) {

		if($sName === 'customer_name') {
			$oInquiry = \Ext_TS_Inquiry::getInstance($oComplaint->inquiry_id);
			$oTraveller = $oInquiry->getTraveller();
			$sName = $oTraveller->getName();
		}

		return $sName;
	}

	/**
	 * @param $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);

		$sLanguage = \System::getInterfaceLanguage();

		$aSqlParts['select'] .= ",
			`tc_c`.`firstname`,
			`tc_c`.`lastname`,
			`tc_c`.`birthday`,
			`tc_c`.`gender`,
			`tc_a`.`country_iso`,
			`tc_ea`.`email`,
			`dl_mother_tongue`.`name_".$sLanguage."` `mother_tongue`,
			IF(`dl_nationality`.`nationality_".$sLanguage."` != '', `dl_nationality`.`nationality_".$sLanguage."`, `dl_nationality`.`nationality_en`) `nationality`,
			`dl_corresponding_language`.`name_".$sLanguage."` `corresponding_language`,
			`tc_c_n`.`number` `customer_number`,
			`kg`.`name` `group_name`,
			`kg`.`short` `group_short`,
			`ka`.`ext_1` `agency_full_name`,
			`ka`.`ext_2` `agency_short`,
			`ts_a`.`number` `agency_number`,
			`tc_cc`.`title` `category_title`,
			`tc_cc`.`short_name` `category_short`,
			`tc_cc`.`type` `category_area`,
			`tc_csc`.`title` `subcategory_title`,
			`tc_csc`.`short_name` `subcategory_short`,
			`tc_ch`.`state`,
			(
				".$this->getFollowUpQuery('`tc_cs`.`id`')."
			) `followup`
		";

		$aSqlParts['from'] .= "
			INNER JOIN `ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `tc_cs`.`inquiry_id` AND
				`ts_i`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_t_c` ON
				`ts_i_t_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_t_c`.`type` = 'traveller' LEFT JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_t_c`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`data_languages` `dl_mother_tongue` ON
				`dl_mother_tongue`.`iso_639_1` = `tc_c`.`language` LEFT JOIN
			`data_countries` `dl_nationality` ON
				`dl_nationality`.`cn_iso_2` = `tc_c`.`nationality` LEFT JOIN
			`data_languages` `dl_corresponding_language` ON
				`dl_corresponding_language`.`iso_639_1` = `tc_c`.`corresponding_language` LEFT JOIN
			`tc_contacts_to_emailaddresses` `tc_c_t_ea` ON
				`tc_c_t_ea`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_emailaddresses` `tc_ea` ON
				`tc_ea`.`id` = `tc_c_t_ea`.`emailaddress_id` AND
				`tc_ea`.`active` = 1 LEFT JOIN
			`tc_contacts_numbers` `tc_c_n` ON
				`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` AND
				`kg`.`active` = 1 LEFT JOIN
			`ts_companies` `ka` ON
				`ka`.`id` = `ts_i`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_a` ON
				`ts_a`.`company_id` = `ka`.`id` LEFT JOIN
			`tc_complaints_categories` `tc_cc` ON
				`tc_cc`.`id` = `tc_cs`.`category_id` AND
				`tc_cc`.`active` = 1 LEFT JOIN
			`tc_complaints_categories_subcategories` `tc_csc` ON
				`tc_csc`.`id` = `tc_cs`.`sub_category_id` AND
				`tc_csc`.`active` = 1 LEFT JOIN
			`tc_complaints_histories` `tc_ch` ON
				`tc_ch`.`id` = `tc_cs`.`latest_comment_id` AND
				`tc_ch`.`active` = 1 LEFT JOIN
			`tc_contacts_to_addresses` `tc_c_t_a` ON
				`tc_c_t_a`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_addresslabels` `tc_al` ON
				`tc_al`.`type` = 'contact_address' AND
				`tc_al`.`active` = 1 LEFT JOIN
			`tc_addresses` `tc_a` ON
				`tc_a`.`id` = `tc_c_t_a`.`address_id` AND
				`tc_a`.`label_id` = `tc_al`.`id` AND
				`tc_a`.`active` = 1 LEFT JOIN
			`tc_complaints_histories` `tc_ch_1` ON
			    `tc_ch_1`.`complaint_id` = `tc_cs`.`id` AND
			    `tc_ch_1`.`active` = 1
		";

		$aSqlParts['groupby'] = " `tc_cs`.`id` ";

	}

}
