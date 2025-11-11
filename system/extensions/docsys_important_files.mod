<style type="text/css">
<!--
  body { background-color:#FFFFFF; font-family : Verdana,Helvetica,Arial,sans-serif; font-size : 11px;
  margin: 0px; scrollbar-base-color:#FFFFFF; scrollbar-3dlight-color:#999999; scrollbar-arrow-color:#999999;
  scrollbar-darkshadow-color:#999999; scrollbar-face-color:#dddddd; scrollbar-highlight-color:#FFFFFF;
  scrollbar-track-color:#eeeeee; scrollbar-shadow-color:#FFFFFF; }
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







// Anzeigen der als wichtig markierten Dateien
global $sIconVerzeichnis;
$sIconVerzeichnis = "/media/Dokunentenmanagement/Icons/";

// Lade die Icons
function show_important_icon($name)
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



function show_important_files()
{
    global $user_data;
    global $sIconVerzeichnis;

    #$user_data['id']=2;

    $query="SELECT *
    FROM docsys_tree

    WHERE
        important='1'
    AND current='1'
    AND active='1'
    AND element='file'
    ".getRechteString($user_data)."

    ORDER BY date DESC";

    #echo $query;
    #

    $result=db_query($query);



    #box_2_start("Wichtige Dateien auf einen Blick");

    #echo "<table class=table width=\"100%\" border=\"0\" cellpadding=4 cellspacing=4>";

    #echo "<tr><td colspan=2 class=ueberschrift>";
    #echo "<DIV style=\"position:absolute;margin-top:-6px;margin-left:12px;\">";
    #echo "<nobr>Wichtige Dateien auf einen Blick</nobr>";
    #echo "</div>";

    #echo "<br></td></tr>";

    #echo "<tr><td>&nbsp;</td></tr>";

    $counter=0;
    $FLAG_mehr=false;

    while ($my=get_data($result))
    {
        #$link="document.location.href='../docsys_files.mod?current=&todo=newFile&id=".$my['id']."&element=file'";
        $link="href=\""."/system/extensions/docsys_files.mod?current=".$my['path']."&amp;todo=newFile&amp;id=".$my['id']."&amp;element=file\"";


        echo "<tr>";#OnMouseOver='style=\"cursor:pointer;\"'
        echo "<td>&nbsp;</td>";
        echo "<td><a $link>".show_important_icon($my['file'])."</a></td>";

        echo "<td align=left><a $link>".$my['file']."</a></td>";
        #echo "<td><a href='$link'>O</a></td>";
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
        #<a href='/system/extensions/docsys_tree.mod?current=&action=search&target=link&bWichtig=on' target='docsys_tree_frame'>weitere >></a>
        echo "<tr>";
        echo "<td>&nbsp;</td>";

        echo "<td colspan=2>

                <a href='/system/extensions/docsys_tree.mod?
        current=&amp;action=search&amp;target=0&amp;Suchart=enthaelt&amp;Suchtext=&amp;selectAutor=0&amp;selectKategorie=0&amp;selectGruppe=0&amp;bWichtig=on&amp;DatumAb=0&amp;DatumBis=0' target='docsys_tree_frame'>
        weitere <img src=\"".$sIconVerzeichnis."doppelpfeil.png\" border=0></a>

        </td></tr>";

    }

    #echo "</table>";




}



box_2_start("<nobr>Wichtige Dateien auf einen Blick</nobr>",100,array("20","40"));
show_important_files();
box_2_end();
?>

<!--

<TABLE class="" cellSpacing=0 cellPadding=0 background="" border=0 maxcols="4">
  <colgroup>
    <col width=15>
    <col width=*>
    <col width=15>
  <colgroup>
  <TBODY>
    <TR>
      <TD width=15><IMG height=15 alt=box2_lo.png src="/media/Dokunentenmanagement/box2/box2_lo.png" width=15 align=absMiddle border=0></TD>
      <TD width="99%" style="background-image:url(/media/Dokunentenmanagement/box2/box2_o.png)"><IMG height=15 alt=box2_o.png src="/media/Dokunentenmanagement/box2/box2_o.png" width=15 align=absMiddle border=0></TD>
      <TD width=15><IMG height=15 alt=box2_ro.png src="/media/Dokunentenmanagement/box2/box2_ro.png" width=15 align=absMiddle border=0></TD>
    </TR>
    <TR>
      <TD colSpan=3>
      <TABLE class="" style="WIDTH: 100%" cellSpacing=0 cellPadding=0 width="100%" background="" border=0 maxcols="2">
          <colgroup>
            <col width=2>
            <col width=*>
            <col width=2>
          <colgroup>
          <TBODY>
            <TR>
              <TD style="background-image:url(/media/Dokunentenmanagement/box2/box2_l.png)"><IMG height=2 alt=box2_l.png src="/media/Dokunentenmanagement/box2/box2_l.png"
 width=2 align=absMiddle border=0></TD>
              <TD style="background-image:url(/media/Dokunentenmanagement/box2/box2_m.png)" nowrap>
                < ?  show_important_files();?>
              </TD>
              <TD style="background-image:url(/media/Dokunentenmanagement/box2/box2_r.png)"><IMG height=2 alt=box2_r.png src="/media/Dokunentenmanagement/box2/box2_r.png"
 width=2 align=absMiddle border=0></TD>
            </TR>
          </TBODY>
        </TABLE></TD>
    </TR>
    <TR>
      <TD width=15><IMG height=15 alt=box2_lu.png src="/media/Dokunentenmanagement/box2/box2_lu.png" width=15 align=absMiddle border=0></TD>
      <TD style="background-image:url(/media/Dokunentenmanagement/box2/box2_u.png)"><IMG height=15 alt=box2_u.png src="/media/Dokunentenmanagement/box2/box2_u.png"
 width=15 align=absMiddle border=0></TD>
      <TD width=15><IMG height=15 alt=box2_ru.png src="/media/Dokunentenmanagement/box2/box2_ru.png" width=15 align=absMiddle border=0></TD>
    </TR>
  </TBODY>
</TABLE>
-->
