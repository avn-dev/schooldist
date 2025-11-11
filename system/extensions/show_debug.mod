<?
// Dieses Modul spuckt POST und GET in ansehlicher Form aus

echo "<table border=1 cellspacing=4 cellpadding=4>";
echo "<tr><th colspan=2><b>POST</b></th></tr>";


if(is_array($_POST))
{
    foreach($_POST as $key => $val)
    {
        echo "<tr><td>$key</td><td>$val&nbsp;</td></tr>";
    }
}
echo "<tr><td colspan=2>&nbsp;</td></tr>";



echo "<tr><th colspan=2><b>GET</b></th></tr>";
if(is_array($_GET))
{
    foreach($_GET as $key => $val)
    {
        echo "<tr><td>$key</td><td>$val&nbsp;</td></tr>";
    }
}



echo "<tr><th colspan=2><b>GLOBALS</b></th></tr>";

if(is_array($GLOBALS))
{
    foreach($GLOBALS as $key => $val)
    {
        echo "<tr><td>$key</td><td>$val&nbsp;</td></tr>";
    }
}



echo "</table>";

?>
