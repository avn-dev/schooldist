<?php

include_once(__DIR__."/autoload.inc.php");
include_once(__DIR__."/debug.inc.php");
include_once(Util::getDocumentRoot()."config/config.php");
include_once(__DIR__."/dbconnect.inc.php");
$session_data['public'] = 1;
include_once(__DIR__."/variables.inc.php");
include_once(__DIR__."/access.inc.php");