<?php

namespace Core\Helper;

class BitwiseOperator {

	public static function has($value, int $flag): bool {
		return (((int)$value & $flag) == $flag);
	}

	public static function add(&$value, int $flag): void {
		$value |= $flag;
	}

	public static function remove(&$value, int $flag): void {
		$value &= ~$flag;
	}

}
