<?php
if(isset($_POST['task']) && $_POST['task'] == 'test_request')
{
	echo json_encode('wow, hat funktioniert');
	die();
}
?>