<?php
/*
 * Created  on 09.10.2006
 * @author: Bastian H�bner
 * Dieses Modul gibt ein konfiguriertes
 * SQL Statement aus
 * - Templategesteuert:
 *   <#data#><#/data#> pro Zeile
 *   <#SPALTENNAME#> innerhalb jeder Zeile
 *   f�r jeden Spaltennamen der Tabelle / des Queries
 * - innerhalb von #data# kann es einen Block #sql# geben mit eben diesem
 *
 */



// Konfiguration laden
$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

// Template vorbereiten
$sTemplate = $element_data['content'];

#echo "<br><hr>das template:<br>$sTemplate<br>|ende template||<br><hr><br>";


if(!function_exists('sql_parse_each'))
{
	function sql_parse_each($sTemplate)
	{
		while($sLine=checkforblock_clean($sTemplate,"data"))
		{
			#echo "<br><hr>sLine:<br>$sLine<br><hr><br>";
			$sInBlock='';

			// Query vorbereiten
			$sSQL=trim($config->sSQL);

			// Falls keiner konfiguriert ist, mal ins Template schauen (innerhalb des data-Blocks!!)
			if(!$sSQL)
			{
				$sSQL=\Cms\Service\PageParser::checkForBlock($sLine,"sql");
			}

			$sLine=\Cms\Service\PageParser::replaceBlock($sLine,"sql","");


			// Ersetze moegliche REQUEST Parameter im SQL
			$replaced_SQL='';

			$parts=explode("<#",$sSQL);

			$replaced_SQL=$parts[0];

			if(is_array($parts))
			foreach($parts as $key => $value)
			{
				if($key==0) continue;
				$pieces=explode("#>",$value);
				$default=explode(":",$pieces[0]);

				if(!$_REQUEST[$default[0]])
				{
					$replaced_SQL.=$default[1];
				}
				else
				{
					$replaced_SQL.=$_REQUEST[$default[0]];
				}

				$replaced_SQL.=$pieces[1];

			}


			#$replaced_SQL=\DB::escapeQueryString($replaced_SQL);

			#echo "<!--zblag:".$replaced_SQL."-->";

			// Sicherheitshalber folgende F�lle abfangen:
			/*
			if(strpos($sSQL,"SELECT")!==0) 		die();
			if(strpos($sSQL,"INSERT")!==FALSE) 	die();
			if(strpos($sSQL,"UPDATE")!==FALSE) 	die();
			*/

			// Daten laden

			#echo "<br>$replaced_SQL<br>";

			$rRes=db_query($replaced_SQL);



			// Template zeilenweise abarbeiten
			while($my=get_data($rRes))
			{
				$tmpLine=$sLine;

				foreach($my as $key => $value)
				{
					$key=trim($key);
					$tmpLine=str_replace("<#$key#>","$value",$tmpLine);
				}
				$tmpLine = sql_parse_each($tmpLine);
				$sInBlock.=$tmpLine;
			}

			// Template wieder zusammensetzen
			$sTemplate=replaceblock_clean($sTemplate,"data",$sInBlock);


		}
		return $sTemplate;
	}
}

$sTemplate = sql_parse_each($sTemplate);

// Template ausgeben
echo $sTemplate;

?>
