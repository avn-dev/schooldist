<?php
/*
 * Created on 16.10.2006
 * Eingabe der gewünschten Zahlungsoptionen
 * HINWEIS: Diese Version hat die Optionen
 * Kreditkarte, Überweisung, Lastschrift
 */



// Nimmt die Zahlungsdaten auf - Erweiterbar um Funktion zur Prüfung von Kreditkarten-Daten
$config = new config_class($page_id, $element_data["number"]);
global $SESSION;


?>

<form name = "customer_db_data_form_1" enctype="multipart/form-data" method="POST" action=''>


<table>
<colgroup>

<col width=20%>
<col width=75%>
</colgroup>


<!--
    <TR>
        <TD colSpan=3>
            <STRONG>
                <input type="radio" id="cdb_field_1_1" <?if($SESSION['customer_db_'.$config->Zahlung_primaer]!=2){echo "CHECKED";}?> name="customer_db_<?=$config->Zahlung_primaer?>" value="1" class="" OnFocus='disable_others(1);'>
                <input type="hidden" name="customer_db_fields[]" value="<?=$config->Zahlung_primaer?>">
                Zahlung per Kreditkarte</STRONG>
        </TD>
    </TR>



    <TR>
        <TD style="WIDTH: 163px">
            Kreditkarte auswählen:
        </TD>
        <TD>
            <select id="cdb_field_1_2" name="customer_db_<?=$config->K_Type?>"  class="txt">

<?
$query="SELECT display, value FROM customer_db_definition d, customer_db_values v WHERE d.id=v.definition_id AND d.field_nr='".$config->K_Type."' AND db_nr=".intval($config->idDatabase);
echo "<br>query:".$query."<br>";
$result=db_query($query);
while($my=get_data($result))
{
    if($my['value']==$SESSION['customer_db_'.$config->K_Type])
    $selector="SELECTED";
    else
    $selector='';

    echo "<option $selector value='".$my['value']."' >
                ".$my['display']."
                </option>";
}

?>

            </select>
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_Type?>">
        </TD>
        <TD>
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 163px">
            Kreditkartennummer:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_3" name="customer_db_<?=$config->K_Nummer?>" value="<?=$SESSION['customer_db_'.$config->K_Nummer]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_Nummer?>">
        </TD>
        <TD>
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 163px">
            Gültig bis:
        </TD>
        <TD>
            <select id="cdb_field_1_4" name="customer_db_<?=$config->K_gueltig_tag?>"  class="txt">





<?
$query="SELECT display, value FROM customer_db_definition d, customer_db_values v WHERE d.id=v.definition_id AND d.field_nr='".$config->K_gueltig_tag."' AND db_nr=".intval($config->idDatabase);
#echo "<br>".$query."<br>";
$result=db_query($query);
while($my=get_data($result))
{
    if($my['value']==$SESSION['customer_db_'.$config->K_gueltig_tag])
    $selector="SELECTED";
    else
    $selector='';
    echo "<option $selector value='".$my['value']."' >
                ".$my['display']."
                </option>";
}

?>
            </select>
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_gueltig_tag?>">
            &nbsp;
            <select id="cdb_field_1_5" name="customer_db_<?=$config->K_gueltig_monat?>"  class="txt">
<?
$query="SELECT display, value FROM customer_db_definition d, customer_db_values v WHERE d.id=v.definition_id AND d.field_nr='".$config->K_gueltig_monat."' AND db_nr=".intval($config->idDatabase);
#echo "<br>".$query."<br>";
$result=db_query($query);
while($my=get_data($result))
{
    if($my['value']==$SESSION['customer_db_'.$config->K_gueltig_monat])
    $selector="SELECTED";
    else
    $selector='';
    echo "<option $selector value='".$my['value']."' >
                ".$my['display']."
                </option>";
}

?>
            </select>
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_gueltig_monat?>">
            &nbsp;
            <select id="cdb_field_1_6" name="customer_db_<?=$config->K_gueltig_jahr?>"  class="txt">
<?
$query="SELECT display, value FROM customer_db_definition d, customer_db_values v WHERE d.id=v.definition_id AND d.field_nr='".$config->K_gueltig_jahr."' AND db_nr=".intval($config->idDatabase);
#echo "<br>".$query."<br>";
$result=db_query($query);
while($my=get_data($result))
{
    if($my['value']==$SESSION['customer_db_'.$config->K_gueltig_jahr])
    $selector="SELECTED";
    else
    $selector='';
    echo "<option $selector value='".$my['value']."' >
                ".$my['display']."
                </option>";
}

