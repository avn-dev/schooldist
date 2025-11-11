<?php
/*
 * Created on 16.10.2006
 * Eingabe der gewünschten Zahlungsoptionen
 *
 * HINWEIS: Diese Version hat die Optionen
 * Überweisung, Lastschrift
 * Die Option Kreditkarte ist hier entfernt worden!
 */



// Nimmt die Zahlungsdaten auf - Erweiterbar um Funktion zur Prüfung von Kreditkarten-Daten
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);

?>

<form name = "customer_db_data_form_1" enctype="multipart/form-data" method="psot" action=''>
<input type="hidden" name="customer_db_fields[]" value="<?=$config->Zahlung_primaer?>">

<table>
<colgroup>

<col width=20%>
<col width=75%>
</colgroup>



    <TR>
        <TD colSpan=2>
            <STRONG>
                <input type="radio" id="cdb_field_1_8" <?if($_SESSION['customer_db_'.$config->Zahlung_primaer]==2 OR $_SESSION['customer_db_'.$config->Zahlung_primaer]!=3){echo "CHECKED";}?> name="customer_db_<?=$config->Zahlung_primaer?>" value="2" class="" OnFocus='disable_others(2);'>
                Zahlung per Lastschrift</STRONG>
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Kontoinhaber:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_9" name="customer_db_<?=$config->Kon_Inhaber?>" value="<?=$_SESSION['customer_db_'.$config->Kon_Inhaber]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->Kon_Inhaber?>">
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Kontonummer:&nbsp;
        </TD>
        <TD>
            <input type=text id="cdb_field_1_10" name="customer_db_<?=$config->Kon_Nummer?>" value="<?=$_SESSION['customer_db_'.$config->Kon_Nummer]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->Kon_Nummer?>">
        </TD>
    </TR>
    <TR>
        <TD style="WIDTH: 169px">
            Bankleitzahl:
        </TD>
        <TD>
            <input type=text id="cdb_field_1_11" name="customer_db_<?=$config->BLZ?>" value="<?=$_SESSION['customer_db_'.$config->BLZ]?>" OnFocus = ' if ( this.value == "" ) { this.value = ""; }; ' class="txt">
            <input type="hidden" name="customer_db_fields[]" value="<?=$config->BLZ?>">
        </TD>
    </TR>

    <TR>
        <td>&nbsp;</td>
    </TR>

    <TR>
        <TD colSpan=2>
            <STRONG>
                <input type="radio" id="cdb_field_1_12" <?if($_SESSION['customer_db_'.$config->Zahlung_primaer]==3){echo "CHECKED";}?> name="customer_db_<?=$config->Zahlung_primaer?>" value="3" class="" OnFocus='disable_others(3);'>
                Zahlung per �berweisung</STRONG>
        </TD>
    </TR>
    <TR>
        <td nowrap>
        Empf�nger:
        </td>
        <td>
        <?=$config->transfer_recipient?>
        </td>
    </TR>
    <TR>
        <td nowrap>
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
        <td nowrap>
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


if(document.getElementById('cdb_field_1_8').checked==true)
{
    if(document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffffff';}

    if(document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.value == '' || document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.value == ''){document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffccaa';required_missing=1;}
    else {document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffffff';}


}


    if( required_missing > 0 ) {
        // setze alle fehlenden auf neuen Style!
        alert("Die n�tigen Pflichtfelder wurden nicht vollst�ndig ausgef�llt! Bitte f�llen Sie diese nun aus! ");
        return 0;
	} else if( password_check > 0 ) {
        alert("Die Passw�rter stimmen nicht �berein. Bitte kontrollieren Sie Ihre Angaben! ");
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

        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=true;
        document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=true;

        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='d4d0c8';
        document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='d4d0c8';
    }

    else
    {
        if(selection==2)
        {
        //d4d0c8

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=false;
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=false;

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='ffffff';
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='ffffff';
        }
        else
        {
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.disabled=true;
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.disabled=true;

            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Inhaber?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->Kon_Nummer?>.style.backgroundColor='d4d0c8';
            document.customer_db_data_form_1.customer_db_<?=$config->BLZ?>.style.backgroundColor='d4d0c8';

        }
    }
}



    if(document.getElementById('cdb_field_1_8').checked==true)
    {
        disable_others(2);
    }
    else
    {
        disable_others(3);
    }



</script>



</table>


</form>
