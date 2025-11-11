<?php

namespace Cms\Helper;

class Template {
	var $template;
		// in $this->template steht nach dem Construktior
		//  - in den geraden Elementen   {0,2,4,6,...} einfacher Content zur Verfügung
		//  - in den ungeraden Elementen {1,3,5,7,...} Modulbezeichnungen zum includen zur Verfügung
	var $template_element_ids; // id_s der elemente im template;
	var $element_id;
	var $page_id;
	var $block_pre;
	var $block_post;





	function find_repeat_block($template, $config_repeat_block, $begin_delay, $end_delay) {
		global $db_data;      // Systemdatenbank-name
		global $db_module;    	// Moduldatenbank-name
		global $SHOP_DEBUG;    	// Debugmode für Shop-Dateien

		if($config_repeat_block == 'tr_repeat') {
			// alles vorm ersten <tr> in die Variable $template_pre schreiben und abschneiden

			if($begin_delay != '') { // wenn es einen begin-delay-block gibt, dann das tr erst nach dem block suchen.
				$begin_delay_sec = addslashes($begin_delay);
				$sql = "SELECT * FROM cms_content WHERE page_id = '$this->page_id' AND element = 'submodul' AND file = '$begin_delay_sec' AND active='1'";
				$element_result = DB::getQueryRows($sql);
				if($SHOP_DEBUG) echo "<br>$sql<br><br><b>". \DB::fetchLastErrorMessage().'</b><br>';
				foreach($element_result as $my_element) {
					$template = str_replace('content'.sprintf("%03d", $my_element[number]), $begin_delay, $template);
				}
				$start = strpos($template, '<#'.$begin_delay.'#>');
				$template = str_replace('<#'.$begin_delay.'#>', '', $template);
			}

			if($end_delay != '') { // wenn es einen begin-delay-block gibt, dann das tr erst nach dem block suchen.
				$end_delay_sec = addslashes($end_delay);
				$sql = "SELECT * FROM cms_content WHERE page_id = '$this->page_id' AND element = 'submodul' AND file = '$end_delay_sec' AND active='1'";
				$element_result = DB::getQueryRows($sql);
				if($SHOP_DEBUG) echo "<br>$sql<br><br><b>". \DB::fetchLastErrorMessage().'</b><br>';
				foreach($element_result as $my_element) {
					$template = str_replace('content'.sprintf("%03d", $my_element[number]), $end_delay, $template);
				}

				$stop = strpos($template, '<#'.$end_delay.'#>');
				$template = str_replace('<#'.$end_delay.'#>', '', $template);
			}
			//sonderfälle des delays  abfangen:
			if($start >= $stop) $stop = strlen($template);

// MARKS BUGFIX

			// Blöcke finden und ausschneiden
			//anfang des repeat-blocks finden
			$pos = 				strpos($template, '<tr>', $start);
			if(!$pos) $pos=		strpos($template, '<TR>', $start);
			$this->block_pre =	substr($template, 0, $pos);

			$pos2 = 			strpos($template, '</tr>', $stop);
			if(!$pos2) $pos2 =	strpos($template, '</TR>', $stop);

			while($pos2 == 0) {
				$stop -= 5;
				$pos2 = 		strpos(strtolower($template), '</tr>', $stop);
			}

			$pos2 += 5;
			$this->block_post =	substr($template, $pos2);
			$template = substr($template,$pos,$pos2-$pos);

			//MK $template =			substr($template, $pos);

			/*
			echo "\n\n##############\n\n".$this->block_pre."\n\n##############\n\n";
			echo "\n\n##############\n\n".$template."\n\n##############\n\n";
			echo "\n\n##############\n\n".$this->block_post."\n\n##############\n\n";
			*/

			// alles nach dem letzten </tr> in die Variable $template_post schreiben und abschneiden
			/*
			$tmp=$pos+3;
			$pos2=0;
			while($tmp > $pos2 AND $tmp <= $stop) { // das $stop muss Überschritten werden, aber der _erste_ danach soll genommen werden
				$pos2 = $tmp;
				$tmp = 				@strpos($template, '</tr>', $pos2+1);
				if(!$tmp) $tmp=		@strpos($template, '</TR>', $pos2+1);
			}
			if($pos2 != 3) {
				$pos2 += 5; // das '</tr>' noch mit ausschneiden
				$this->block_post =	substr($template, $pos2, strlen($template));
				$template =			substr($template, 0, $pos2);
			}
			*/
		}

		return $template;
	}


	 function __construct($page_id, $element_id, $template, $begin_delay='', $end_delay='',$config_repeat_block= 'tr_repeat') {

		global $db_data;      // Systemdatenbank-name
		global $db_module;      // Moduldatenbank-name
		global $SHOP_DEBUG;     // Debugmode für Shop-Dateien

		$this->page_id =        addslashes($page_id);
		$this->element_id =     addslashes($element_id);

		$this->template = $this->find_repeat_block($template, $config_repeat_block, $begin_delay, $end_delay); // findet den Bereich, der laut Einstellung wiederholt werden soll
		// Template in array einlesen
		//echo $template.'<br><br>';
		$this->template = str_replace('<#', '#>', $this->template);
		$this->template = explode('#>', $this->template); // danach sind die geraden(ab 0) immer text, die ungeraden(ab 1) immer contentbereiche
		//var_dump($this->template);

		// Die Liste der Submodule dieser Seite einlesen:
		$sSql = "SELECT c.id, c.page_id, c.file, c.number, c.public as template, e.template as global_template FROM cms_content c LEFT JOIN system_elements e ON c.file=e.file WHERE c.page_id = ".(int)$this->page_id." AND c.element = 'submodul' AND c.active = 1 ";
		$element_result = \DB::getQueryRows($sSql);
		if($SHOP_DEBUG) echo "<br>$sql<br><br><b>". \DB::fetchLastErrorMessage().'</b><br>';

		foreach($element_result as $my_element) {        //Array erzeugen, in dem im Shlüssel die ElementID und im Value der Filename steht
				$this->elemente[$my_element[number]] =  $my_element;
		}

		for($i=1; $i<count($this->template); $i+=2) { //Die ungeraden elemente durchgehen
				// contentXXX wird hier in die entsprechende modulbezeichnung konvertiert
				$this->template_element_ids[$i] =       (int)substr($this->template[$i],7);
				$this->template_element_data[$i] =      $this->elemente[$this->template_element_ids[$i]];
				if($this->template_element_data[$i][template] =='')
				   $this->template_element_data[$i][template] = $this->elemente[$this->template_element_ids[$i]][global_template];
				$this->template[$i] =                           $this->elemente[$this->template_element_ids[$i]][file];
		}
		// in $this->template steht jetzt
		//  - in den geraden Elementen   {0,2,4,6,...} einfacher Content zur Verfügung
		//  - in den ungeraden Elementen {1,3,5,7,...} Modulbezeichnungen zum includen zur Verfügung
	}