?>

            </select>
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_gueltig_jahr?>">
        </TD>
        <TD>
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 163px">
            Name des Karteninhabers:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_7" name="customer_db_<?=$config->K_Inhaber?>" value="<?=$SESSION['customer_db_'.$config->K_Inhaber]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->K_Inhaber?>">
        </TD>
        <TD>
        </TD>
    </TR>
    <TR>
        <td>&nbsp;</td>
    </TR>


    -->

    <TR>
    </TR></STRONG>
    <TR>
        <TD colSpan=2>
            <STRONG>
                <input type="radio" id="cdb_field_1_8" <?if($SESSION['customer_db_'.$config->Zahlung_primaer]==2 OR $SESSION['customer_db_'.$config->Zahlung_primaer]!=3){echo "CHECKED";}?> name="customer_db_<?=$config->Zahlung_primaer?>" value="2" class="" OnFocus='disable_others(2);'>
                Zahlung per Lastschrift</STRONG>
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Kontoinhaber:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_9" name="customer_db_<?=$config->Kon_Inhaber?>" value="<?=$SESSION['customer_db_'.$config->Kon_Inhaber]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->Kon_Inhaber?>">
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Kontonummer:&nbsp;
        </TD>
        <TD>
            <input type=text id="cdb_field_1_10" name="customer_db_<?=$config->Kon_Nummer?>" value="<?=$SESSION['customer_db_'.$config->Kon_Nummer]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->Kon_Nummer?>">
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Bankleitzahl:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_11" name="customer_db_<?=$config->BLZ?>" value="<?=$SESSION['customer_db_'.$config->BLZ]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->BLZ?>">
        </TD>
    </TR>

    <TR>
        <td>&nbsp;</td>
    </TR>

    <TR>
        <TD colSpan=2>
            <STRONG>
                <input type="radio" id="cdb_field_1_12" <?if($SESSION['customer_db_'.$config->Zahlung_primaer]==3){echo "CHECKED";}?> name="customer_db_<?=$config->Zahlung_primaer?>" value="3" class="" OnFocus='disable_others(3);'>
                Zahlung per Überweisung</STRONG>
        </TD>
    </TR>
    <TR>
        <td>
        Empfänger:
        </td>
        <td>
        <?=$config->transfer_recipient?>
        </td>
    </TR>
    <TR>
        <td>
        Kreditinstitut:
        </td>

        <td>
        <?=$config->transfer_institut?>
        </td>
    </TR>
    <TR>
        <td nowrap>
        Kto.-Nr.:
        </td>
        <td>
        <?=$config->transfer_accountnumber?>
        </td>
    </TR>
    <TR>
        <td>
        BLZ:
        </td>
        <td>
        <?=$config->transfer_banknumber?>
        </td>

    </TR>

    <TR>
        <TD nowrap>
        Verwendungszweck:
        </td>
        <td>
        <?=$config->transfer_purpose?>
        </TD>
    </TR>





    <TR>
        <TD>
            &nbsp;
        </TD>
    </TR>
    <TR>
    	<td colspan=3>
    		<?=$config->comment?>
		</td>
    </TR>

    <TR>
        <TD>
            &nbsp;
        </TD>
    </TR>
    <TR>

        <?
            if($config->back_button)
            {
            ?>
                <TD><input class="btn" type='button' value="<?=$config->back_button?>" name="" OnCLick='document.customer_db_data_form_1.action="<?=$config->back_url?>";document.customer_db_data_form_1.submit();'></TD>
            <?
            }

        ?>

        <TD>
            <input class="btn" type='button' value="<?if($config->destination_button) echo $config->destination_button; else echo "Weiter &raquo;";?>" name="" OnCLick='if(form_check_script_z()){document.customer_db_data_form_1.action="<?=$config->destination_url?>";document.customer_db_data_form_1.submit();}'>
        </TD>
    </TR>



<input type='hidden' name = 'table_number' value = '1'></form>
<script>

function form_check_script_z()
{
    required_missing	= 0;
	password_check		= 0;

if(document.getElementById('cdb_field_1_1').checked==true)
{
    if(document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='ffffff';}

    document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='d4d0c8';



}

if(document.getElementById('cdb_field_1_8').checked==true)
{
    if(document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffffff';}

    document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='d4d0c8';
    document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='d4d0c8';
}


    if( required_missing > 0 ) {
        // setze alle fehlenden auf neuen Style!
        alert("Die nötigen Pflichtfelder wurden nicht vollständig ausgefüllt! Bitte füllen Sie diese nun aus! ");
        return 0;
	} else if( password_check > 0 ) {
        alert("Die Passwörter stimmen nicht überein. Bitte kontrollieren Sie Ihre Angaben! ");
        return 0;
    } else {
        return 1;
    }


}
</script>



<script>
function disable_others(selection)
{
    if(selection==3 || selection==4)
    {
    //d4d0c8

        document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.disabled=true;

        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=true;

        document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='d4d0c8';

        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='d4d0c8';
    }

    else
    {
        if(selection==2)
        {
        //d4d0c8

            document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.disabled=true;

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=false;

            document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='d4d0c8';

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffffff';
        }
        else
        {
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=true;

            document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.disabled=false;

            document.customer_db_data_form_1.customer_db_<?=$config->K_Type?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->K_Nummer?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_tag?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_monat?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->K_gueltig_jahr?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->K_Inhaber?>.style.backgroundColor='ffffff';

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='d4d0c8';

        }
    }
}


if(document.getElementById('cdb_field_1_1').checked==true)
{
    disable_others(1);
}
else
{
    if(document.getElementById('cdb_field_1_8').checked==true)
    {
        disable_others(2);
    }
    else
    {
        disable_others(3);
    }
}



</script>



</table>


</form>
