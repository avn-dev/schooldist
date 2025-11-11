<?
////////////////////////////////////////////////////////////////////////////////////////
// CMS Module
// Beschreibung: Download-Modul
//
//
// copyright by plan-i GmbH
// Michael Zimmermann (mz@plan-i.de)
//
// Erstellungsdatum: 26.06.2002
// letzte Änderung: 26.06.2002
// durch: Michael Zimmermann (mz@plan-i.de)
//
// ALLE RECHTE VORBEHALTEN
// NICHT AUTORISIERTE VERVIELFÄLTIGUNG ODER VERWENDUNG WIRD STRAFRECHTLICH VERFOLGT!
// Bitte kontaktieren Sie uns, wenn Sie befürchten eine nicht legale Kopie dieser
// Sourcen gefunden zu haben!
// Tel: +49 (0)241 189 28 20 oder Email: piracy@plan-i.de
// ALL RIGHTS RESERVED!
// UNAUTHORIZED DUPLICATION OR USE WILL BE PROSECUTED!
// Please contact us, if you feare to have an unauthorized version of this software
// sources.
// phone: +49 (0)241 18928 20 or email: piracy@plan-i.de
////////////////////////////////////////////////////////////////////////////////////////

global $color_a,$user_data, $DOCUMENT_ROOT, $upload_dir, $HTTP_HOST;
$media_pic_dir = "/admin/images/";

//gruppen_id des users holen
if($user_data[id] > 0){
$down_abfrage4 = (db_query($db_system, "SELECT status FROM user WHERE id = '$user_data[id]'"));
$down_array4 = get_data($down_abfrage4);
$user_data[group] = $down_array4[status];
}

//template laden
//prüfen ob ein Benutzerdefiniertes Template vorliegt
$down_abfrage = (db_query($db_system, "SELECT * FROM content WHERE page_id = '$page_id' AND number = '$element'"));
$down_array = get_data($down_abfrage);
$down_template = $down_array[content];

if(strlen($down_template) < 1){
//wenn kein Benutzertemplate dann Vorlageneinstellung laden
$down_abfrage = (db_query($db_module, "SELECT * FROM download_templatezuordnung WHERE page_id = '$page_id' AND element_id = '$element'"));
$down_array = get_data($down_abfrage);
$down_template_id = $down_array[template_id];

//wenn Vorlageneinstellung vorhanden dann Vorlagentemplate laden
if($down_template_id > 0){
$down_abfrage = (db_query($db_system, "SELECT * FROM templates WHERE id = '$down_template_id'"));
$down_array = get_data($down_abfrage);
$down_template = $down_array[template];
}else{
$down_abfrage = (db_query($db_system, "SELECT * FROM templates WHERE file = 'download' AND name = 'standard'"));
$down_array = get_data($down_abfrage);
$down_template = $down_array[template];
}//else
}//if (strlen($down_template < 1)

//$down_template = $my_element[content];

//loops für Downloadlinks holen
//ich habe vorerst 2 verschiedene loops vorgesehen um so zum Beispiel Tabellen aufzubauen
unset($down_loop2);
unset($down_loop1);
ereg("<#loop_download_1#>(.*)<#\/loop_download_1#>", $down_template, $down_loop1);
ereg("<#loop_download_2#>(.*)<#\/loop_download_2#>", $down_template, $down_loop2);
//wenn kein extra loop für 2 dann auch den ersten nehmen
if(!$down_loop2[1]){$down_loop2[1]=$down_loop1[1];}

//loops für Kategorienamen holen
unset($down_maincat_loop);
unset($down_subcat_loop);
ereg("<#loop_hauptkategorie_name#>(.*)<#\/loop_hauptkategorie_name#>", $down_template, $down_maincat_loop);
ereg("<#loop_subkategorie_name#>(.*)<#\/loop_subkategorie_name#>", $down_template, $down_subcat_loop);
//und diese gleich löschen
$down_template  = eregi_replace("<#loop_hauptkategorie_name#>(.*)<#\/loop_hauptkategorie_name#>", "", $down_template);
$down_template  = eregi_replace("<#loop_subkategorie_name#>(.*)<#\/loop_subkategorie_name#>", "", $down_template);

