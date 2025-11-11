<?php

include_once(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/autoload.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/debug.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/config.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");

// set flag for cms backend page
System::setInterface('backend');
DB::setResultType(MYSQL_ASSOC);

include_once(\Util::getDocumentRoot()."system/includes/variables.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/admin.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/access.inc.php");