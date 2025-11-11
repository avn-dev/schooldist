<?
// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)

Global $wnid;

$actual_date = date("YmdHis");

if ($wnid > 0) {
	$my_news_result = db_query($db_module,"SELECT * FROM news_data WHERE id = '$wnid'");
} else {
	$my_news_result = db_query($db_module,"SELECT * FROM news_data WHERE language = '$language' AND active = 1 AND validfrom < '$actual_date' AND validto > '$actual_date' ORDER BY date DESC LIMIT 1");
}

$my_news = get_data($my_news_result);

$tag = substr($my_news['date'],6,2);
$monat = substr($my_news['date'],4,2);
$jahr = substr($my_news['date'],0,4);
$stunde = substr($my_news['date'],8,2);
$minute = substr($my_news['date'],10,2);

$datumgesamt = strftime("%x",mktime(0,0,0,$monat,$tag,$jahr));//$tag.".".$monat.".".$jahr;

$my_news['text'] = strip_tags($my_news['text'],"<img><p><br>");

?>

<?=$datumgesamt?>
<h2><?=stripslashes($my_news['title'])?></h2>
<h3><?=stripslashes($my_news['subtitle'])?></h3>
<p style="text-align:justify;"><?=$my_news['text']?></p>

<?

//ggf. Bilddatei holen
if($my_news[image]>0){
$down_abfrage3 = (db_query($db_system, "SELECT * FROM system_media WHERE id = '$my_news[image]'"));
$down_array3 = get_data($down_abfrage3);
$news_bild_file = $upload_dir.$down_array3[file];
echo "<img src=".$news_bild_file." border=\"0\">";
}
?>