	function parse($_template = false, $_element_ids = false) {
		if(!$_template)         $_template =            $this->template;
		if(!$_element_ids)      $_element_ids = $this->template_element_ids;
		global $SHOP_MOD_PATH;          // Modulpfad (definiert in der shop.inc)
		global $SHOP_DEBUG;     // Debugmode für Shop-Dateien
		global $SHOP_PATH;
		global $SHOP_DETAIL_PAGE;
		global $shop_product;
		global $shop_actual_inv_group;
		global $__shop_color1;
		global $__shop_color2;
		global $__shop_colors;
		global $db_module;
		global $user_data, $system_data, $file_data, $page_data, $db_data;
		global $_VARS;

		//Farben wurden noch nicht geladen
		if($system_data['shop_active'] && !$__shop_colors){
                  $__shop_colors = true; //damit's nur ein mal pro durchlauf geladen wird
                  $shop_actual_inv_group_sec =	(int)$shop_actual_inv_group;
                  $time_sec=time();
                  $sql = "SELECT color1, color2 FROM shop_inventory_group WHERE `group` = '$shop_actual_inv_group_sec'
   	               AND valid_from  <= '$time_sec'
                       AND valid_until >= '$time_sec'
	               AND active = '1'
                       LIMIT 0, 1";
                  if($SHOP_DEBUG) echo "<br>$sql<br><br>Daten:";
                  $my_color = \DB::getQueryRow($sql);
                  $__shop_color1 = $my_color[color1];
                  $__shop_color2 = $my_color[color2];
		}
	        for($_i=0; $_i<count($_template); $_i+=2) { //Die ungeraden elemente durchgehen
                        $out = str_replace('#detailurl#', $SHOP_PATH.$SHOP_DETAIL_PAGE.'?shop_inventory_key='.urlencode($shop_product->key).'&shop_actual_inv_group='.urlencode($shop_actual_inv_group),$_template[$_i]);
                        $out = str_replace('#color1#', $__shop_color1 ,$out);
                        $out = str_replace('#color2#', $__shop_color2 ,$out);
                        echo $out;
	                $page_id = $this->page_id;
	                $element = $_element_ids[$_i+1];
	                $element_data = $this->template_element_data[$_i+1];
	                $element_data['id'] = $this->template_element_data[$_i+1]['number'];
	                $element_data['page_id'] = $this->template_element_data[$_i+1]['page_id'];
	                $element_data['content_id'] = $this->template_element_data[$_i+1]['id'];

	                if($SHOP_DEBUG) echo '#'.$_i.':'.$_template[$_i+1].'#';

	                // Achtung, brutaler workaround!! Dringend ändern, bzw neue class einführen!!
                        //echo "<br>Doc Root : ".$DOCUMENT_ROOT;
	                if ($SHOP_MOD_PATH=="" OR !$SHOP_MOD_PATH){$SHOP_MOD_PATH=\Util::getDocumentRoot()."system/extensions/";}
	                if($_template[$_i+1]) include($SHOP_MOD_PATH.$_template[$_i+1].'.mod');
	        }
	}




	function parse_block($block) {
//		echo $block;
		$block = str_replace('<#', '#>', $block);
		$block = explode('#>', $block); // danach sind die geraden(ab 0) immer text, die ungeraden(ab 1) immer contentbereiche

		for($i=1; $i<count($block); $i+=2) { //Die ungeraden elemente durchgehen
			// contentXXX wird hier in die entsprechende modulbezeichnung konvertiert
			$element_ids[$i] =	(int)substr($block[$i],7);
			$block[$i] =		$this->elemente[(int)substr($block[$i],7)][file];
		}
		$this->parse($block, $element_ids);
	}
	
	
	static public function getStructure($sParent='') {

		$aStructure = [];
		
		$sSql = "
			SELECT 
				* 
			FROM 
				cms_pages 
			WHERE 
				path = :path AND 
				element = 'template' AND 
				active != 2 
			ORDER BY 
				title";
		$aSql = [
			'path' => $sParent
		];
		$aTemplates = (array)\DB::getQueryRows($sSql, $aSql);
		
		foreach($aTemplates as $aTemplate) {
		
			$oPageTemplate = \Cms\Entity\PageTemplate::getInstance($aTemplate['id']);
			
			if($aTemplate['file'] == $sParent) {
				continue;
			}

			$aTemplate['page'] = $oPageTemplate;
			$aTemplate['childs'] = self::getStructure($aTemplate['file']);
			
			$aStructure[] = $aTemplate;
			
		}

		return $aStructure;
	}
	
}