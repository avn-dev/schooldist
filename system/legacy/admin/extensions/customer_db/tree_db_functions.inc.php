<?
//////////////////////////////////////////////////////////////
// Funkionssammlung für die Baum-Datenbanken-Admin          //
// Erlaubt, Bäume zu erstellen, zu löschen und zu pflegen   //
//////////////////////////////////////////////////////////////


function tree_browser($db_name, $tree_table)
{
  // Der Tree-Browser erlaubt es, durch die
  // Struktur des Baumes zu browsen. Erlaubt es
  // Kategorien einzufügen, zu löschen und zu bewegen


 
}

function show_trees($db_name,$tree_table)
{
  #echo "show_trees($db_name,$tree_table)";
  // Zeigt die verschiedenen Bäume an
  $result=db_query($db_name,"SELECT * FROM $tree_table WHERE active=1 AND name='Wurzel' AND depth=0 GROUP BY tree_number");
  #var_dump($result);
  for ($i=0;$i<mySQL_num_rows($result);$i++)
  {
    $data=get_data($result);
    if ($i==0)
    {
      $pre_select="checked";
    }
 	  else
	  {
	     $pre_select="";
	  }
	//echo "<input type=radio name=\"selected_tree\" value=$data[tree_number] $pre_select>";
	echo "<tr><td width=\"60%\">Baum ".$data[tree_number]."</td>";

  ?>
    <td width="15%" align="center">
    <a href='javascript:document.formular1.submit()' onClick="document.formular1.form_action.value='edit_tree';document.formular1.selected_tree.value='<?=$data['tree_number']?>';"><img src='/admin/media/edit.png' width='20' height='20' border=0 alt="Bearbeiten"></a>&nbsp;
    <a href='javascript:document.formular1.submit()' onClick="if(window.confirm('Wollen Sie wirklich den Baum <?=$data[tree_number]?> löschen?')){document.formular1.form_action.value='delete_tree';document.formular1.selected_tree.value='<?=$data[tree_number]?>';}"><img src='/admin/media/delete.png' width='20' height='20' border=0 alt="Löschen"></a>
    </td>
  <?
  echo "</tr>";


  }
  return $result;
}



function select_category($db_name, $tree_table, $selected_tree, $linemarker, $groupmarker)
{
  // Select feld für die Kategorien
  echo "<select class=\"input\" style=\"width:465px;\" name=\"category_select\">";//<option value=\'\'>Wurzelverzeichnis";
  $res_gruppen[0] = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND tree_number='$selected_tree' AND parent_ID='0'");
  $level=0;

  while($level!=-1)
  {
   //echo"<br>$level#".mysql_error();
   $my_gruppen[$level] = get_data($res_gruppen[$level]);
   // wenn daten: zeile ausgeben, level erhöhen, und selecten
   if($my_gruppen[$level])
   {
     echo '                <option class = "input" value="'.$my_gruppen[$level][id].'">';
     for($i=0; $i<$level; $i++) echo '&nbsp;&nbsp;&nbsp;';
     if($level>1) echo "&nbsp;$groupmarker&nbsp;";
     if($level>2) echo "&nbsp;$linemarker&nbsp;";
     echo htmlspecialchars($my_gruppen[$level][name]);
     $level++;
     $res_gruppen[$level] = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND tree_number='$selected_tree' AND parent_ID='".($my_gruppen[$level-1][id])."'");
   }
   // wenn keine daten: level verringern
   else
   {
     $level--;
   }
  }

  echo "         </select>";
}



