<?php

namespace TsComplaints\Entity;

use \TcComplaints\Entity\ComplaintHistory as TcComplaints_Entity_ComplaintHistory;

/**
 * Class ComplaintHistory
 * @package TsComplaints\Entity
 *
 * @property int id
 * @property string changed
 * @property string created
 * @property bool active
 * @property int creator_id
 * @property int editor_id
 * @property int complaint_id
 * @property string comment
 * @property string state
 * @property string comment_type
 * @property string comment_date
 * @property string followup
 * @property int assigned_to
 */
class ComplaintHistory extends TcComplaints_Entity_ComplaintHistory {

	/**
	 * @param array $aSqlParts
	 * @param string|null $sView
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= " ,
			`tc_c`.`firstname`,
			`tc_c`.`lastname`,
			`tc_c_n`.`number`,
			`ka`.`ext_1`,
			`ka`.`ext_2`,
			`ts_a`.`number`
		";

		$aSqlParts['from'] .= "
			INNER JOIN `tc_complaints` `tc_cs` ON
				`tc_cs`.`id` = `tc_ch`.`complaint_id` AND
				`tc_cs`.`active` = 1 LEFT JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `tc_cs`.`inquiry_id` AND
				`ts_i`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_t_c` ON
				`ts_i_t_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_t_c`.`type` = 'traveller' LEFT JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_t_c`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`tc_contacts_numbers` `tc_c_n` ON
				`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`ts_companies` `ka` ON
				`ka`.`id` = `ts_i`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_a` ON
				`ts_a`.`company_id` = `ka`.`id`
		";
	}

}
