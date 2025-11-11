<?php

require_once __DIR__."/../../../includes/console.inc.php";

// Diese Datei muss leider weiter redundant existieren für das veraltete thebing-elasticsearch-index-update
$oApplication = new \Core\Service\Console(app(), app('events'), \System::d('version'));
$oApplication->setName('Fidelo Framework - Console tool');
$oApplication->addBundleCommands();
$oApplication->run();
