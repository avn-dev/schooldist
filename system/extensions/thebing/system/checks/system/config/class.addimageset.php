<?php

class Ext_Thebing_System_Checks_System_Config_AddImageSet extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Add an ImageSet';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Add an ImageSet for a GUI list';
	}

	/**
	 * @return bool
	 */
	public function executeCheck() {

		try {
			Ext_Thebing_Util::backupTable('system_imgbuilder');
			$sSQL = "INSERT INTO `system_imgbuilder` (`id`, `date`, `title`, `x_dynamic`, `y_dynamic`, `x`, `y`, `data`, `bg_colour`, `bg_transparent`, `type`, `active`) VALUES (4, '2014-05-05 09:56:59', 'GUI-Liste 60px', 1, 1, 0, 0, 'a:1:{i:0;a:17:{s:4:\"type\";s:6:\"images\";s:4:\"file\";s:0:\"\";s:1:\"x\";s:1:\"0\";s:1:\"y\";s:1:\"0\";s:4:\"from\";s:1:\"1\";s:5:\"index\";s:1:\"1\";s:4:\"text\";s:0:\"\";s:4:\"user\";s:1:\"1\";s:1:\"w\";s:2:\"60\";s:1:\"h\";s:2:\"60\";s:6:\"resize\";s:1:\"1\";s:5:\"align\";s:1:\"C\";s:9:\"grayscale\";s:1:\"0\";s:9:\"bg_colour\";s:0:\"\";s:6:\"rotate\";s:1:\"0\";s:4:\"flip\";s:0:\"\";s:8:\"position\";s:1:\"1\";}}', '', 0, 'jpg', 0)";
			DB::executeQuery($sSQL);
			return true;
		}
		catch(Exception $ex) {
			return false;
		}

	}

}