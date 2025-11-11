<?
// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)

global $color_a,$user_data, $upload_dir, $nid;

$media_pic_dir = "/admin/images/";
$ne_number = \Cms\Service\PageParser::checkForBlock($my_element['content'],'number');
if($ne_number < 1) $ne_number = 10;


//gruppen_id des users holen
if($user_data[id] > 0){
$news_abfrage4 = (db_query($db_system, "SELECT status FROM system_user WHERE id = '$user_data[id]'"));
$news_array4 = get_data($news_abfrage4);
$user_data[group] = $news_array4[status];
}

//template laden
//pr�fen ob ein Benutzerdefiniertes Template vorliegt
$news_abfrage = (db_query($db_system, "SELECT * FROM cms_content WHERE page_id = '".$element_data['page_id']."' AND number = '".$element_data['id']."'"));
$news_array = get_data($news_abfrage);
$news_template = $news_array[content];

if(strlen($news_template) < 1){
//wenn kein Benutzertemplate dann Vorlageneinstellung laden
$news_abfrage = (db_query($db_module, "SELECT * FROM news_data LIMIT 0,1"));
$news_array = get_data($news_abfrage);
$news_template_id = $news_array[template_id];

//wenn Vorlageneinstellung vorhanden dann Vorlagentemplate laden
if($news_template_id > 0){
$news_abfrage = (db_query($db_system, "SELECT * FROM templates WHERE id = '$news_template_id'"));
$news_array = get_data($news_abfrage);
$news_template = $news_array[template];
}else{
$news_abfrage = (db_query($db_system, "SELECT * FROM templates WHERE file = 'news' AND name = 'standard'"));
$news_array = get_data($news_abfrage);
$news_template = $news_array[template];
}//else
}//if (strlen($news_template < 1)

$my_element[content] = $news_template;




$buffer_news = $my_element['content'];

if($nid) {
$my_news = get_data(db_query($db_module,"SELECT * FROM news_data WHERE id = $nid"));

$cache = eregi( "<#detail#>(.*)<#\/detail#>", $buffer_news, $regs );
$buffer_news = $regs[1];

$buffer_news = str_replace("<#nid#>",$nid,$buffer_news);

//ggf. Bilddatei holen
if($my_news[image]>0){
$down_abfrage3 = (db_query($db_system, "SELECT * FROM system_media WHERE id = '$my_news[image]'"));
$down_array3 = get_data($down_abfrage3);
$news_bild_file = $upload_dir.$down_array3[file];
$buffer_news = str_replace("<#newsimage#>","<img src=".$news_bild_file." border=0>",$buffer_news);
} else {
$buffer_news = str_replace("<#newsimage#>","",$buffer_news);
}//else

	$tag = substr($my_news[date],6,2);
	$monat = substr($my_news[date],4,2);
	$jahr = substr($my_news[date],0,4);
	$stunde = substr($my_news[date],8,2);
	$minute = substr($my_news[date],10,2);
    $datumgesamt = $tag.".".$monat.".".$jahr;

$buffer_news = str_replace("<#newstitle#>",stripslashes($my_news['title']),$buffer_news);
$buffer_news = str_replace("<#subtitle#>",stripslashes($my_news['subtitle']),$buffer_news);
$buffer_news = str_replace("<#newstext#>",nl2br($my_news['text']),$buffer_news);
$buffer_news = str_replace("<#shorttext#>",nl2br(stripslashes($my_news['shorttext'])),$buffer_news);
$buffer_news = str_replace("<#bgcolor#>",$color_a,$buffer_news);
$buffer_news = str_replace("<#PHP_SELF#>",$_SERVER['PHP_SELF'],$buffer_news);
$buffer_news = str_replace("<#newsdatum#>",$datumgesamt,$buffer_news);
echo $buffer_news;

$nid = false;
$GLOBALS['nid'] = false;

} else {

$actual_date = date("YmdHis");

$cache = eregi( "<#list#>(.*)<#\/list>", $buffer_news, $regs );
$buffer_news = $regs[1];

$loop_news = eregi( "<#loop_news#>(.*)<#\/loop_news#>", $buffer_news, $regs );

$my_news_result = db_query($db_module,"SELECT * FROM news_data WHERE (language = '$language' OR language = '') AND active = 1 AND validfrom < '$actual_date' AND validto > '$actual_date' AND (benutzer_gruppen_id = '0' OR benutzer_gruppen_id = '$user_data[group]') ORDER BY date DESC LIMIT 0,$ne_number");
$temp_news = "";

while($my_news = get_data($my_news_result)) {

	$tag = substr($my_news[date],6,2);
	$monat = substr($my_news[date],4,2);
	$jahr = substr($my_news[date],0,4);
	$stunde = substr($my_news[date],8,2);
	$minute = substr($my_news[date],10,2);
    $datumgesamt = $tag.".".$monat.".".$jahr;

$cache_news = $regs[1];

//ggf. Bilddatei holen
if($my_news[image]>0){
$down_abfrage3 = (db_query($db_system, "SELECT * FROM system_media WHERE id = '$my_news[image]'"));
$down_array3 = get_data($down_abfrage3);
$news_bild_file = $upload_dir.$down_array3[file];
$cache_news = str_replace("<#newsimage#>","<img src=".$news_bild_file." border=0>",$cache_news);
} else {
$cache_news = str_replace("<#newsimage#>","",$cache_news);
}//else

$cache_news = str_replace("<#phpself#>",$_SERVER['PHP_SELF'],$cache_news);
$cache_news = str_replace("<#newsid#>",$my_news['id'],$cache_news);
$cache_news = str_replace("<#newstitle#>",stripslashes($my_news['title']),$cache_news);
$cache_news = str_replace("<#subtitle#>",stripslashes($my_news['subtitle']),$cache_news);
$cache_news = str_replace("<#newstext#>",nl2br($my_news['text']),$cache_news);
$cache_news = str_replace("<#shorttext#>",nl2br(stripslashes($my_news['shorttext'])),$cache_news);
$cache_news = str_replace("<#newsdatum#>",$datumgesamt,$cache_news);

$temp_news .= $cache_news;

}

$buffer_news = eregi_replace( "<#loop_news#>(.*)<#\/loop_news#>", $temp_news,$buffer_news);
$buffer_news = str_replace("<#bgcolor#>",$color_a,$buffer_news);

echo $buffer_news;
} 
?>