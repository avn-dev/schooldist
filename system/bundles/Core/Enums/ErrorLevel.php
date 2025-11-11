<?php

namespace Core\Enums;

enum ErrorLevel: string
{
	case EMERGENCY = 'emergency';
	case ALERT = 'alert';
	case CRITICAL = 'critical';
	case ERROR = 'error';
	case WARNING = 'warning';
	case NOTICE = 'notice';
	case INFO = 'info';
	case DEBUG = 'debug';

	/**
	 * Liste aller verfügbaren Level
	 *
	 * @return ErrorLevel[]
	 */
	public static function getLevels(): array
	{
		return self::cases();
	}

	/**
	 * Priorität beim PP
	 *
	 * @return int
	 */
	public function getParallelProcessingPriority(): int
	{
		return match($this)
		{
			self::EMERGENCY => 5,
			self::ALERT => 5,
			self::CRITICAL => 5,
			self::ERROR => 10,
			self::WARNING => 10,
			default => 15
		};
	}

	/**
	 * Priorität beim E-Mail versand
	 *
	 * @return int
	 */
	public function getEmailPriority(): int
	{
		return match($this)
		{
			self::EMERGENCY => 5,
			self::ALERT => 5,
			self::CRITICAL => 5,
			self::ERROR => 10,
			self::WARNING => 10,
			default => 15
		};
	}

}