<?php

namespace TsStatistic\Controller;

use TcStatistic\Generator\Statistic\AbstractGenerator;

class StatisticController extends \TcStatistic\Controller\StatisticController {

	const BUNDLE_NAME = 'TsStatistic';

	const TRANSLATION_PATH = 'Thebing School » Reports';

	/**
	 * @inheritdoc
	 */
	protected function createSmartyObject(AbstractGenerator $oGenerator) {

		$oSmarty = parent::createSmartyObject($oGenerator);

		$oSmarty->assign('sDateFormat', (new \Ext_Thebing_Gui2_Format_Date())->format_js);

		if (($period = $oGenerator->createDateFilterPeriod()) !== null) {
			$oSmarty->assign('aDatePeriod', [\Ext_Thebing_Format::LocalDate($period->getStartDate()), \Ext_Thebing_Format::LocalDate($period->getEndDate())]);
		}

		return $oSmarty;

	}

}
