<?php

namespace TsTuition\Controller;

class OwnOverviewController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'thebing_tuition_overview';

	public function getExport(\MVC_Request $request) {

		$aConfig = array();
		$aConfig['report_id']	= (int)$request->input('report_id');
		$aConfig['week']		= (int)$request->input('week');
		$aConfig['filter_data'] = array(
			'state_booking' => $request->input('state_booking'),
			'state_course' => $request->input('state_course')
		);

		// PDF oder CSV
		$aConfig['export_type']	= $request->input('export_type');

		$oResult = new \Ext_Thebing_Tuition_Report_Result($aConfig);
		$oResult->setRequestObject($request);
		$oResult->bExport = true;

		// PDF oder CSV Export
		if($request->input('export_type') == 'pdf') {
			$oResult->getPDF();
		} elseif($request->input('export_type') == 'csv') {
			$oResult->getCSV();
		}

		return;
	}

	public function loadTable(\MVC_Request $request) {

		$aConfig = array();
		$aConfig['report_id'] = (int)$request->input('report_id');
		$aConfig['week'] = (int)$request->input('week');
		$aConfig['filter_data'] = array(
			'state_booking' => $request->input('state_booking'),
			'state_course' => $request->input('state_course'),
			'inbox_id' => $request->input('inbox_id')
		);

		$oResult = new \Ext_Thebing_Tuition_Report_Result($aConfig);
		$oResult->setRequestObject($request);

		$mData = $oResult->getTable();

		if($request->input('debug')) {

			__out(\Util::getQueryHistory());

		}

		return response()->json($mData);
	}

}
