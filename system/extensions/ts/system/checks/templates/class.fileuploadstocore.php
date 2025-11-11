<?php

class Ext_TS_System_Checks_Templates_FileUploadsToCore extends GlobalChecks
{
	public function getTitle()
	{
		return 'File Uploads';
	}

	public function getDescription()
	{
		return 'Prepare structure of file uploads';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		// --- CONFIG ---
		$srcUploadTable      = 'kolumbus_upload';
		$dstUploadTable      = 'tc_upload';
		$srcUploadLangTable  = 'kolumbus_upload_languages';
		$dstUploadLangTable  = 'tc_upload_languages';
		$srcLinkTable        = 'kolumbus_upload_to_schools';
		$dstObjectLinkTable  = 'tc_upload_objects';
		$srcFilesDir         = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/storage/ts/uploads';
		$dstFilesDir         = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/storage/tc/uploads';
		$batchSize           = 500;

		// nur simulieren, nichts schreiben/kopieren
		$dryRun = false;

		// migration check
		$alreadyMigrated = \DB::getQueryOne("SELECT * FROM `{$dstUploadTable}` LIMIT 1");
		if (!empty($alreadyMigrated) && !$dryRun) {
			return true;
		}

		if (!$dryRun && !$this->backup()) {
			__pout('Backup error');
			return false;
		}

		if ($dryRun) {
			__pout([
				'info' => 'DryRun aktiviert: es werden keine DB-Änderungen oder Datei-Kopien durchgeführt.',
				'params' => compact('srcUploadTable','dstUploadTable','srcUploadLangTable','dstUploadLangTable','srcLinkTable','dstObjectLinkTable','srcFilesDir','dstFilesDir','batchSize')
			]);
		}

		try {
			if (!\Util::checkTableExists($srcUploadTable) || !\Util::checkTableExists($dstUploadTable)) {
				__pout("Fehler: Tabelle fehlt {$srcUploadTable} / {$dstUploadTable}");
				return false;
			}

			$hasLangSrc = \Util::checkTableExists($srcUploadLangTable);
			$hasLangDst = \Util::checkTableExists($dstUploadLangTable);
			$hasLinkSrc = \Util::checkTableExists($srcLinkTable);
			$hasLinkDst = \Util::checkTableExists($dstObjectLinkTable);

			$this->ensureDir($dstFilesDir, $dryRun);

			// Uploads migrieren
			$this->migrateUploads($srcUploadTable, $dstUploadTable, $batchSize, $dryRun);

			// Languages
			if ($hasLangSrc && $hasLangDst) {
				$this->migrateLanguages($srcUploadLangTable, $dstUploadLangTable, $batchSize, $dryRun);
			} elseif ($hasLangSrc xor $hasLangDst) {
				__pout('Hinweis: Sprach-Tabellen (Quelle/Ziel) sind asymmetrisch vorhanden. Migration Sprachen übersprungen.');
			}

			// Objekt-Links (school_id -> object_id)
			if ($hasLinkSrc && $hasLinkDst) {
				$this->migrateObjectLinks($srcLinkTable, $dstObjectLinkTable, $batchSize, $dryRun);
			} elseif ($hasLinkSrc xor $hasLinkDst) {
				__pout('Hinweis: Link-Tabellen (Quelle/Ziel) sind asymmetrisch vorhanden. Migration Links übersprungen.');
			}

			// Dateien kopieren
			// Es kann doch /ts/uploads/... benutzt werden
			//$this->copyFiles($srcUploadTable, $srcFilesDir, $dstFilesDir, $batchSize, $dryRun);

			if ($dryRun) {
				__pout('DryRun beendet – Migration simuliert, keine Daten verändert.');
			}

		} catch (\Throwable $e) {
			__pout($e);
			return false;
		}

		if (!$dryRun) {
			\DB::executeQuery('DROP TABLE `kolumbus_upload`');
			\DB::executeQuery('DROP TABLE `kolumbus_upload_languages`');
			\DB::executeQuery('DROP TABLE `kolumbus_upload_to_schools`');
		}

		return true;
	}

