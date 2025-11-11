<?php
/*
 * Created on 20.11.2006
 * Zählt die Aufrufe der Bilder der Galerie
 * Erwartet: 
 * $_REQUEST['url']
 * $_REQUEST['id']
 * $_REQUEST['doCount'] !!!NEU!!! nur wenn doCount==1 wird gezählt, um doppelzählung zu vermeiden
 *                                dabei wird !keine! Datei ausgegeben
 * Ausgabe:  
 * die Datei
 */


include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/parser.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
/**/



$id=intval($_REQUEST['id']);
if($id>0)
{
	if($_REQUEST['doCount'])
    {
        $sUpdate="UPDATE gallery2_items SET views=views+1 WHERE id='".$id."'";
        db_query($sUpdate);
	}
    else
    {
    	$sSelect="SELECT * FROM `gallery2_files` WHERE item_id='$id' AND size_id='".intval($_REQUEST['size'])."'";
    	$aFile=get_data(db_query($sSelect));
    	$destination=$aFile['path'].$aFile['filename'];

    	$sFile = \Util::getDocumentRoot().$destination;
    	if(is_file($sFile))
    	{
    		if($_REQUEST['doDownload'])
    		{
				header("Content-type: application/force-download");
				header("Content-Disposition: attachment; filename=".basename($sFile));
				header("Content-length:".(string)(filesize($sFile)));
    		}
    		readfile($sFile);
    	}
	}
#	else
#	{
#		echo "No File: ".\Util::getDocumentRoot().$destination;
#	}
}

?>
