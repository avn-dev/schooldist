<?php

namespace TsCompany\Gui2\Format\JobOpportunity;

class ValuePer extends \Ext_Thebing_Gui2_Format_Format {

	private $amountColumn;

	private $perColumn;

	public function __construct(string $amountColumn, string $perColumn) {
		$this->amountColumn = $amountColumn;
		$this->perColumn = $perColumn;
	}

	public function format($value, &$volumn = null, &$resultData = null) {

		if(
			!isset($resultData[$this->amountColumn]) ||
			!isset($resultData[$this->perColumn])
		) {
			throw new \RuntimeException(sprintf('Missing column in result data for format class "%s"!', get_called_class()));
		}

		$amount = (new \Ext_Thebing_Gui2_Format_Amount)->formatByValue($resultData[$this->amountColumn]);
		$unit = (new ValueUnit())->formatByValue($resultData[$this->perColumn]);

		return sprintf('%s/%s', $amount, $unit);
	}

}
