<?php

namespace TsApi\Controller;

use DateTime;
use Illuminate\Http\Request;

class PlacementtestController extends \Illuminate\Routing\Controller {

	public function update(Request $request) {

		$data = $request->json()->all();

		/** @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Ext_TS_Inquiry::query()
			->where('unique_key', $data['unique_key'])
			->get()
			->first();

		if (empty($inquiry)) {
			return response('BOOKING_NOT_FOUND', 404);
		}

		$school = $inquiry->getSchool();
		$levels = $school->getLevelList(true, null, 'internal', false, true);
		
		$levelMapping = array_flip($levels);

		$level = null;
		if(!empty($levelMapping[$data['level']])) {
			$level = \Ext_Thebing_Tuition_Level::getInstance($levelMapping[$data['level']]);
		}

		if (empty($level)) {
			return response('INVALID_LEVEL', 400);
		}

		$courseLanguage = \Ext_Thebing_Tuition_LevelGroup::query()
			->where('id', $data['language_id'])
			->get()
			->first();

		if (empty($courseLanguage)) {
			return response('COURSELANGUAGE_NOT_FOUND', 400);
		}

		$courseLanguages = $inquiry->getCourseLanguages();

		if (!array_key_exists($data['language_id'], $courseLanguages)) {
			return response('INVALID_COURSELANGUAGE', 400);
		}

		$result = \Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($inquiry->id, $courseLanguage->id);

		if (empty($result)) {
			$result = new \Ext_Thebing_Placementtests_Results();
			$result->inquiry_id = $inquiry->id;
			$result->courselanguage_id = $courseLanguage->id;
		}

		$date = DateTime::createFromFormat('Y-m-d', $data['date']);
		if ($date) {
			$result->placementtest_date = $data['date'];
		}

		$result->level_id = $level->id;
		$result->comment = strip_tags($data['comment']);
		$result->inquiry_id = $inquiry->id;
		$result->save();

		return response('SUCCESS', 200);
	}

}