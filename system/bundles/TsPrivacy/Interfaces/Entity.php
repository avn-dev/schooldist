<?php

namespace TsPrivacy\Interfaces;

interface Entity extends Purge {

	/**
	 * Label für diese Entität (verwendet in der Benachrichtigung)
	 *
	 * @return string
	 */
	public static function getPurgeLabel();

	/**
	 * Settings für diese Entität
	 *
	 * @return array
	 */
	public static function getPurgeSettings();

}
