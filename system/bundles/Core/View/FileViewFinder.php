<?php

namespace Core\View;

use \Illuminate\Support\Str;

class FileViewFinder extends \Illuminate\View\FileViewFinder {

	protected function findInPaths($name, $paths) {

		// Falls mit @ ein anderes Bundle angegeben wurde (z.b. @Core) den Resources-Pfad von diesem hinzufügen
		if(substr($name, 0, 1) === '@') {
			$name = Str::of($name);

			$bundle = $name->before('.')->replace('@', '');
			$bundleDir = (new \Core\Helper\Bundle())->getBundleDirectory((string)$bundle);

			$name = $name->replace('@'.$bundle, '');
			$paths[] = $bundleDir.'/Resources/views';
		}

		return parent::findInPaths($name, $paths);
	}

}
