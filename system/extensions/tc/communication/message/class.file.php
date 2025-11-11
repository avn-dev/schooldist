<?php

use Communication\Traits\Model\Log\WithModelRelations;

/**
 * WDBASIC der Messages der Relations zu Dateien
 */
class Ext_TC_Communication_Message_File extends Ext_TC_Basic
{
	use WithModelRelations;

	protected $_sTable = 'tc_communication_messages_files';
	protected $_sTableAlias = 'tc_cmf';

	protected $_aJoinedObjects = [
		'message' => [
			'class' => \Ext_TC_Communication_Message::class,
			'key' => 'message_id',
			'type' => 'parent'
		]
	];

	protected $_aJoinTables = array(
		'relations' => array(
			'table' => 'tc_communication_messages_files_relations',
			'foreign_key_field' => array('relation', 'relation_id'),
			'primary_key_field' => 'file_id'
		)
	);

	public function save($bLog = true)
	{
		// Dateien aus dem temporären Verzeichnis in das korrekte Verzeichnis verschieben
		if (str_contains($this->file, '/tmp/')) {
			$message = $this->getJoinedObject('message');

			if ($message->exist()) {
				$fullPath = realpath(storage_path(\Illuminate\Support\Str::after($this->file, 'storage/')));
				$oldDirectory = dirname($fullPath);

				if (str_contains($fullPath, '/tc/communication/out/') && file_exists($fullPath)) {
					$uploadDirectory = storage_path('tc/communication/out/'.\Util::getCleanPath($message->id));

					\Util::checkDir($uploadDirectory);

					$target = $uploadDirectory.'/'.basename($this->file);

					if (rename($fullPath, $target)) {
						$this->file = '/storage/'.\Illuminate\Support\Str::after($target, 'storage/');
						$oldDirFiles = glob($oldDirectory.'/*');
						if (empty($oldDirFiles)) {
							rmdir($oldDirectory);
						}
					}
				}
			}
		}

		return parent::save($bLog);
	}

	public function delete() {
		
		$sFile = Util::getDocumentRoot(false).$this->file;
		if (is_file($sFile)) {
			unlink($sFile);
		}

		parent::delete();
		
	}

}