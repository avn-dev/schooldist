<?php

namespace Communication\Enums;

enum MessageOutput: string
{
	case EDIT = 'edit';
	case PREVIEW = 'preview';
	case FINAL = 'final';

	public function isEdit(): bool
	{
		return $this === self::EDIT;
	}

	public function isPreview(): bool
	{
		return $this === self::PREVIEW;
	}

	public function isFinal(): bool
	{
		return $this === self::FINAL;
	}
}