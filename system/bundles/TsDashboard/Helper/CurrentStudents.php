<?php

namespace TsDashboard\Helper;

class CurrentStudents extends Charts
{
	const VIEW = 'current_students';

	public function getHandler(): \Admin\Components\Dashboard\Handler
	{
		return (new \Admin\Components\Dashboard\Handler(2, 6, true))->min(2, 2);
	}

	protected static function getView(): string
	{
		return self::VIEW;
	}

	public function getColor(): ?string
	{
		return \Admin\Helper\Welcome\Box::COLOR_GREEN;
	}
}