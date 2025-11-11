<?
function try_login($pw,$email)
{
    global $parent_config;
    $result=db_query("SELECT * FROM ".$parent_config->table_name." WHERE email=$email AND password=$pw AND active=1");
    #$my=get_data($result);
    #var_dump($my);
    if(count_rows($result)==1)
    {
        #global $_VARS;
        #$_VARS['loginmodul']=1;
        
		#$_VARS['customer_login_1'] = $email;
		#$_VARS['customer_login_3'] = $email;
        
        #echo "<form method=POST>";
        echo "<input type=hidden name=\"loginmodul\" value=1>";
        echo "<input type=hidden name=\"customer_login_1\" value='$email'>";
        echo "<input type=hidden name=\"customer_login_3\" value='$pw'>";
        #echo "</form>";
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}


/*
echo "SESSION:<br>";
global $SESSION;
var_dump($SESSION);
echo "<br><br>POST:<br>";
var_dump($_POST);
echo "<br><br>VARS:<br>";
global $_VARS;
var_dump($_VARS);
*/


if($SESSION['customer_db_password']!='' AND $SESSION['customer_db_email']!='')
{
    Echo "<br><br>könnte einloggen ..... ";
    if (try_login($SESSION['customer_db_password'],$SESSION['customer_db_email']))
    {
        #echo "<br>gechäckt!";
    }
    else
    {
        #echo "<br>nix gechäckt!";
    }
    
    
    
}
else
{
    #Echo "<br><br>kann NICHT einloggen!";
    
}



 
?>
