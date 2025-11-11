<?php

namespace Core\Enums;

enum AlertLevel: string
{
	case INFO = 'info';
	case WARNING = 'warning';
	case SUCCESS = 'success';
	case DANGER = 'danger';

	public function getIcon(): string {
		return match ($this) {
			self::INFO => 'fas fa-info-circle',
			self::WARNING => 'fas fa-exclamation-triangle',
			self::SUCCESS => 'far fa-check-circle',
			self::DANGER => 'far fa-times-circle',
		};
	}

}
