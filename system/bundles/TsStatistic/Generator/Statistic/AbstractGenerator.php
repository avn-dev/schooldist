<?php

namespace TsStatistic\Generator\Statistic;

use TsStatistic\Model\Filter;

abstract class AbstractGenerator extends \TcStatistic\Generator\Statistic\AbstractGenerator {

	const RENDERER_HTML = '\TsStatistic\Generator\Table\Html';

	protected $aAvailableFilters = [
		Filter\Schools::class
	];

}