//Daten holen
$down_abfrage = (db_query($db_module, "SELECT * FROM download WHERE page_id = '$page_id' AND element_id = '$element' AND active='1' AND (benutzer_gruppen_id = '0' OR benutzer_gruppen_id = '$user_data[group]') ORDER BY number"));
while($down_array = get_data($down_abfrage)) {
//alte daten löschen
unset($down_bild_file);
unset($down_array2);
unset($down_array3);
//Dateinamen ect holen
$down_abfrage2 = (db_query($db_system, "SELECT * FROM media WHERE id = '$down_array[media_id]'"));
$down_array2 = get_data($down_abfrage2);

		 //wenn andere Kategorie als Vorgänger dann Tabelle ausgeben
		 if($down_array[cat_id] != $last_cat_id AND $down_ok1 == 1){
         $down_loop_table[1]  = eregi_replace("<#loop_download_1#>(.*)<#\/loop_download_1#>", "$listegesamt", $down_loop_table[1]);
         $down_loop_table[1]  = eregi_replace("<#loop_download_2#>(.*)<#\/loop_download_2#>", "", $down_loop_table[1]);
		 $down_loop_table[1] = str_replace("<#bgcolor#>",$color_a,$down_loop_table[1]);
		 echo $cat_string.$down_loop_table[1];
		 unset($listegesamt);
		 }

//wenn andere Kategorie als Vorgänger
//if($down_array[cat_id] != $last_cat_id AND $down_ok1 == 1){
//Kategorienamen holen ersetzen und ausgeben
$down_abfrage5 = (db_query($db_module, "SELECT * FROM download_cat WHERE id = '$down_array[cat_id]'"));

$down_array5 = get_data($down_abfrage5);
if($down_array5[main_cat] < 1){
$cat_string = $down_maincat_loop[1];
}else{
$cat_string = $down_subcat_loop[1];
}//else
$cat_string = str_replace("<#kategorie_name#>", $down_array5[german], $cat_string);
//echo $cat_string;
//}//if

//ggf. Bilddatei holen bei einstellung "eigenes Bild"
if($down_array[picfile_media_id]>0){
$down_abfrage3 = (db_query($db_system, "SELECT * FROM media WHERE id = '$down_array[picfile_media_id]'"));
$down_array3 = get_data($down_abfrage3);
$down_bild_file = $upload_dir.$down_array3[file];
}

unset($measure);
if($size = @getimagesize($DOCUMENT_ROOT.$upload_dir.$down_array2[file])) {
$image_width = $size[0];
$image_height = $size[1];
$measure = "$image_width x $image_height";
}


//Bild holen bei einstellung "standard"
if($down_array[pic]==1){
//falls für verschiedene typen ein icon gilt diese zusammenfassen
if($down_array2[extension] == "tif" OR $down_array2[extension] == "png" OR $down_array2[extension] == "bmp"){
$down_array2[extension] = "jpg";
}
if($down_array2[extension] == "gif" OR $down_array2[extension] == "jpg" OR $down_array2[extension] == "pdf" OR $down_array2[extension] == "ppt" OR $down_array2[extension] == "swf" OR $down_array2[extension] == "xls" OR $down_array2[extension] == "zip" OR $down_array2[extension] == "exe" OR $down_array2[extension] == "txt" OR $down_array2[extension] == "doc"){
$down_bild_file = $media_pic_dir."media_".$down_array2[extension].".gif";
}else{
$down_bild_file = $media_pic_dir."media_misc.gif";
}//else
}//if pic==1
//wenn kein bild
if($down_array[pic]==0){unset($down_bild_file);}

//wenn kein titel-text dann filename nehemen
if(strlen($down_array2[title]) < 2){$down_array2[title] = $down_array2[file];}

//$link_string = "<a href=\"$upload_dir$down_array2[file]\" type=\"application/unknown\">$down_array2[title]</a>";
$link_string = "<a href=\"/system/applications/download.php?down_id=$down_array[id]\">$down_array2[title]</a>";
if($down_bild_file){
$bild_string = "<a href=\"/system/applications/download.php?down_id=$down_array[id]\"><img src=\"$down_bild_file\" border=\"0\"></a>";
}
//loop für Tabelle holen
unset($down_loop_table);
ereg("<#loop_tabelle#>(.*)<#\/loop_tabelle#>", $down_template, $down_loop_table);

         if($r == 1){
         $liste = str_replace("<#download_link#>", "$link_string", $down_loop1[1]);
         $liste = str_replace("<#download_bild#>", "$bild_string", $liste);
         $liste = str_replace("<#bildgroesse#>", "$measure", $liste);
         $liste = str_replace("<#dateigroesse#>", "$down_array2[size]", $liste);
         $liste = str_replace("<#beschreibung#>", "$down_array2[description]", $liste);
         $liste = str_replace("<#count#>", "$down_array[count]", $liste);
         $r = 0;
         }else{
         $liste = str_replace("<#download_link#>", "$link_string", $down_loop2[1]);
         $liste = str_replace("<#download_bild#>", "$bild_string", $liste);
         $liste = str_replace("<#bildgroesse#>", "$measure", $liste);
         $liste = str_replace("<#dateigroesse#>", "$down_array2[size]", $liste);
         $liste = str_replace("<#beschreibung#>", "$down_array2[description]", $liste);
         $liste = str_replace("<#count#>", "$down_array[count]", $liste);
         $r = 1;
         }//else
         $listegesamt = $listegesamt.$liste;



		 $last_cat_id = $down_array[cat_id];
		 $down_ok1 = 1;
         unset($link_string);
         unset($bild_string);
}//while
         $down_template  = eregi_replace("<#loop_download_1#>(.*)<#\/loop_download_1#>", "$listegesamt", $down_template);
         $down_template  = eregi_replace("<#loop_download_2#>(.*)<#\/loop_download_2#>", "", $down_template);
         $down_template  = eregi_replace("<#loop_tabelle#>", "", $down_template);
         $down_template  = eregi_replace("<#\/loop_tabelle#>", "", $down_template);
		 $down_template = str_replace("<#bgcolor#>",$color_a,$down_template);
         echo $cat_string.$down_template;

unset($down_template);
unset($link_string);
unset($bild_string);
unset($down_array);
unset($down_bild_file);
unset($down_array2);
unset($liste);
unset($listegesamt);
unset($last_cat_id);
?>