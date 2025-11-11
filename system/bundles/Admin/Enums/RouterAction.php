<?php

namespace Admin\Enums;

enum RouterAction: string
{
	case TAB = 'tab';
	case MODAL = 'modal';
	case SLIDEOVER = 'slideOver';
	case PAGE = 'page';
	case GUI2_DIALOG = 'gui2_dialog';

	public function isTab(): bool
	{
		return $this === self::TAB;
	}
}