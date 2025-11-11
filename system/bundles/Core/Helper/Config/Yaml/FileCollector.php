<?php

namespace Core\Helper\Config\Yaml;

class FileCollector extends \Core\Helper\FileCollector {

	protected $sFilePattern = "/Resources/config/config.yml";
	
	protected $sFileClass = "\Core\Helper\Config\Yaml\File";

}