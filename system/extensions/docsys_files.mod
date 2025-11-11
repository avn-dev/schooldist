<?

if(basename($_SERVER["SCRIPT_FILENAME"]) != 'docsys_files.mod')
{
  #echo "<iframe id='docsys_files_frame' name='docsys_files_frame' scrolling='no' width='100%' style='height:100%' src='/system/extensions/docsys_files.mod' frameborder='0'></iframe>";
  echo "<iframe id='docsys_files_frame' name='docsys_files_frame' scrolling='auto' width='100%' style='height:100%' src='docsys_home.html' frameborder='0'></iframe>";
}
else
{


include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");
include(\Util::getDocumentRoot()."system/includes/variables.inc.php");
$session_data['public'] = 1;
include(\Util::getDocumentRoot()."system/includes/access.inc.php");


ob_start();


?>
<link rel="stylesheet" href="/css/styles.css">

<style type="text/css">
<!--
  /*
  body { background-color:#FFFFFF; font-family : Verdana,Helvetica,Arial,sans-serif; font-size : 11px;
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

? >
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f6f6f6; height:100%;">
  <colgroup>
    <col>
    <col width=10 style="width:10px">
  </colgroup>
  <tr>
    <td colspan=2 width="100%" height="10" bgcolor="#f6f6f6" valign="top"><img src="/media/spacer.gif" width="1" height="6"></td>
  </tr>
  <tr>
    <td width="100%" bgcolor="#FFFFFF" style="border:1px solid #dddddd;" valign="top">
< ?


? >
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="table-layout:fixed; height:100%;">
  <tr style="height:100%">
    <td align="left" valign="top" width="100%">
      <div style="height:100%;width:100%;overflow:scroll">
< ?
//*/


	$target = "parent.content.page.location";
	$extended = ($system_data['sitemap_start_open']?"1":"0");

  if($_REQUEST['todo'] == "newFile")
  {
	  include(\Util::getDocumentRoot()."system/extensions/docsys/docsys_attributes.inc.php");
  }
  else
  {
	  include(\Util::getDocumentRoot()."system/extensions/docsys/docsys_files.inc.php");
  }

/*
? >
      </div>
    </td>
  </tr>
</table>
< ?
//*/



ob_end_flush();


}

?>
