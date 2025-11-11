<?php

class Ext_TS_System_Checks_Course_SetActivatePricesPerPaymentCondition extends GlobalChecks {

	const CHECK_ALREADY_EXECUTED_KEY = 'check_set_activate_prices_per_payment_condition';

	public function getTitle() {
		return 'Courses: Activate prices per payment checkbox';
	}

	public function getDescription() {
		return 'Sets the checkbox to activate the prices per payment condition setting for courses with existing values';
	}

	public function executeCheck() {

		// Wenn der Check schon ausgeführt wurde
		if(\System::d(self::CHECK_ALREADY_EXECUTED_KEY, false) == 1) {
			return true;
		}

		$table = 'wdbasic_attributes';

		$courseIds = WDBasic_Attribute::query()
			->where('entity', 'kolumbus_tuition_courses')
			->where('key', 'prices_per_payment_condition')
			->whereNotNull('value')
			->where('value', '!=', '')
			->where('value', '!=', '[]')
			->pluck('entity_id');

		if($courseIds->isNotEmpty()) {
			$backup = Util::backupTable($table);
			if(!$backup) {
				return false;
			}
			foreach ($courseIds as $courseId) {
				DB::insertData($table, [
					'entity' => 'kolumbus_tuition_courses',
					'entity_id' => $courseId,
					'key' => 'show_prices_per_payment_conditon_select',
					'value' => '1'
				]);
			}
		}

		\System::s(self::CHECK_ALREADY_EXECUTED_KEY, 1);

		return true;
	}

}
