<?php
require_once('./main.inc.php');

function strip_comments($s) {
    $comment1 = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';

    $single = "'[^'\\\]*(?:\\.[^'\\\]*)*'";
    $double = '"[^"\\\]*(?:\\.[^"\\\]*)*"';
    $eot = '<<<\s?(\S+)\b.*^\2';

    $other = '[^"\'/\#<]';
    $r = preg_replace ("#($other+ | 
                  $single$other* | 
                  $double$other* | 
                  $eot$other*)| 
                  $comment1
                #msx" ,'\1', $s);

    return $r;
}

// profi mod - styles edit - to set old styles inactive
function setOldStyleInactive($sStyleName)
{
	$aSql = array(
		'CSS' => $sStyleName
	);
	$sSql = "
		UPDATE
			`cms_styles`
		SET
			`active` = 0
		WHERE
			`file` = '".$sStyleName."'
	";
	db_query($sSql);
}

function style_import($sCss,$sFile){
	// Rechte prüfen.
	global $sStylesPermissionName;
	if (!hasRight($sStylesPermissionName)) {
		return array();
	}
	$sCss = strip_comments($sCss);
	preg_match_all("/([^{]+){([^}]+)}/i",$sCss,$aMatches);
	foreach($aMatches[0] as $k=>$v) {
		$style_aufgabe = db_query("INSERT INTO cms_styles (name, description, style, type, file, active) VALUES ('".\DB::escapeQueryString(trim($aMatches[1][$k]))."', '', '".\DB::escapeQueryString(trim(str_replace("\t", "", $aMatches[2][$k])))."', 'system', '".$sFile."', 1)");
	}
//$iMatches = preg_match_all("/([#.&nbsp;]{1}[a-z\s]+){((?:[^{}]++|(?1)))}/", $sCss, $aMatches);
//	$sStyleFile = $sFile;
//
//	foreach($aMatches[1] as $key => $aValue){
//		$arrCssClass[$aValue]= $aMatches[2][$key];
//		$sSql = "INSERT INTO
//						`cms_styles`
//					(
//						`name`,
//						`description`,
//						`type`,
//						`style`,
//						`file`,
//						`active`
//					) VALUES (
//						'".\DB::escapeQueryString($aValue)."',
//						'',
//						'".\DB::escapeQueryString('system')."',
//						'".\DB::escapeQueryString($arrCssClass[$aValue])."',
//						'".\DB::escapeQueryString($sFile)."',
//						1
//					)";
//		db_query($sSql);
//	}
//
//	enterlog(0,"CSS-Stile wurden importiert.");
}
if(isset($_VARS['task']) && $_VARS['task'] == 'import')
{
	if(isset($_VARS['set_inactive']))
	{
		setOldStyleInactive($_VARS['set_inactive']);
	}
	$sCss = rawurldecode($_VARS['sCss']);
	style_import($sCss,$_VARS['sFile']);
}
////old mehod
//switch ($_VARS['task']) {
//	case "import":
//
//		break;
//}
?>
