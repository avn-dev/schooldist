<?php

namespace TsCompany\Gui2\Data;

class JourneyEmployment extends \Ext_Thebing_Inquiry_Gui2 {

	protected $_bIgnoreTableQueryDataInclude = true;

	public static function getInquiryDialog(\Ext_Thebing_Gui2 $gui2): \Ext_Gui2_Dialog {

		return \Ext_TS_Inquiry_Index_Gui2_Data::getDialog('employment_student_allocations', $gui2);

	}

	public static function getCoursesFilterOptions(\Ext_Thebing_Gui2 $gui2): array {

		return \Ext_Thebing_Tuition_Course::query()->where('per_unit', \Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT)->get()
			->mapWithKeys(function (\Ext_Thebing_Tuition_Course $course) {
				return [$course->getId() => $course->getName()];
			})
			->toArray()
		;

	}

	public static function getAllocationFilterOptions(\Ext_Thebing_Gui2 $gui2) {

		return [
			'allocated' => $gui2->t('Zugewiesen'),
			'not_allocated' => $gui2->t('Nicht zugewiesen'),
			'requested' => $gui2->t('Angefragt'),
			'not_requested' => $gui2->t('Nicht angefragt'),
		];

	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		// die SelectedIds decoden damit der SR funktioniert
		$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, 'inquiry_id');

		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

	}

	public static function getWhere(\Ext_Gui2 $gui2) {
		return [];
	}

	public static function getOrderby() {
		return ['ts_ijc.from' => 'ASC'];
	}

	/**
	 * @inheritdoc
	 */
	public function prepareColumnListByRef(&$aColumnList) {

		parent::prepareColumnListByRef($aColumnList);

		if(\System::d('debugmode') == 2) {

			$oColumn = new \Ext_Gui2_Head();
			$oColumn->db_alias = 'ts_tcps';
			$oColumn->db_column = 'id';
			$oColumn->select_column = 'program_service_id';
			$oColumn->title = 'PS';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			array_unshift($aColumnList, $oColumn);

			$oColumn = new \Ext_Gui2_Head();
			$oColumn->db_alias = 'ts_ijc';
			$oColumn->db_column = 'id';
			$oColumn->select_column = 'inquiry_journey_course_id';
			$oColumn->title = 'IJC';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			array_unshift($aColumnList, $oColumn);

			$oColumn = new \Ext_Gui2_Head();
			$oColumn->db_alias = 'ts_i';
			$oColumn->db_column = 'id';
			$oColumn->select_column = 'inquiry_id';
			$oColumn->title = 'I';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			array_unshift($aColumnList, $oColumn);

		}

	}

}
