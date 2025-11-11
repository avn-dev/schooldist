<?php

namespace Tc\Traits;

trait Filename
{
	public function addCounter($fileName, $path, $extension = '.pdf')
	{
		// Schauen, ob schon eine Datei existiert, falls ja, Zähler anhängen
		$iCount = 0;
		do {

			if ($iCount == 3000) {
				throw new \RuntimeException('Maximum count reached for file name: ' . $fileName . $iCount . $extension);
			}

			if ($iCount > 0) {
				$newName = $fileName . $iCount;
			} else {
				$newName = $fileName;
			}

			$iCount++;

		} while (file_exists($path . $newName . $extension));

		return $newName;
	}
}