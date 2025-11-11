<?php

namespace TsCompany\Gui2\Format\JobOpportunity;

use Core\Helper\BitwiseOperator;
use TsCompany\Entity\JobOpportunity\StudentAllocation;

class StudentAllocationStatus extends \Ext_Thebing_Gui2_Format_Format {

	private $html = false;

	public function __construct(bool $html = false) {
		$this->html = $html;
	}

	public function format($value, &$volumn = null, &$resultData = null) {

		$status = [];

		if (BitwiseOperator::has($value, StudentAllocation::STATUS_ALLOCATED)) {
			$status[] = ['color' => 'bg-green', 'label' => \L10N::t('Zugewiesen')];
		} else if (BitwiseOperator::has($value, StudentAllocation::STATUS_CONFIRMED)) {
			$status[] = ['color' => 'bg-yellow', 'label' => \L10N::t('Bestätigt')];
		} else if (BitwiseOperator::has($value, StudentAllocation::STATUS_REQUESTED)) {
			$status[] = ['color' => 'badge-default', 'label' => \L10N::t('Angefragt')];
		}

		if (!$this->html) {
			return implode(', ', array_column($status, 'label'));
		}

		return array_map(function ($status) {
			return sprintf('<span class="badge %s">%s</span>', $status['color'], $status['label']);
		}, $status);

	}

}
