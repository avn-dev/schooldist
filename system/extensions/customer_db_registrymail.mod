<?


#die("Modul Registry-Mail offline!!");
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);

$to=$_SESSION['customer_db_email'];


// Laden der korrekten DB-Feldnamen:
$query="SELECT * FROM customer_db_definition WHERE active=1 and db_nr=1";
$result=db_query($query);


while($my=get_data($result))
{
    if($my['name']=="email") $field_names[$my['name']]="email";
    if(intval($my['field_nr'])==0) continue;
    $field_names[$my['name']]="save_".trim($my['field_nr']); 
}

#echo "<br>Body orig.:<br>".$config->body."<br>";

$body='';
$body_array=explode("<#",$config->body);
if(is_array($body_array))
{
    foreach($body_array as $key => $value)
    {
        
        $sub_array=explode("#>",$value);
        
#        echo "<br>val : $value | s_arr0 : ".$sub_array[0];
                
        
        if(strpos($value, ":if:")===0) // Einfache if Abragen einbauen!
        {
            $verzweigung=explode(":",$sub_array[0]);
            
#            var_dump($verzweigung);
            
            if(count($verzweigung)!=5) 
            {
                $body.=" ##ERROR## ";
            }
            else
            {
                if($verzweigung[1]!=="if") continue;
                $wert_1=$_SESSION["customer_db_".$field_names[$verzweigung[2]]];
                $wert_2=$verzweigung[4];
                
                
                switch ($verzweigung[3])
                {
                    case '==':  
                        if($wert_1==$wert_2) $body.=$sub_array[1];
                        else $FLAG_if=TRUE;
                        break;
                    case '<':   
                        if($wert_1<$wert_2) $body.=$sub_array[1];
                        else $FLAG_if=TRUE;
                        break;
                    case '>':   
                        if($wert_1>$wert_2) $body.=$sub_array[1];
                        else $FLAG_if=TRUE;
                        break;
                    default:
                        $FLAG_if=TRUE;
                	break;
                };
                            
            
            }
            
            
        }
        elseif(strpos($value, "/if")===0)
        {
            $body.=$sub_array[1];
            $FLAG_if=FALSE;
        }
        else
        {
            if($FLAG_if!=TRUE)
            {
                if(count($sub_array)==2)
                {
                    $body.=$_SESSION["customer_db_".$field_names[$sub_array[0]]];
                    $body.=$sub_array[1];
                }
                else
                {
                    $body.=$value;        
                }
            }
        }
    }
}
else
{
    $body=$config->body;
}

if($to!='') 
{
    wdmail($to, $config->subject, $body, "FROM:".$config->from."\r\nReply-To:".$config->from."\r\n");
}


#echo "<br><br>Mail an: $to";
#echo "<br><br>Mail von: FROM:".$config->from."\r\nReply-To:".$config->from."\r\n";
#echo "<br><br>Mail subj: ".$config->subject;
#echo "<br><br>Mail text: $body";

