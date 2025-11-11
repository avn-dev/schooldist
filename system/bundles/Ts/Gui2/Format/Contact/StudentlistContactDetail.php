<?php

namespace Ts\Gui2\Format\Contact;

use Illuminate\Support\Str;

class StudentlistContactDetail extends \Ext_Gui2_View_Format_Abstract
{
	public function __construct(
		private string $detail,
		private string $separator = ', ',
	) {}

	public function format($value, &$column = null, &$resultData = null)
	{
		if (!empty($value)) {
			$details = array_filter(explode('{||}', $value), fn ($detail) => Str::startsWith($detail, $this->detail.'{|}'));
			$data = array_map(fn ($detail) => Str::after($detail, $this->detail.'{|}'), $details);

			return implode($this->separator, $data);
		}

		return $value;
	}

}