	/* ========================
	 * Worker: Uploads
	 * ======================== */
	private function migrateUploads(string $src, string $dst, int $batchSize, bool $dryRun): void
	{
		$total = (int)(\DB::getQueryRow("SELECT COUNT(*) AS c FROM `{$src}`")['c'] ?? 0);
		$sql   = "INSERT INTO `{$dst}`
					(`id`,`changed`,`created`,`active`,`creator_id`,`editor_id`,`category`,`description`,`filename`)
				  VALUES
					(:id, :changed, :created, :active, :creator_id, :editor_id, :category, :description, :filename)
				  ON DUPLICATE KEY UPDATE `id` = `id`";

		$this->runBatches($total, $batchSize, 'migrate_uploads', $dryRun,
			function(int $offset, int $limit) use ($src) {
				return \DB::getQueryRows("SELECT * FROM `{$src}` ORDER BY `id` ASC LIMIT {$limit} OFFSET {$offset}");
			},
			function(array $row) use ($sql, $dryRun) {
				$catId    = (int)$row['category_id'];

				$params = [
					'id'         => (int)$row['id'],
					'changed'    => (string)$row['changed'],
					'created'    => (string)$row['created'],
					'active'     => (int)$row['active'],
					'creator_id' => (int)$row['creator_id'],
					'editor_id'  => (int)$row['editor_id'],
					'category'   => $catId,
					'description'=> (string)$row['description'],
					'filename'   => (string)$row['filename'],
				];

				$this->execOrDry($sql, $params, $dryRun, 'tc_upload');
			},
			function(int $batchNo, int $processed, int $totalInBatch) use ($dryRun) {
				if ($dryRun) {
					__pout([
						'dryrun'   => 'tc_upload batch',
						'batch'    => $batchNo,
						'processed'=> $processed,
						'count'    => $totalInBatch,
					]);
				}
			}
		);
	}

	/* ========================
	 * Worker: Languages
	 * ======================== */
	private function migrateLanguages(string $src, string $dst, int $batchSize, bool $dryRun): void
	{
		$total = (int)(\DB::getQueryRow("SELECT COUNT(*) AS c FROM `{$src}`")['c'] ?? 0);
		$sql   = "INSERT INTO `{$dst}` (`upload_id`,`language_iso`)
				  VALUES (:upload_id, :language_iso)
				  ON DUPLICATE KEY UPDATE `upload_id`=`upload_id`";

		$this->runBatches($total, $batchSize, 'migrate_languages', $dryRun,
			function(int $offset, int $limit) use ($src) {
				return \DB::getQueryRows("SELECT `upload_id`, `language` FROM `{$src}` ORDER BY `upload_id` ASC, `language` ASC LIMIT {$limit} OFFSET {$offset}");
			},
			function(array $row) use ($sql, $dryRun) {
				$params = [
					'upload_id'   => (int)$row['upload_id'],
					'language_iso'=> (string)$row['language'],
				];
				$this->execOrDry($sql, $params, $dryRun, 'tc_upload_languages');
			},
			function(int $batchNo, int $processed, int $totalInBatch) use ($dryRun) {
				if ($dryRun) {
					__pout([
						'dryrun'   => 'tc_upload_languages batch',
						'batch'    => $batchNo,
						'processed'=> $processed,
						'count'    => $totalInBatch,
					]);
				}
			}
		);
	}

	/* ========================
	 * Worker: Object Links
	 * ======================== */
	private function migrateObjectLinks(string $src, string $dst, int $batchSize, bool $dryRun): void
	{
		$total = (int)(\DB::getQueryRow("SELECT COUNT(*) AS c FROM `{$src}`")['c'] ?? 0);
		$sql   = "INSERT INTO `{$dst}` (`upload_id`,`object_id`)
				  VALUES (:upload_id, :object_id)
				  ON DUPLICATE KEY UPDATE `upload_id`=`upload_id`";

		$this->runBatches($total, $batchSize, 'migrate_links', $dryRun,
			function(int $offset, int $limit) use ($src) {
				return \DB::getQueryRows("SELECT `upload_id`, `school_id` FROM `{$src}` ORDER BY `upload_id` ASC, `school_id` ASC LIMIT {$limit} OFFSET {$offset}");
			},
			function(array $row) use ($sql, $dryRun) {
				$params = [
					'upload_id' => (int)$row['upload_id'],
					'object_id' => (int)$row['school_id'], // school_id -> object_id
				];
				$this->execOrDry($sql, $params, $dryRun, 'tc_upload_objects');
			},
			function(int $batchNo, int $processed, int $totalInBatch) use ($dryRun) {
				if ($dryRun) {
					__pout([
						'dryrun'   => 'tc_upload_objects batch',
						'batch'    => $batchNo,
						'processed'=> $processed,
						'count'    => $totalInBatch,
					]);
				}
			}
		);
	}

