<?php

namespace TsDashboard\Helper;

class ConvertedEnquiries extends Charts
{
	const VIEW = 'converted_enquiries';

	public function getHandler(): \Admin\Components\Dashboard\Handler
	{
		return (new \Admin\Components\Dashboard\Handler(2, 6, true))->min(2, 2);
	}

	public static function getView(): string
	{
		return self::VIEW;
	}

	public function getColor(): ?string
	{
		return \Admin\Helper\Welcome\Box::COLOR_BLUE;
	}

}