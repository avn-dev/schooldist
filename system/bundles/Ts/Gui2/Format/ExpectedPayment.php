<?php

namespace Ts\Gui2\Format;

class ExpectedPayment extends \Ext_Gui2_View_Format_Abstract
{
	public function __construct(private string $display) {}

	public function format($value, &$column = null, &$resultData = null)
	{
		if (!$value instanceof \Ts\Dto\ExpectedPayment) {
			return '';
		}

		return match ($this->display) {
			'document_number' => $value->document->document_number,
			'date' => $this->formatDate($value),
			'amount' => $value->openAmount->toString(),
			default => sprintf('%s: %s (%s)', $value->document->document_number, $value->openAmount->toString(), $this->formatDate($value))
		};
	}

	private function formatDate(\Ts\Dto\ExpectedPayment $payment): string
	{
		$date = new \Ext_Thebing_Gui2_Format_Date(iSchoolForFormat: $payment->document->getSchoolId());
		return $date->formatByValue($payment->date);
	}

}