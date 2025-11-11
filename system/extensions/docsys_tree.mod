<?

if(basename($_SERVER["SCRIPT_FILENAME"]) != 'docsys_tree.mod')
{
  echo "<iframe id='docsys_tree_frame' name='docsys_tree_frame' width='100%' height='100%' src='/system/extensions/docsys_tree.mod?action=home' frameborder='0' scrolling='yes'></iframe>";
}
else
{


include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
$session_data['public'] = 1;
include(\Util::getDocumentRoot()."system/includes/access.inc.php");

include \Util::getDocumentRoot()."system/extensions/docsys/docsys_include.inc.php";

ob_start();


?>
<link rel="stylesheet" type="text/css" href="/css/styles.css">
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
  span { white-space : nowrap; }
  a:link { text-decoration:none; color:#000000; }
  a:visited { text-decoration:none; color:#000000 }
  a:hover { text-decoration:none; color:#000000 }
  a:active { text-decoration:none; color:#000000 }
  a:focus { text-decoration:none; color:#000000 }
-->
</style>
<?


/*? >
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f6f6f6; width=100%; height:100%;">
  <tr>
    <td width="9"><img src="/media/spacer.gif" width="9" height="10"></td>
    <td height="10" style="border-bottom:1px solid #dddddd;"><img src="/media/spacer.gif" width="10" height="10"></td>
  </tr>
  <tr>
    <td><img src="/media/spacer.gif" width="9" height="10"></td>
    <td width="100%" bgcolor="#FFFFFF" style="border-left:1px solid #dddddd;border-bottom:1px solid #dddddd;" valign="top">
< ? */

/*
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="table-layout:fixed; width:100%; height:100%;">
  <tr>
    <td valign="top">
<div style="height:100%;width:100%;overflow-x:scroll;overflow-y:scroll;">
*/
?>
<form id="SearchFormTop" name="SearchFormTop" action="/system/extensions/docsys_tree.mod">
<input type="hidden" name="action" id="action" value="search">
<input type="hidden" name="current" id="current" value="<?=$_REQUEST['current'];?>">

<table cellspacing=0 cellpadding=0 border=0 style="width:100%">
  <tr>
    <td valign="middle" style="background-image:url(<?=$sIconVerzeichnis;?>suche_back.png)" class="docsys_suche" nowrap>

<table cellspacing=0 cellpadding=0 border=0>
  <tr>
    <td valign="middle" class="docsys_suche" nowrap>
      Suche<img src="/media/spacer.gif" width=8 height=1><input type="text" style="width:130px;" class="docsys_input" name="Suchtext" id="Suchtext" value="<?=$_REQUEST['Suchtext'];?>">
    </td>
    <td valign="middle" nowrap>
      <img src="/media/spacer.gif" width=8 height=1><input type="image" src="<?=$sIconVerzeichnis;?>suche_los.png"><img src="/media/spacer.gif" width=6 height=1>
    </td>
  </tr>
</table></tr></td>

<tr><td><table cellpadding="0" cellspacing="6" border="0" width="100%"">
  <tr>
    <td>
<?


	$extended = ($system_data['sitemap_start_open']?"1":"0");

	include(\Util::getDocumentRoot()."system/extensions/docsys/docsys_tree.inc.php");


?>
    </td>
  </tr>

</table>
</tr></td>
</table>
</form>
<?

/*

</div>
    </td>
</tr>
</table>


? >
   &nbsp;
    </td>
  </tr>
  <tr>
   <td height="10" colspan="2"><img src="/media/spacer.gif" width="1" height="10"></td>
  </tr>
</table>
*/
?>

<script type="text/javascript">

// f�r das Tracking
var element=top.document.getElementById('inner_tracking');
if(element)
{
  element.innerHTML = '<?
    echo "Startverzeichnis" ;
    $aParts = explode('/',$_REQUEST['current']);
    foreach($aParts as $k=>$v)
    {
      if($v)
      {
        echo "<img src=\"".$sIconVerzeichnis."doppelpfeil.png\">";
        echo "$v";
      }
    }

  ;?>';
}
</script>



<?


ob_end_flush();


}

?>
