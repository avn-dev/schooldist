<?php

namespace Admin\Enums;

enum ContentType: string
{
	case IFRAME = 'iframe';
	case HTML = 'html';
	case COMPONENT = 'component';

	public function isVueComponent(): bool
	{
		return $this === self::COMPONENT;
	}
}