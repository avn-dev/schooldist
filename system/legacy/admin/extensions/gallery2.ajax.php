<?php

session_start();

$_SESSION['gallery2']['default_name'] = stripslashes(urldecode($_REQUEST['name']));

?>