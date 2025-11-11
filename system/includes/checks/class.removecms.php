<?php

class Checks_RemoveCMS extends Checks_FideloUpgrade {

	public function getTitle() {
		return 'Remove CMS from core structure';
	}
	
	public function getDescription() {
		return 'Removes insecure scripts and checks if it is possible to execute PHP scripts in media directory.';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {

		$this->executeQueries();
		
		$this->deleteFiles();

		// Delete tables
		$aDeleteTables = array(
			'system_sessions'
		);

		foreach($aDeleteTables as $sTable) {
			try {
				$sSql = "DROP TABLE #table";
				$aSql = [
					'table' => $sTable
				];
				DB::executePreparedQuery($sSql, $aSql);
			} catch(Exception $e) {
				__out($e->getMessage());
			}
		}

		// Rename tables
		$aRenameTables = array(
			'system_blockdata',
			'system_blocks',
			'system_content',
			'system_extensions_config',
			'system_meta',
			'system_pageproperties',
			'system_pages',
			'system_pages_rights',
			'system_sites',
			'system_sites_domains',
			'system_sites_languages',
			'system_stats',
			'system_styles',
			'system_styles_files'
		);

		foreach($aRenameTables as $sTable) {
			try {
				$sSql = "RENAME TABLE #old TO #new";
				$aSql = [
					'old' => $sTable,
					'new' => str_replace('system_', 'cms_', $sTable)
				];
				DB::executePreparedQuery($sSql, $aSql);
			} catch(Exception $e) {
				__out($e->getMessage());
			}
		}

		return true;
	}
	
	private function deleteFiles() {
		
		$aDelete = array(
			'admin/styles.html',
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
			'system/includes/parser.inc.php',
			'system/includes/class.welcome.php',
			'css.php',
			'system/applications/',
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
		];
		
		foreach($aQueries as $sQuery) {
			DB::executeQuery($sQuery);
		}
		
	}

}
