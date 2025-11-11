<?
// Klasse, die sämtliche SQL-Abfragen behandelt

// Folgende functions werden von dieser Klasse betreitgestellt:
 
// SQL_get_row_by_name
// SQL_get_row_by_option
// SQL_insert_row
// SQL_update_row_by_id
// SQL_update_row_by_option
// SQL_get_rows_by_option_and_limit
// SQL_delete_row

// SQL_get_field_names
// SQL_insert_field
// SQL_delete_field

// SQL_get_table_names (by identifier)
// SQL_create_new_table
// SQL_delete_table

// SQL_get_ID

global $db_class_is_declared;
$db_class_is_declared=1;

class customer_db
{
  var $result;

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_get_row_by_name($db_name, $definition_table, $table_name, $field_name)
  {
    $position=strrpos($table_name,"_")+1;
    $tablenumber=substr($table_name,$position);
    $query="SELECT * FROM $definition_table WHERE name=\"$field_name\" AND db_nr=$tablenumber AND active=1";
    $result=mySQL_db_query($db_name,$query);
    $temp=mySQL_fetch_array($result);
	$this->result[1]=$temp;
  }  // Ende function SQL_get_row_by_name

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////


function SQL_get_row_by_option($db_name, $definition_table, $table_name, $option_name, $option, $orderby=0)
{
    $query="SELECT * FROM $definition_table WHERE active=\"1\"";
    if (!is_array($option_name))
    {
        if ($option_name!=""){$query.=" AND $option_name = \"$option\"";}
    }
    else
    {
	    for ($i=0;$i<count($option_name);$i++)
	    {
	       if ($option_name[$i]!="")
           {
               $query.=" AND $option_name[$i] = \"$option[$i]\"";
           }
	    }
    }
	if($orderby) {
		$query .= " ORDER BY ".$orderby." ";
	}
    $result = DB::executeQuery($query);

    $i=0;
    if ($result)
    {
	   while ($temp = DB::getRow($result))
	   {
	       $values[$i]=$temp;
	       $i++;
	   }
	   $this->result[1]=$values;
    }
  } // Ende function SQL_get_row_by_option


///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////


  function SQL_get_rows_by_option_and_limit($db_name, $definition_table, $table_name, $option_name, $option, $limit_lower = -1, $limit_upper = -1)
  {
    $query="SELECT * FROM $definition_table WHERE active=1 ";

    if(is_array($option_name))
    {
        for ($i=0;$i<count($option_name);$i++)
        {
            $query.=" AND ".$option_name[$i]." = \"".$option[$i]."\"";
        }
    }
    elseif($option_name!="")
    {
        $query.=" AND ".$option_name." = \"".$option."\"";
    }
    

    if ($limit_lower>-1 && $limit_upper>-1)
	{
	  $query.=" LIMIT $limit_lower, $limit_upper";
	}
	#echo "<br>".$query."<br>";
	$result=mySQL_db_query($db_name,$query);
	$i=0;
	while ($temp=@mySQL_fetch_array($result))
	{
	 $values[$i]=$temp;
	 $i++;
	}
	$this->result[1]=$values;
	  //var_dump($result);

  } // Ende function SQL_get_row_by_option


///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
  
  function SQL_update_row_by_id($db_name,$table_name,$name_array,$value_array,$id)
  {
	$query="UPDATE $table_name SET";
	for($i=0;$i<count($name_array);$i++)
	{
	 $query.=" $name_array[$i] = \"$value_array[$i]\"";
	 if (count($name_array)>($i+1)) $query.=",";
	}
	
	substr($query,0,(strlen($query)-2));

	$query.=" WHERE id=$id";
	#echo $query;
	mySQL_db_query($db_name,$query);
	
  } // Ende function SQL_update_row_by_id

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_update_row_by_option($db_name,$table_name,$name_array,$value_array,$option_name, $option)
  {
    /*
    echo "<br>option_name";
	var_dump($name_array);
	echo "<br>option";
	var_dump($value_array);
    */

	$query="UPDATE $table_name SET";
	for($i=0;$i<count($name_array);$i++)
	{
         if ($name_array[$i]!="")
         {
           $query.=" ".$name_array[$i]." = '".$value_array[$i]."',";
           //if (count($name_array)>($i+1)) $query.=",";
         }
	}
        if ($query[strlen($query)-1]==",")
        {
          $query=substr($query,0,(strlen($query)-1));
        }

	$query.=" WHERE";
	
	if (is_string($option_name))
	{
           $query.=" $option_name = \"$option\"";
        }
        else
        {
  	for($i=0;$i<count($option_name);$i++)
	{
         if ($option_name[$i]!="")
         {
           $query.=" $option_name[$i] = \"$option[$i]\"";
           if (count($option_name)>($i+1)) $query.=" AND ";
         }
	}
        }

	db_query($db_name,$query);

  } // Ende function SQL_update_row_by_option

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_delete_row($db_name,$table_name,$name)
  {
   if ($name!="")
   {
	 mySQL_db_query($db_name,"UPDATE $table_name SET active=0 WHERE name=\"$name\"");
   }
  }  // Ende function SQL_delete_row<br>

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_delete_row_by_option($db_name,$table_name,$option_name,$option)
  {
   if ($option_name!="")
   {
	 DB::executeQuery("UPDATE $table_name SET active=0 WHERE $option_name=\"$option\"");
   }
  }  // Ende function SQL_delete_row
  
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

