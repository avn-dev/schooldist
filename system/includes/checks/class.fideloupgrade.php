<?php

class Checks_FideloUpgrade extends GlobalChecks {

	public function getTitle() {
		return 'Execute upgrade tasks';
	}
	
	public function getDescription() {
		return '...';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {
		
		$this->executeQueries();
		
		$this->deleteFiles();

		try {
			$aAdminBundle = [
				'title' => 'Admin',
				'element' => 'modul',
				'category' => 'Standard',
				'file' => 'admin',
				'active' => 1
			];
			DB::insertData('system_elements', $aAdminBundle);
		} catch (Exception $e) {
			// Element ist schon da, Upgrade wurde schon durchgeführt.
			return true;
		}

		$aMoveDirectories = [
			'admin/' => 'system/legacy/admin/',
			'media/secure/' => 'storage/',
			'media/' => 'storage/public/'
		];

		foreach($aMoveDirectories as $sSource=>$sTarget) {
			$this->moveDirectoryWrapper($sSource, $sTarget);
			Util::recursiveDelete(Util::getDocumentRoot().$sSource);
		}

		$aMoveFiles = [
			'system/includes/config.inc.php' => 'config/config.php'
		];

		foreach($aMoveFiles as $sSource=>$sTarget) {
			rename(Util::getDocumentRoot().$sSource, Util::getDocumentRoot().$sTarget);
		}

		symlink(Util::getDocumentRoot().'storage/public', Util::getDocumentRoot().'public/media');

		return true;
	}
	
	private function deleteFiles() {
		
		$aDelete = array(
			'admin/styles.html',
			'admin/cache.html',
			'admin/sitemap.html',
			'admin/block.html',
			'admin/templates.html',
			'admin/sites.html',
			'admin/links.html',
			'admin/filter.html',
			'admin/preferences.html',
			'admin/index.html',
			'admin/index_neu.html',
			'admin/system.html',
			'system/includes/classes.inc.php',
			'system/includes/main.inc.php',
			'system/includes/header.inc.php',
			'system/includes/footer.inc.php',
			'system/includes/class.welcome.php',
			'css.php',
			'system/applications/',
			'system/config/',
			'system/lib/',
			'system/includes/dbconnect.inc.php',
			'system/includes/class.db.inc.php'
		);
		
		Util::$iDeletedFiles = 0;
		
		foreach($aDelete as $sDelete) {
			Util::recursiveDelete(Util::getDocumentRoot().$sDelete);
		}

		$this->logInfo('deleteFiles', array('deleted_files'=>Util::$iDeletedFiles));

	}

	private function executeQueries() {

		$aQueries = [
			"ALTER TABLE `core_parallel_processing_stack` ADD `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `id`", // Analog zu Error-Stack
			"ALTER TABLE `core_parallel_processing_stack` ADD `user_id` INT NULL DEFAULT NULL AFTER `data`, ADD INDEX `user_id` (`user_id`)",
			"ALTER TABLE `core_parallel_processing_stack_error` ADD `user_id` INT NULL DEFAULT NULL AFTER `data`",
			"ALTER TABLE `system_user` ADD `secret` VARCHAR(255) NOT NULL AFTER `password`",
			"ALTER TABLE `system_user` ADD `authentication` ENUM('simple','googletwofactor') NOT NULL DEFAULT 'simple' AFTER `secret`",
			"ALTER TABLE `system_user` ADD UNIQUE(`email`)",
			"CREATE TABLE `system_user_password_resets` ( `user_id` int(11) NOT NULL, `token` varchar(32) NOT NULL, `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"ALTER TABLE `system_user_password_resets` ADD PRIMARY KEY (`user_id`)",
			"ALTER TABLE `system_user_password_resets` ADD UNIQUE(`token`)"
		];

		foreach($aQueries as $sQuery) {
			try {
				DB::executeQuery($sQuery);
			} catch (DB_QueryFailedException $e) {
				
			}
		}

	}

	function moveDirectoryWrapper($sourceDir, $targetDir, $exclusions = array()){
		
		$sourceDir = Util::getDocumentRoot().$sourceDir;
		$targetDir = Util::getDocumentRoot().$targetDir;
		
		try{
			$this->moveDirectory($sourceDir, $targetDir, $exclusions);
		}catch(Exception $e){
			switch ($e->getCode()) {
				case 1:
					__out("ERROR: Source Directory [$sourceDir] doesn't exist or isn't a directory, we can't copy it to [$targetDir] if it isn't there.");
					break;
				case 2:
					__out("ERROR: Destination Directory [$targetDir] doesn't exist and we can't create it.");
					break;
				case 3:
					__out("ERROR: " . $e->getMessage());
					break;
				default:
					__out("ERROR: Something went sideways with copying the [$sourceDir] directory to [$targetDir].");
					__out($e->getMessage());
					__out($e->getTraceAsString());
			}

		}

	}

	function moveDirectory($src, $dest, $exclusions = array()) {

		// If source is not a directory stop processing
		if(!is_dir($src)) {
			throw new InvalidArgumentException('The source passed in does not appear to be a valid directory: ['.$src.']', 1);
		}
		// If the destination directory does not exist create it
		if(!is_dir($dest)) {
			if(!Util::checkDir($dest)) {
				throw new InvalidArgumentException('The destination does not exist, and I can not create it: ['.$dest.']', 2);
			}
		}

		// Ensure enclusions parameter is an array.
		if (! is_array($exclusions)) {
			throw new InvalidArgumentException('The exclustion parameter is not an array, it MUST be an array.', 3);
		}

		$emptiedDirs = array();
		// Open the source directory to read in files
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
			// Check to see if we should ignore this file or directory
			foreach ($exclusions as $pattern){
				if (preg_match($pattern, $f->getRealPath())){
					if ($f->isFile()){
						if (! unlink($f->getRealPath())) {
							__out("Failed to delete file [{$f->getRealPath()}] ");
						}
					}elseif($f->isDir()){
						// we will attempt deleting these after we have moved all the files.
						array_push($emptiedDirs, $f->getRealPath());
					}
					// Because we have to jump up two foreach levels
					continue 2;
				}
			}
			// We need to get a path relative to where we are copying from
			$relativePath = str_replace($src, '', $f->getRealPath());
			// And we can create a destination now.
			$destination = $dest . $relativePath;
			// if it is a file, lets just move that sucker over
			if($f->isFile()) {
				$path_parts = pathinfo($destination);
				// If we don't have a directory for this yet
				if (! is_dir($path_parts['dirname'])){
					// Lets create one!
					if (!Util::checkDir($path_parts['dirname'])) {
						__out("Failed to create the destination directory: [{$path_parts['dirname']}]");
					}
				}

				// Nicht überschreiben
				if(!is_file($destination)) {
					if(!rename($f->getRealPath(), $destination)) {
						__out("Failed to rename file [{$f->getRealPath()}] to [$destination]");
					}
				}

				Util::changeFileMode($destination);

			// if it is a directory, lets handle it
			}elseif($f->isDir()){
				// Check to see if the destination directory already exists
				if (! is_dir($destination)){
					if (!Util::checkDir($destination)) {
						__out("Failed to create the destination directory: [$destination]");
					}
				}
				// we will attempt deleting these after we have moved all the files.
				array_push($emptiedDirs, $f->getRealPath());
			// if it is something else, throw a fit. Symlinks can potentially end up here. I haven't tested them yet, but I think isFile() will typically
			// just pick them up and work
			}else{
				__out("I found [{$f->getRealPath()}] yet it appears to be neither a directory nor a file. [{$f->isDot()}] I don't know what to do with that!");
			}
		}
		foreach ($emptiedDirs as $emptyDir){
			if (realpath($emptyDir) == realpath($src)){
				continue;
			}
			if (!is_readable($emptyDir)) {
				__out("The source directory: [$emptyDir] is not Readable");
			}
			// Delete the old directory
			if (! rmdir($emptyDir)){
				// The directory is empty, we should have successfully deleted it.
				if ((count(scandir($emptyDir)) == 2)){
					__out("Failed to delete the source directory: [$emptyDir]");
				}
			}
		}
		// Finally, delete the base of the source directory we just recursed through
		if (! rmdir($src)) {
			__out("Failed to delete the base source directory: [$src]");
		}

		return true;
	}

}
