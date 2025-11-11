<?php

namespace TsTuition\Controller;

/**
 * @TODO Wird nicht verwendet
 */
class InterfaceController extends \MVC_Abstract_Controller {
	
	/**
	 * 
	 */
	public function ViewAction() {

		$oSmarty = new \SmartyWrapper();

		$sFrom = $this->_oRequest->get('from');
		$sUntil = (int)$this->_oRequest->get('until');

		$dFrom = new \Core\Helper\DateTime($sFrom);
		$dUntil = new \Core\Helper\DateTime($sUntil);
		
		$sFrom = \Ext_Thebing_Format::LocalDate($dFrom);
		$sUntil = \Ext_Thebing_Format::LocalDate($dUntil);
		
		$oSmarty->assign('dFrom', $dFrom);
		$oSmarty->assign('dUntil', $dUntil);
		$oSmarty->assign('sFrom', $sFrom);
		$oSmarty->assign('sUntil', $sUntil);

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		// TODO Auf Ext_Thebing_School_Tuition_Allocation_Result umstellen
		$sSql = "
			SELECT
				* 
			FROM 
				`kolumbus_tuition_classes` ktc JOIN
				`kolumbus_tuition_blocks` ktb ON
					ktc.id = ktb.class_id AND
					ktb.active = 1 JOIN
				`kolumbus_tuition_blocks_days` ktbd ON
					ktb.id = ktbd.block_id LEFT JOIN
				`kolumbus_tuition_blocks_inquiries_courses` ktbic ON
					ktb.id = ktbic.block_id LEFT JOIN
				ts_inquiries_journeys_courses ts_ijc ON
					ktbic.inquiry_course_id = ts_ijc.id LEFT JOIN
				ts_inquiries_journeys ts_ij ON
					ts_ijc.journey_id = ts_ij.id LEFT JOIN
				ts_inquiries ts_i ON
					ts_ij.inquiry_id = ts_i.id LEFT JOIN
				ts_inquiries_to_contacts ts_itc ON
					ts_i.id = ts_itc.inquiry_id AND
					ts_itc.type = 'traveller' LEFT JOIN
				tc_contacts tc_c ON
					ts_itc.contact_id = tc_c.id
			WHERE
				ktc.`active` = 1 AND
				ktc.`school_id` = :school_id AND
				ktb.week BETWEEN :from AND :until
			";
		$aSql = [
			'school_id' => (int)$oSchool->id,
			'from' => $dFrom->format('Y-m-d'),
			'until' => $dUntil->format('Y-m-d')
		];
		$aItems = \DB::getQueryRows($sSql, $aSql);

		$aClasses = [];
		foreach($aItems as $aItem) {
			if(empty($aClasses[$aItem['class_id']])) {
				$aClasses[$aItem['class_id']] = [
					'class' => $aItem['name'],
					'month' => [],
					'students' => []
				];
			}
			
			$oDate = new \Core\Helper\DateTime($aItem['week']);
			$oDate->add(new \DateInterval('P'.($aItem['day']-1).'D'));

			$aClasses[$aItem['class_id']]['month'][$oDate->format('Y-m')]['label'] = strftime('%B %Y', $oDate->getTimestamp());
			$aClasses[$aItem['class_id']]['month'][$oDate->format('Y-m')]['dates'][$oDate->format('Y-m-d')] = $oDate;
			
			if($aItem['contact_id'] > 0) {
				$aClasses[$aItem['class_id']]['students'][$aItem['contact_id']]['name'] = $aItem['lastname'].', '.$aItem['firstname'];
				$aClasses[$aItem['class_id']]['students'][$aItem['contact_id']]['dates'][$oDate->format('Y-m-d')] = $oDate;
			}
		}

		$oSmarty->assign('aClasses', $aClasses);

		$sTemplatePath = \Util::getDocumentRoot().'system/bundles/TsTuition/Resources/views/report.tpl';
		$sContent = $oSmarty->fetch($sTemplatePath);

		echo $sContent;

		die();
	}
	
}