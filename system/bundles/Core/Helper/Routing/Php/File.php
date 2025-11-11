<?php

namespace Core\Helper\Routing\Php;

use Illuminate\Support\Facades\Route;

/**
 * Diese Klasse soll eine composer.json-Datei darstellen. 
 */
class File extends \Core\Helper\Routing\AbstractFile {

	public function parseContent() {	
		
		$sFileName = $this->_sFileName;
		
		Route::group(['bundle' => $this->sBundle, 'as' => $this->sBundle.'.'], function() use ($sFileName) {
			require_once $sFileName;
		});
		
	}

}