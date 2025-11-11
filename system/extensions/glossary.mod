<?
// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)

// Umstellung auf template: 01.2007 B.Huebner


// Template holen
$sTemplate = $element_data["content"];
$sTemplate = stripslashes($sTemplate);


#echo "<br /><br />original:<hr />".$sTemplate."<br /><hr />";



////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

$sTemplate = str_replace('<#PHP_SELF#>',$PHP_SELF, $sTemplate);



// holen der Klassen
$SPAN_text = trim(\Cms\Service\PageParser::checkForBlock($sTemplate,'class_text'));
\Cms\Service\PageParser::replaceBlock($sTemplate ,'class_text',"");

$SPAN_link = trim(\Cms\Service\PageParser::checkForBlock($sTemplate,'class_link'));
\Cms\Service\PageParser::replaceBlock($sTemplate ,'class_link',"");

$letter = $_VARS['letter'];

Global $word, $letter,$gl_keyword;
$word=addslashes(trim($word));
$letter=addslashes(trim($letter));
$gl_keyword=addslashes(trim($gl_keyword));

//////////////////////////////////////////////////
// Hier wird ein einzelnes Wort behandelt
//////////////////////////////////////////////////
if($word) 
{
	$buffer_glossary = \Cms\Service\PageParser::checkForBlock($sTemplate,'word');
	$buffer_mlink = \Cms\Service\PageParser::checkForBlock($sTemplate,'mlink');
	$buffer_olink = \Cms\Service\PageParser::checkForBlock($sTemplate,'olink');
	
	$result_word = db_query($db_module,"SELECT * FROM glossary WHERE active=1 AND word = '$word'");
	
	$my_word = get_data($result_word);
	
	$buffer_glossary = str_replace("<#myword#>",$my_word['word'],$buffer_glossary);
	$buffer_glossary = str_replace("<#mydescription#>",nl2br($my_word['description']),$buffer_glossary);
	
	$sbuffer_alphabet = str_replace("<#letter#>","A-Z",$buffer_mlink);
	
	#for($i=97;$i<=122;$i++) {
	for($i=65;$i<=90;$i++) {
		if(count_rows(db_query($db_module,"SELECT id FROM glossary WHERE active=1 AND word LIKE '".chr($i)."%'  LIMIT 1")) > 0)
			$sbuffer_alphabet .= str_replace("<#letter#>",chr($i),$buffer_mlink);
		else
			$sbuffer_alphabet .= str_replace("<#letter#>",chr($i),$buffer_olink);
	}
	
	$buffer_glossary = \Cms\Service\PageParser::replaceBlock($buffer_glossary,'alphabet',$sbuffer_alphabet);
	
	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate ,'word',$buffer_glossary);
	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate ,'list',"");

} 



//////////////////////////////////////////////////
// Dieser Abschnitt ist für die eigentliche 
// Glossar-Seite zuständig
//////////////////////////////////////////////////
else 
{
	$sTemplate = \Cms\Service\PageParser::checkForBlock($sTemplate,'list');

	if(empty($_VARS['letter']))
	{	
		$letter = "%";
	}
	else
	{
		$letter = $_VARS['letter'];
	}	

	#$i=97;
	$i=65;
	$letters = "<b><a href='$PHP_SELF?letter=%'><span class=".$SPAN_link."\"\">A-Z</span></a></b>";
	
	#while($i<=122) 
	while($i<=90) 
	{
		$letters .= " ";
		if(count_rows(db_query($db_module,"SELECT id FROM glossary WHERE active=1 AND word LIKE '".chr($i)."%'  LIMIT 1")) > 0) 
		{
			$letters .= "<b>";
			$letters .= "<a href='$PHP_SELF?letter=".chr($i)."'>";
			$showlink = true;
			$letters .=  "<span class=".$SPAN_link."\"\">";
		}
		else
		{
			$letters .=  "<span class=".$SPAN_text."\"\">";
			$showlink = false;
		}
		
		if ($letter == chr($i)) 
		{

			$letters .=  "".chr($i)."</span>";

		} 
		else 
		{

			$letters .=  " ".chr($i)."</span> ";

		}
		
		if($showlink) 
		{
			$letters .=  "</a></b>";
		}
		
		$letters .=  " ";
		$i++;
		
	}
	
	$sTemplate = str_replace("<#letters#>",$letters,$sTemplate);
	
	
	$selected_letter="";
	if($letter == "%")
	{
		$selected_letter .=  "<p><span class=".$SPAN_text."><b>A-Z: </b></span><p>";
	}
	else
	{
		$selected_letter .=  "<p><span class=".$SPAN_text."><b>$letter: </b></span><p>";
	}	
	
	$sTemplate = str_replace("<#letter#>",$selected_letter,$sTemplate);
	
	
	
	
	$result_word = db_query($db_module,"SELECT * FROM glossary WHERE active=1 AND (word LIKE '$letter".$gl_keyword."%' OR keywords LIKE '%, $letter".$gl_keyword."%') ORDER BY word");
	
	
	
	if(count_rows($result_word) == 0) 
	{
		$sReplacement = trim(\Cms\Service\PageParser::checkForBlock($sTemplate,"no_result"));
	} 
	else 
	{
		
		// Ergebnisse auflisten:
		$sRow = \Cms\Service\PageParser::checkForBlock($sTemplate,"result-list");
		$sReplacement = "";
		
		while ($my_word = get_data($result_word))
		{	
			$sTmpRow = $sRow;
			
			$sTmpRow = str_replace('<#myword#>',$my_word['word'], $sTmpRow);
			$sTmpRow = str_replace('<#mydescription#>',$my_word['description'], $sTmpRow);
			#echo "<font class=text><b>".$my_word['word']."</b></font><br />".$my_word['description']."<p>";
			
			$sReplacement .= $sTmpRow;
		}
		
		// Ersetze Block:
		
	}
	
	// schreibe Ergebnis zurück
	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate,'result-list',    $sReplacement);

	// Der Block muss in jedem Fall entfernt werden
	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate,'no_result',    "");

	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate ,'word',"");

}


// Aufraeumen 
$pos=0;
while($pos = strpos($sTemplate,'<#',$pos)) 
{
	$end = strpos($sTemplate,'#>',$pos);
	$var = substr($sTemplate, $pos+2, $end-$pos-2);
	$sTemplate = substr($sTemplate, 0, $pos)  .  $$var  .  substr($sTemplate, $end+2);
}




echo $sTemplate;
?>