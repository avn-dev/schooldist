<?
// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)

$buffer_errors = $element_data['content'];

if($_SERVER['REDIRECT_STATUS']) {

	if(
		$_SERVER['REDIRECT_URL'] != '/favicon.ico'
	) {
	
		$my = get_data(db_query($db_data['module'],"SELECT message FROM errors_messages WHERE error = '".$_SERVER['REDIRECT_STATUS']."' AND language = '".$language."'"));
	
		$buffer_errors = str_replace("<#error_page#>", htmlentities($_SERVER['REDIRECT_URL']), $buffer_errors);
		$buffer_errors = str_replace("<#error_text#>", htmlentities($my['message']." (Code: ".$_SERVER['REDIRECT_STATUS'].")"), $buffer_errors);
		$buffer_errors = str_replace("<#error_email#>", htmlentities(\System::d('error_email')), $buffer_errors);
		error($my['message'],1,0);
	
		$GLOBALS['session_data']['error'] = true;
	
		echo $buffer_errors;
		
	}

}

?>