	/* ========================
	 * Worker: Files
	 * ======================== */
	private function copyFiles(string $srcUploadTable, string $srcDir, string $dstDir, int $batchSize, bool $dryRun): void
	{
		$total = (int)(\DB::getQueryRow("SELECT COUNT(*) AS c FROM `{$srcUploadTable}` WHERE `filename` <> ''")['c'] ?? 0);

		$this->runBatches($total, $batchSize, 'copy_files', $dryRun,
			function(int $offset, int $limit) use ($srcUploadTable) {
				return \DB::getQueryRows("SELECT `id`,`filename` FROM `{$srcUploadTable}` WHERE `filename` <> '' ORDER BY `id` ASC LIMIT {$limit} OFFSET {$offset}");
			},
			function(array $row) use ($srcDir, $dstDir, $dryRun) {
				$src = rtrim($srcDir, '/') . '/' . $row['filename'];
				$dst = rtrim($dstDir, '/') . '/' . $row['filename'];

				if ($dryRun) {
					__pout(['dryrun' => 'COPY', 'from' => $src, 'to' => $dst]);
					return;
				}

				$dstSub = dirname($dst);

				$this->ensureDir($dstSub, $dryRun);

				if (!@copy($src, $dst)) {
					__pout(['ERROR copying', $src, $dst]);
				}
			},
			function(int $batchNo, int $processed, int $totalInBatch) use ($dryRun) {
				if ($dryRun) {
					__pout([
						'dryrun'   => 'files batch',
						'batch'    => $batchNo,
						'processed'=> $processed,
						'count'    => $totalInBatch,
					]);
				}
			}
		);
	}

	/* ========================
	 * Helpers
	 * ======================== */

	/**
	 * Führt Batches über SELECT/LIMIT/OFFSET aus und kapselt Transaktion (sofern nicht DryRun).
	 *
	 * @param int $total             Total rows
	 * @param int $batchSize         Batch size
	 * @param string $txName         Transaction name
	 * @param bool $dryRun           Simulationsmodus
	 * @param callable $fetchBatch   fn(int $offset, int $limit): array
	 * @param callable $handleRow    fn(array $row): void
	 * @param callable|null $afterBatch fn(int $batchNo, int $processed, int $countInBatch): void
	 */
	private function runBatches(
		int $total,
		int $batchSize,
		string $txName,
		bool $dryRun,
		callable $fetchBatch,
		callable $handleRow,
		?callable $afterBatch = null
	): void {
		if ($total <= 0) {
			if ($dryRun) { __pout(['dryrun' => 'skip (0 rows)', 'tx' => $txName]); }
			return;
		}

		$offset   = 0;
		$batchNo  = 0;

		while ($offset < $total) {
			$batchNo++;
			$rows = $fetchBatch($offset, $batchSize);
			if (!$rows) break;

			if (!$dryRun) { \DB::begin($txName); }

			$processed = 0;
			try {
				foreach ($rows as $row) {
					$handleRow($row);
					$processed++;
				}
				if (!$dryRun) { \DB::commit($txName); }
			} catch (\Throwable $e) {
				if (!$dryRun) { \DB::rollback($txName); }
				__pout($e);
			}

			if ($afterBatch) {
				$afterBatch($batchNo, $processed, is_array($rows) ? count($rows) : 0);
			}

			$offset += $batchSize;
		}
	}

	/**
	 * Führt ein Prepared-Statement aus oder loggt im DryRun die Absicht.
	 */
	private function execOrDry(string $sql, array $params, bool $dryRun, string $tag): void
	{
		if ($dryRun) {
			__pout([
				'dryrun' => $tag,
				'sql'    => $sql,
				'params' => $params,
			]);
			return;
		}
		\DB::executePreparedQuery($sql, $params);
	}

	/**
	 * Zielverzeichnis sicherstellen (mit DryRun-Logging).
	 */
	private function ensureDir(string $dir, bool $dryRun): void
	{
		if (is_dir($dir)) {
			if ($dryRun) { __pout(['dryrun' => 'MKDIR skip (exists)', 'dir' => $dir]); }
			return;
		}

		if ($dryRun) {
			__pout(['dryrun' => 'MKDIR', 'dir' => $dir]);
			return;
		}

		\Util::checkDir($dir);
	}

	private function backup(): bool
	{
		$backup = [
			\Util::backupTable('kolumbus_upload'),
			\Util::backupTable('kolumbus_upload_languages'),
			\Util::backupTable('kolumbus_upload_to_schools'),
		];

		return !in_array(false, $backup);
	}
}