 function SQL_insert_field($db_name,$table_name,$field_name,$options)
 {
   	// Fügt einer vorhandene Tabelle ein neues Feld hinzu
   	$query="ALTER TABLE `$table_name` ADD `$field_name` $options";
	if (!(db_query($db_name,$query)))
	{
	 return FALSE;
	}
	else
 	{
	 return TRUE;
	}

 } // Ende function SQL_insert_field()

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

 function SQL_insert_unique_field($db_name,$table_name,$field_name)
 {
   	// Fügt einer vorhandene Tabelle ein neues Feld hinzu
   	$query="ALTER TABLE `$table_name` ADD UNIQUE ($field_name)";
   	//echo $query."<br>";
	if (!(mySQL_db_query($db_name,$query)))
	{
	 return FALSE;
	}
	else
 	{
	 return TRUE;
	}

 } // Ende function SQL_insert_field()





///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

 function SQL_delete_field($db_name,$table_name,$field_name)
  {
	//Entfernt ein Feld aus der Tabelle
	#die("ALTER TABLE $table_name CHANGE $field_name __remove_$field_name TEXT NOT NULL");
	#mySQL_db_query($db_name,"ALTER TABLE $table_name DROP $field_name");
	DB::executeQuery("ALTER TABLE $table_name CHANGE $field_name __remove_$field_name TEXT NOT NULL");
	
  } // Ende function SQL_delete_field
  
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

 function SQL_get_table_names($db_name, $identifier)
  {
	// Gibt die Namen aller Tabellen in der DB zurück, die mit identifier beginnen
	// ausgenommen die identifier_definition Tabelle

	$result=@mySQL_list_tables($db_name);
	$i=0;
	$n=0;
	
	while ( $i < mySQL_num_rows ( $result ) )
	{
	 $temp=mySQL_tablename ( $result, $i );
	 $position=strpos ( $temp, $identifier );
	 if( $position  !== FALSE AND (strpos(mySQL_tablename($result,$i) , "inactive"))=== FALSE  AND ((strpos(mySQL_tablename($result,$i) , "values"))=== FALSE)    )
         {
	  if (mySQL_tablename ( $result, $i ) != $identifier."definition" )
	  {
	    $name_array[$n]=mySQL_tablename ( $result, $i );
	  }
 	  $n++;

	 }
	 $i++;
	}

	$this->result[1] = $name_array;

  } // Ende function SQL_get_table_names

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_create_new_table($db_name,$table_name)
  {
	// erzeugt eine neue Tabelle mit dem Name $table_name
	// bei der Erzeugung wird automatisch ein ID Feld mit auto-increment
	// als Primary Key miterzeugt!

	
	$query="CREATE TABLE `$table_name` (`id` INT NOT NULL AUTO_INCREMENT ,PRIMARY KEY ( `id` ))";
	//echo $query;
	mySQL_db_query($db_name,$query);

  } // Ende function SQL_create_new_table

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////


  function SQL_delete_table($db_name,$table_name)
  {
   // Entfernt eine Tabelle aus einer Datenbank
   $counter=0;
   $inactive_table_name="inactive_".$table_name;
   while(!(mySQL_db_query($db_name, "RENAME TABLE $table_name TO $inactive_table_name")))
   {
	 $counter++;
	 $inactive_table_name="inactive_".$table_name."_".$counter;
   }



  } // Ende function SQL_delete_table

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_get_id($db_name,$definition_table,$table_name,$field_name)
  {
    $position=strrpos($table_name,"_")+1;
    $tablenumber=substr($table_name,$position);
	$query="SELECT * FROM $definition_table WHERE name=\"$field_name\" AND db_nr=$tablenumber AND active=1";
	$result=mySQL_db_query($db_name,$query);
	$temp=mySQL_fetch_array($result);
	$this->result[1]=$temp["id"];

  }

///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

  function SQL_get_id_by_field_nr($db_name,$definition_table,$table_name,$field_nr)
  {
    $position=strrpos($table_name,"_")+1;
    $tablenumber=substr($table_name,$position);
	$query="SELECT * FROM $definition_table WHERE field_nr=\"$field_nr\" AND db_nr=$tablenumber AND active=1";
	#echo $query;
	$result=mySQL_db_query($db_name,$query);
	$temp=mySQL_fetch_array($result);
	$this->result[1]=$temp["id"];

  }


}  // Ende class customer_db






?>