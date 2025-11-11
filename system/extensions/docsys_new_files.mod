<style type="text/css">
<!--
/*  body { background-color:#FFFFFF; font-family : Verdana,Helvetica,Arial,sans-serif; font-size : 11px;
  margin: 0px; scrollbar-base-color:#FFFFFF; scrollbar-3dlight-color:#999999; scrollbar-arrow-color:#999999;
  scrollbar-darkshadow-color:#999999; scrollbar-face-color:#dddddd; scrollbar-highlight-color:#FFFFFF;
  scrollbar-track-color:#eeeeee; scrollbar-shadow-color:#FFFFFF; }
*/
  tr { font-size:11px; }
  tr.title { background-color:#eeeeee; color:#29527A; font-weight:bold; font-size:12px; }
  tr.head { background-color:#cecece; font-weight:bold; font-size:11px; }
  th { font-weight:bold; font-size:11px; }
  .table { border-top:solid 1px #aaa999; border-left:solid 1px #aaa999; }
  .table th { font-size:11px; background-color:#eeeeee; color:#29527A; font-weight:bold; vertical-align:middle;
  text-align:left; border-bottom: solid 1px #aaa999; border-right: solid 1px #aaa999; }
  th a:link { color:#29527A; text-decoration:underline; }
  th a:hover { color:#29527A; text-decoration:underline; }
  th a:visited { color:#29527A; text-decoration:underline; }
  th a:active { color:#29527A; text-decoration:underline; }
  .table td { font-size:11px; vertical-align:middle; border-bottom: solid 1px #aaa999;
    border-right:solid 1px #aaa999; }
  span { }
  a:link { text-decoration:none; color:#000000; }
  a:visited { text-decoration:none; color:#000000 }
  a:hover { text-decoration:none; color:#000000 }
  a:active { text-decoration:none; color:#000000 }
  a:focus { text-decoration:none; color:#000000 }
-->
</style>
<?
/*
include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
$session_data['public'] = 1;
include(\Util::getDocumentRoot()."system/includes/access.inc.php");
include \Util::getDocumentRoot()."system/extensions/docsys/docsys_include.inc.php";
*/




if(!function_exists("getRechteString"))
{
    include \Util::getDocumentRoot()."system/extensions/docsys/docsys_include.inc.php";
}
#if(!function_exists("parse_filetype"))
#{
#    include \Util::getDocumentRoot()."system/extensions/docsys/docsys_include.inc.php";
#}


global $sIconVerzeichnis;
$sIconVerzeichnis = "/media/Dokunentenmanagement/Icons/";
// Anzeigen der neuen Dateien
// Lade die Icons
function show_new_icon($name)
{
    global $sIconVerzeichnis;


    $sEndung = strtolower(substr(strrchr($name, "."), 1));
    $sDatei = $sIconVerzeichnis.$sEndung;
    $sDatei2 = \Util::getDocumentRoot().$sDatei.".png";#.($aFile['link']['element'] == 'link'?".link":"")



    if(file_exists($sDatei2))
    {
      $icon = "<IMG height='30' src='".$sDatei;
      $icon .= ".png' border='0'>";
    }
    else
    {
      $icon = "<IMG height='30' src='".$sIconVerzeichnis."unbekannt.png' border='0'>";
    }

    return $icon;
}







function show_new_files()
{
    global $sIconVerzeichnis;

    // Lade alle user in array
    $user_query="SELECT * FROM customer_db_1 WHERE active=1";

    $user_result=db_query($user_query);
    while($my_user=get_data($user_result))
    {
        $aUser[$my_user['id']]=$my_user['nickname'];
    }


    $sDestPath = \Util::getDocumentRoot()."storage/docsys_files/";
    global $user_data;


    $query="SELECT
            *, UNIX_TIMESTAMP(date) AS uni_date

            FROM docsys_tree

            WHERE
                active='1'
            AND current='1'
            AND element='file'
            ".getRechteString($user_data)."

            ORDER BY date DESC";

    $result=db_query($query);


    $counter=0;
    $FLAG_mehr=FALSE;

    #style="position:absolute;margin-top:-6px;margin-left:12px;"

    #echo "<tr bgcolor=#e8ecef style='position:absolute;margin-top:-7px;' width=100%>";


    #echo "<table class=table cellpadding=0 cellspacing=0>";
    echo "<tr bgcolor=#e8ecef>";# style='position:absolute;margin-top:-7px;'
    echo "<th>&nbsp;</th>";
    echo "<th colspan=2 align=left><img src='/media/spacer.gif' width=3 height=1 border=0>Name</th>";
    echo "<th align=left>Autor</th>";
    echo "<th align=right>Gr��e</th>";
    echo "<th align=left>Typ</th>";
    echo "<th align=left><nobr>Ge�ndert am</nobr></th>";

    echo "</tr>";

    #echo "</div>";

    #<img src='/media/spacer.gif' width=1 height=1>

    echo "<tr><td>&nbsp;</td></tr>";


    while ($my=get_data($result))
    {
        #document.location.
        $link="href=\""."/system/extensions/docsys_files.mod?current=".$my['path']."&amp;todo=newFile&amp;id=".$my['id']."&amp;element=file\"";

        echo "<tr>";
        echo "<td>&nbsp;</td>";

        echo "<td><a $link>".show_new_icon($my['file'])."</a></td>";

        #echo "<td>&nbsp;</td>";

        echo "<td align=left><a $link>".$my['file']."</a></td>";
        echo "<td><nobr>".$aUser[$my['owner']]."</nobr></td>";
        echo "<td align=right><nobr>".filesize($sDestPath.$my['temp_name'])." kB</nobr></td>";
        echo "<td><nobr>".parse_filetype($my['file'])."</nobr></td>";
        echo "<td><nobr>".date("d.m.Y",$my['uni_date'])."</nobr></td>";

        echo "</tr>";


        $counter++;

        if($counter>5)
        {
            $FLAG_mehr=TRUE;
            break;
        }

    }

    if($FLAG_mehr)
    {
#        echo "<tr OnClick='
#        document.getElementById(\"SearchFormTop\").submit();
#        '><td colspan=2>weitere >></td></tr>";


        $sDatum=date("d.m.Y", (time()-604800));

        echo "<tr>";

        echo "<td>&nbsp;</td>";
        echo "<td colspan=2>
        <a href='/system/extensions/docsys_tree.mod?"
        ."current=&amp;action=search&amp;target=0&amp;Suchart=enthaelt&amp;Suchtext=&amp;selectAutor=0&amp;selectKategorie=0&amp;selectGruppe=0&amp;DatumAb=$sDatum&amp;DatumBis=0' target='docsys_tree_frame'>"
        ."weitere <img src=\"".$sIconVerzeichnis."doppelpfeil.png\" border=0></a></td></tr>";
    }

    #echo "</table>";
}






box_2_start("Neueste Dateien",100,array("20","40"),25);
show_new_files();
box_2_end();

?>