<?
/**
 * Zeigt Inhalte einer anderen Seite an per fopen oder per Iframe (konfigurierbar)
 * @author: Bastian H�bner
 * @date: 29.06.06
 */


#include(\Util::getDocumentRoot()."system/includes/main.inc.php");
////////////////////////////////////////////////////////////////
// Lade die Konfigurationseinstellungen
////////////////////////////////////////////////////////////////
#$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);
$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if($config->intern_url)
	$destination_url=$config->intern_url;

elseif($config->extern_url)
	$destination_url=$config->extern_url;


////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

// Ersetze m�gliche REQUEST Parameter im SQL
$replaced_dest='';

$parts=explode("<#",$destination_url);

$replaced_dest=$parts[0];

if(is_array($parts))
{
	foreach($parts as $key => $value)
	{
		if($key==0) continue;
		$pieces=explode("#>",$value);
		$default=explode(":",$pieces[0]);

		if(!$_REQUEST[$default[0]])
		{
			$replaced_dest.=$default[1];
		}
		else
		{
			$replaced_dest.=$_REQUEST[$default[0]];
		}

		$replaced_dest.=$pieces[1];

	}
}

if ($replaced_dest)
{
	$destination_url = $replaced_dest;
}



////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////




////
//_REQUEST mitgeben:
////
if(strpos($destination_url, '?')===false) $sParam = '?';
else $sParam = '&';
foreach($_REQUEST as $k=>$v)
	$sParam .= urlencode($k) . '=' . urlencode($v) . '&';
$destination_url = $destination_url.$sParam;








////////////////////////////////////////////////////////////////
// Verzweige aufgrund der Einstellungen
////////////////////////////////////////////////////////////////
if(2 == $config->page_type)
{
	// zeige innerhalb eines iframe an
	echo "<iframe src='".$destination_url."' width='".$config->width."' height='".$config->height."'></iframe>";

}
elseif(1 == $config->page_type)
{
	$handle=fopen($destination_url,'r');
	$sSourceCode='';

	while (!feof($handle) && $handle)
	{
		$sSourceCode.=fread($handle,1024);
	}

	fclose($handle);

	//$sPattern="/(.*?)<body(.*?)>(.*?)<\\/body>(.*?)/i";
	//$sReplace="\\3";
	$sPattern="/^(.*)<body([^>]*)>(<a name=\"top\"><\\/a>|<p class=\"invisible_anchor\"><a name=\"top\"><\\/a><\\/p>)(.*)?<!-- webDynamics System Footer -->(.*)/is";
	$sReplace="\\4";

	$sBody=preg_replace($sPattern,$sReplace,$sSourceCode);

	echo $sBody;
}





////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

?>