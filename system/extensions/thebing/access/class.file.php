<?php

class Ext_Thebing_Access_File extends Ext_TC_Access_File {
	
	/**
	 * @return string
	 */
	protected function getAccessFilePath() {
		return Util::getDocumentRoot().'system/extensions/thebing/access/class.client.php';
	}

}