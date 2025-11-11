<?php

namespace Core\Helper\Routing\Yaml;

class FileCollector extends \Core\Helper\FileCollector {

	protected $sFilePattern = "/Resources/config/routes.yml";
	protected $sFileClass = "\Core\Helper\Routing\Yaml\File"; 

}