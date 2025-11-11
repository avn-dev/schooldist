<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class Handler_Monolog_ZipFile extends RotatingFileHandler {

    protected string $filename;

    protected $maxNoneZipFiles = 1;

	// Logs für ein Jahr speichern
    protected $maxZipFiles = 360;

    protected ?bool $mustRotate = null;

    public function __construct($filename, $maxFiles = 0, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false) {
        
        $fileInfo = pathinfo($filename);
        Util::checkDir($fileInfo['dirname']);

		if($filePermission === null) {
			$filePermission = 0777;
//			$filePermission = \System::d('chmod_mode_file');
//			if(is_string($filePermission)) {
//				$filePermission = decoct($filePermission);
//			}
		}

        parent::__construct($filename, $maxFiles, $level, $bubble, $filePermission, $useLocking);
    }

	/**
	 * Methode überschreiben, damit unabhängig von Zeitzone der Dateiname immer UTC hat
	 *
	 * @return string
	 */
	protected function getTimedFilename(): string {

		$aFileInfo = pathinfo($this->filename);
		$sFilename = $aFileInfo['dirname'].'/'.$aFileInfo['filename'].'-'.gmdate('Y-m-d').'.log';

		return $sFilename;

	}

    /**
    * Rotates the files.
    */
    protected function rotate(): void {

        $fileInfo = pathinfo($this->filename);

        Util::checkDir($fileInfo['dirname']);
        
        $glob = $fileInfo['dirname'].'/'.$fileInfo['filename'].'-[0-9]*'; // frontend.log != frontend-combinations-schools.log
        if (!empty($fileInfo['extension'])) {
            $glob .= '.'.$fileInfo['extension'];
        }
        $iterator = new \GlobIterator($glob);
		try {
			$count = $iterator->count();
		} catch ( \LogicException $e) {
			$count = 0;
		}

        // nur eine Datei nicht zippen
        if ($this->maxNoneZipFiles < $count) {
            // Sorting the files by name to remove the older ones
            $array = iterator_to_array($iterator);
            usort($array, function($a, $b) {
                return strcmp($b->getFilename(), $a->getFilename());
            });

            foreach (array_slice($array, $this->maxNoneZipFiles) as $file) {
                if ($file->isWritable()) {
                    if(class_exists('ZipArchive')){
                        $zip = new ZipArchive;
                        if ($zip->open($file->getRealPath().'.zip', ZipArchive::CREATE) === TRUE) {
                            $zip->addFile($file->getRealPath(), $file->getFilename());
                            $zip->close();
                            unlink($file->getRealPath());
                        } else {
                            __pout('Fehler beim Zippen der Logs');
                        }
                    } else {
                        unlink($file->getRealPath());
                    }
                }
            }
        }

        // Alte ZIP Dateien löschen
        $glob = $fileInfo['dirname'].'/'.$fileInfo['filename'].'-[0-9]*';
        if (!empty($fileInfo['extension'])) {
            $glob .= '.log.zip';
        }
        $iterator = new \GlobIterator($glob);
        $count = $iterator->count();

        // nur eine Datei nicht zippen
        if ($this->maxZipFiles < $count) { // 3 Monate(90 Tage)
            // Sorting the files by name to remove the older ones
            $array = iterator_to_array($iterator);
            usort($array, function($a, $b) {
                return strcmp($b->getFilename(), $a->getFilename());
            });

            foreach (array_slice($array, $this->maxZipFiles) as $file) {
                if ($file->isWritable()) {
                    unlink($file->getRealPath());
                }
            }
        }

    }

}