function new_select_category($db_name, $tree_table, $selected_tree, $selected_Kat, $frame_id,$field_name,$Form_ID, $cat_number)
{
    #global $Form_ID;
?>
<script>
    function make_my_frame(n,i,cat)
    {
        parent.document.customer_db_data_form_<?=$Form_ID?>.<?=$field_name?>.value=n;
        var j;
        //var k;
        j=i;
        j++;
        
        //alert ( "lösche alles oder so ");
        for( k = (j+1) ; k < 20 ; k++)
        {
            if(parent.document.getElementById(<?=$cat_number?>+"_"+k))
            {
                parent.document.getElementById(<?=$cat_number?>+"_"+k).src='';
                //alert ( "lösche "+k);
            }
        }
        


        parent.document.getElementById("<?=$cat_number?>_"+j).src="/admin/extensions/customer_db/category.ifr.php?db_name=<?=$db_name?>&tree_table=<?=$tree_table?>&selected_tree=<?=$selected_tree?>&field_name=<?=$field_name?>&Form_ID=<?=$Form_ID?>&id="+n+"&frame_id="+j+"&cat_number=<?=$cat_number?>";

        //parent.document.getElementById('frame1').
    }
</script>


<?

    // zeigt die gewählte Kat und ihre nächste(!) Unter-Kat an
    #echo "sel-cat: ".$selected_Kat."!<br>";
    $result = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND tree_number='".intval($selected_tree)."' AND ID='".intval($selected_Kat)."'");
    $data=get_data($result);
    echo "<select class=\"input\" style=\"width:465px;\" name=\"category_select_Level".$data['depth']."\" OnChange='make_my_frame(this.options[this.selectedIndex].value, $frame_id, $selected_Kat);'>";

    echo "<option class=\"input\" value=\"".intval($selected_Kat)."\">";
    echo htmlspecialchars($data['name']);
    echo "</option>";

    $result_child = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND tree_number='$selected_tree' AND parent_ID='".intval($selected_Kat)."'");

    while($child_data=get_data($result_child))
    {
        echo '                <option class = "input" value="'.$child_data['ID'].'">';
        echo "&nbsp * ".htmlspecialchars($child_data['name']);
        echo "</option>";
    }

    echo "         </select>";

}








// Diese Funktion findet die maximale Tiefe des tiefsten Kindes
function max_child_depth($db_name, $tree_table, $parent_ID)
{
  $res_gruppen[0] = db_query($db_name, "SELECT * FROM $tree_table WHERE ID='$parent_ID'");
  $level=0;
//  $tmp=get_data($res_gruppen[0]);
  $max_depth=0;

  while($level!=-1)
  {
   //echo"<br>$level#".mysql_error();
   //echo "<br>max Tiefe : ".$max_depth."<br>";

   $my_gruppen[$level] = get_data($res_gruppen[$level]);
   //var_dump($my_gruppen[$level]);
   // wenn daten: zeile ausgeben, level erhöhen, und selecten
   if($my_gruppen[$level])
   {
     if ($max_depth<$my_gruppen[$level][depth])
     {
       $max_depth=$my_gruppen[$level][depth];
     }
     $level++;
     $res_gruppen[$level] = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND parent_ID='".($my_gruppen[$level-1][ID])."'");
   }
   // wenn keine daten: level verringern
   else
   {
     $level--;
   }
  }


  return $max_depth;
}



function recalculate_depth($db_name,$tree_table)
{
  // Diese function berechnet die Tiefen aller
  // Einträge in einem Baum neu

  db_query($db_name, "UPDATE $tree_table SET depth='0' WHERE active='1' AND parent_ID='0'");
  $res_gruppen[0] = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND parent_ID='0'");
  //var_dump ($res_gruppen[0]);
  $level=0;
  while($level!=-1)
  {
   //echo"<br>$level#".mysql_error();
   $my_gruppen[$level] = get_data($res_gruppen[$level]);
   // wenn daten: zeile ausgeben, level erhöhen, und selecten
   if($my_gruppen[$level])
   {
     //db_query($db_name, "UPDATE $selected_tree SET depth='$level' WHERE active='1' AND parent_ID='0'");
     $level++;
     db_query($db_name, "UPDATE $tree_table SET depth=".($level-1)." WHERE active='1' AND ID='".($my_gruppen[$level-1][ID])."'");// AND depth='".($my_gruppen[$level-1][depth])."'
     $res_gruppen[$level] = db_query($db_name, "SELECT * FROM $tree_table WHERE active='1' AND parent_ID='".($my_gruppen[$level-1][ID])."'");
   }
   // wenn keine daten: level verringern
   else
   {
     $level--;
   }
  }
}


?>