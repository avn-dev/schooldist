<?php

namespace Core\Helper\Config\Php;

class FileCollector extends \Core\Helper\FileCollector {

	protected $sFilePattern = "/Resources/config/config.php";
	
	protected $sFileClass = \Core\Helper\Config\Php\File::class;

}