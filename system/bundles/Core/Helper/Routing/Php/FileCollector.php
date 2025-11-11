<?php

namespace Core\Helper\Routing\Php;

use Illuminate\Support\Arr;

class FileCollector extends \Core\Helper\FileCollector
{
	protected $sFilePattern = "/Resources/config/routes.php";
	protected $sFileClass = \Core\Helper\Routing\Php\File::class;

	protected function sortFiles(array $fileNames): array
	{
		$admin = Arr::first($fileNames, fn ($file) => str_contains($file, '/bundles/Admin/'));

		// TODO Anders/Zentraler lösen
		// Sichergehen dass das Admin-Bundle erst ganz am Ende eingelesen wird
		if ($admin) {
			$fileNames = array_combine($fileNames, $fileNames);
			unset($fileNames[$admin]);
			$fileNames[$admin] = $admin;

			return array_values($fileNames);
		}

		return $fileNames;
	}
}