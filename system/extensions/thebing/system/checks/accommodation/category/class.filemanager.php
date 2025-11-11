<?php

class Ext_Thebing_System_Checks_Accommodation_Category_Filemanager extends GlobalChecks {

	public function getTitle() {
		return 'Prepare accommodation categories filemanager for frontend usage';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$this->renameDir('filemanager/ext_thebing_accommodation_category', 'filemanager/ts_accommodation_category');
		$this->renameDir('public/filemanager/ext_thebing_accommodation_category', 'public/filemanager/ts_accommodation_category');

		return true;

	}

	private function renameDir($oldPath, $newPath) {

		$oldPath = Util::getDocumentRoot().'storage/'.$oldPath;
		$newPath = Util::getDocumentRoot().'storage/'.$newPath;
		if (!is_dir($oldPath)) {
			return;
		}

		if (is_dir($newPath)) {
			throw new \RuntimeException('Old dir and new dir both exist! '.$oldPath.' '.$newPath);
		}

		if (!rename($oldPath, $newPath)) {
			throw new \RuntimeException('Could not move dir! '.$oldPath.' '.$newPath);
		}

	}

}