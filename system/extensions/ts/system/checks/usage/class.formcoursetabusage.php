<?php

/**
 * Kein echter Check, sondern nur für Usage über Update-Server/Hotfix
 */
class Ext_TS_System_Checks_Usage_FormCourseTabUsage extends Ext_Thebing_System_Checks_Enquiry_Filterset {

	public function getTitle() {
		return '';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$sql = "
			SELECT
				kf.*,
				kfpb.*
			FROM
				kolumbus_forms_pages_blocks_settings kfpbs INNER JOIN
				kolumbus_forms_pages_blocks_settings kfpbs2 ON
					kfpbs2.block_id = kfpbs.block_id AND
					kfpbs2.setting = 'grouping' AND
					kfpbs2.value IN ('category', 'language') INNER JOIN
				kolumbus_forms_pages_blocks kfpb ON
					kfpb.id = kfpbs.block_id AND
					kfpb.active = 1 INNER JOIN
				kolumbus_forms_pages kfp ON
					kfp.id = kfpb.page_id AND
					kfp.active = 1 INNER JOIN
				kolumbus_forms kf ON
					kf.id = kfp.form_id AND
					kf.active = 1
			WHERE
				kfpbs.setting = 'grouping_selection' AND
				kfpbs.value = 'tab'
		";

		$rows = DB::getQueryRows($sql);

		if (empty($rows)) {
			$rows = 'No form with course tabs found.';
		}

		$client = new \GuzzleHttp\Client();
		$client->post('https://update.fidelo.com/info.php?topic='.urlencode('Frontend Form Course Tab Usage - '.\Util::getHost()), [
			'headers' => [
				'User-Agent' => 'Fidelo Update Service'
			],
			'json' => $rows
		]);

		return true;

	}

}
