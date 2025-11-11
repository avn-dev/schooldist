<?php

namespace Cms\Service;

class PageParser {
	
	public $intLevel;
	protected $_iLatestBlockContent = 800;
	
	protected $oPage;
			
	protected $fStartTime;
	
	function __construct(\Cms\Entity\Page $oPage) {
		$this->intLevel = 0;
		$this->oPage = $oPage;
	}

	public function setStartTime($fStartTime) {
		$this->fStartTime = $fStartTime;
	}

	/**
	 * @return \Cms\Entity\Site
	 */
	public function getSite() {
		
		$oSite = \Cms\Entity\Site::getInstance($this->oPage->site_id);
		
		return $oSite;
	}
	
	function printParserEditBar($idPage, $idElement, $iLevel, $iLayer, $sString, $sMode) {
	
		if($iLayer <= $iLevel) {
			$table_bg_color = "lime";
			$editablediv = "activeeditablediv";
			$handler = ' onkeyup="parent.parent.preload.showCurrentElement(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.setTableElements(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.repositionArrows();" onmouseup="parent.parent.preload.showCurrentElement(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.setTableElements(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.stopCellResize(false);" onscroll="if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.repositionArrows()"';
			$tableId = "edittools_active";
		} else {
			$table_bg_color = "#dededf";
			$editablediv = "editablediv";
			$handler = '';
			$tableId = "edittools_inactive";
		}
	
		$sCode .= "\n".
			"<table id=\"".$tableId."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n".
			"	<tr>\n".
			"		<td><img src=/admin/media/edit_left.gif></td>\n".
			"		<td nowrap><a href=\"".$_SERVER['PHP_SELF']."?mode=".$sMode."&layer=".$iLevel."\"><img src=/admin/media/edit_layer.gif border=0 alt=\"Layer wechseln\"></a></td>\n".
			"		<td><img src=/admin/media/edit_middle.gif></td>\n".
			"		<td nowrap>Inhalt</td>\n";
	
		if($iLayer <= $iLevel) {
			$sCode .= "		<td><img src=/admin/media/edit_middle.gif></td>\n".
				"		<td><a href=# onclick=\"parent.parent.preload.save_db(".$idPage.",".$idElement.",'DIV".$sString."');\"><img src=/admin/media/edit_save.png border=0 alt=\"&Auml;nderung speichern\"></a></td>\n".
				"		<td><img src=/admin/media/edit_spacer.gif></td>\n".
				"		<td><a id=\"editable_1_".$idElement."\" href=# onclick=\"parent.parent.preload.swap_icons2(1,'".$idElement."'); parent.parent.preload.changeeditable(".$idPage.",".$idElement.",'DIV".$sString."');\"><img src=/admin/media/edit_content_1.gif border=0 alt=\"Bearbeiten\"></a><a id=\"editable_2_".$idElement."\" style=\"display:none\" href=# onclick=\"parent.parent.preload.swap_icons2(2,'".$idElement."'); parent.parent.preload.changeeditable(".$idPage.",".$idElement.",'DIV".$sString."');\"><img src=/admin/media/edit_content_2.gif border=0 alt=\"Bearbeiten beenden\"></a></td>\n".
				"		<td><img src=/admin/media/edit_spacer.gif></td>\n".
				"		<td><a id=\"edit_preview1_".$idElement."\" href=# onclick=\"parent.parent.preload.show_code(".$idPage.",".$idElement.",'DIV".$sString."','VAR".$sString."');\"><img src=/admin/media/edit_code.gif border=0 alt=\"Code Ansicht\"></a></td>\n".
				"		<td><a id=\"edit_preview2_".$idElement."\" style=\"display:none\" href=# onclick=\"parent.parent.preload.swap_icons('DIV".$sString."',".$idElement."); parent.parent.preload.show_preview(".$idPage.",".$idElement.",'DIV".$sString."');\"><img src=/admin/media/edit_preview.gif border=0 alt=\"Vorschau Ansicht\"></a></td>\n".
				"		<td><img src=/admin/media/edit_spacer.gif></td>\n".
				"		<td><a href=# onclick=\"window.open('/admin/help.html?help_max=1&help_file=content', 'Hilfe','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');\"><img src=/admin/media/edit_help.gif border=0 alt=\"Hilfe\"></a></td>\n";
		}
	
		$sCode .= "		<td><img src=/admin/media/edit_right.gif></td>\n".
			"	</tr>\n".
			"</table>\n".
			"<div class=\"$editablediv\" $handler style=\"border : 1pt solid $table_bg_color; padding : 1;\" id=\"DIV".$sString."\">";
	
		return $sCode;
	}
	
	// check cms_content for elements and return html
	
	function checkCode($sitecode) {
		global $domain_name;
		global $enter;
		global $dir_name;
		global $db_data;
		global $system_data;
		global $session_data;
		global $page_data;
		global $user_data;
		global $template_data;
		global $file_data;
		global $domain;
		global $_VARS;
		global $objWebDynamicsDAO;
		global $dir_name_cache,$search_link,$admin_link,$parent_id,$parent_string;
		global $oSite;
	
		$this->intLevel++;

		if(
			$this->oPage->getMode() !== \Cms\Entity\Page::MODE_EDIT &&
			$this->oPage->getMode() !== \Cms\Entity\Page::MODE_PREVIEW
		) {
			$validquery = "
				(
					(
						(
							UNIX_TIMESTAMP(validto) > UNIX_TIMESTAMP(validfrom) AND
							UNIX_TIMESTAMP(validto) < 169200 AND
							(
								UNIX_TIMESTAMP(validfrom) < UNIX_TIMESTAMP(TIME_FORMAT(NOW(),'1970-01-02 %H:%i:%s')) OR
								validfrom = 0
							) AND (
								UNIX_TIMESTAMP(validto) > UNIX_TIMESTAMP(TIME_FORMAT(NOW(),'1970-01-02 %H:%i:%s')) OR
								validto = 0
							)
						) OR (
							UNIX_TIMESTAMP(validto) < UNIX_TIMESTAMP(validfrom) AND
							UNIX_TIMESTAMP(validto) < 169200 AND
							(
								(
									UNIX_TIMESTAMP(validfrom) < UNIX_TIMESTAMP(TIME_FORMAT(NOW(),'1970-01-02 %H:%i:%s'))
								) OR (
									UNIX_TIMESTAMP(validto) > UNIX_TIMESTAMP(TIME_FORMAT(NOW(),'1970-01-02 %H:%i:%s'))
								)
							)
						)
					) OR (
						(
							validfrom < NOW() OR
							validfrom = 0
						) AND (
							validto > NOW() OR validto = 0
						)
					)
				) AND ";
		}

		$pos_old = 0;
		$pos = 0;
		while(($pos = strpos($sitecode, "<#content")) !== false) {
	
			$iCodeEndPos = strpos($sitecode, "#>", $pos);
			$string1 = substr($sitecode, $pos+9, 3);
			$string2 = substr($sitecode, $pos+9, $iCodeEndPos-$pos-9);

			if(\System::d('debugmode')) {
				echo '<!-- Content tag: '.$string1.', '.$string2.' -->';
			}

			$element_data['parent_id'] = null;
			
			$regs = array();
			if(preg_match("/([0-9]{9}):([0-9]{3})/", $string2, $regs)) {
				$element_data['id'] 		= (int)$regs[2];
				$element_data['page_id'] 	= (int)$regs[1];
				$string1 = $regs[2];
				$sPageElement = $string2;
			} elseif(preg_match("/([0-9]+)-([0-9]+)/", $string2, $regs)) {
				$element_data['id'] = (int)$regs[2];
				$element_data['parent_id'] = (int)$regs[1];
				$element_data['page_id'] = $page_data['id'];
				$sPageElement = $string2;
			} else {
				$element_data['id'] = (int)$string1;
				if ($element_data['id'] >= 900) {
					$element_data['page_id'] = $template_data['id'];
				} else {
					$element_data['page_id'] = $page_data['id'];
				}
				$sPageElement = $string1;
			}
	
			if ($element_data['id'] >= 900) {
				$element_data['id'] = $element_data['id'] - 900;
				$element_data['in_template'] = true;
			} else {
				$element_data['id'] = $element_data['id'];
				$element_data['in_template'] = false;
			}
	
			$img = "<#content".$sPageElement."#>";
	
			if(strpos($sitecode, $img) === false) {
				echo $sitecode;
				echo '<!-- Error: Content tag "'.$img.'" not found. -->';
				break;
			}
	
			echo (substr($sitecode,$pos_old,$pos-$pos_old));
	
			if(\System::d('debugmode')) {
				echo '<!-- Runtime: '.$this->getCurrentRuntime().'-->';
			}
			
			echo "<!-- WD:CONTENT:START:".$sPageElement." -->";
			
			$pos_old = $pos;
			$pos++;

			// Freier Layout Modus
			if(
				$element_data['in_template'] == true || 
				$page_data['layout'] == "free"
			) {
	
				$strSql = "SELECT c.*, UNIX_TIMESTAMP(`changed`) `changed` FROM cms_content c WHERE ".$validquery." element != 'block' AND page_id = '".(int)$element_data['page_id']."' AND number = '".(int)$element_data['id']."' AND level = '".(int)$this->intLevel."' AND active = 1";
				$my_element = \DB::getQueryRow($strSql);
				if(empty($my_element) && $user_data['cms']){
					\DB::executeQuery("INSERT INTO cms_content SET page_id = '".$element_data['page_id']."', number = '".$element_data['id']."', element = 'content', file = '', author = '".$user_data['id']."', active = '1', level = '1'");
					if(\System::d('debugmode') == 1) {
						\Util::handleErrorMessage("Das Element wurde in der Datenbank nicht gefunden. Es wurde automatisch ein neues angelegt.");
					}
					$my_element = \DB::getQueryRow($strSql);
				}

				if(
					$this->oPage->getMode() !== \Cms\Entity\Page::MODE_EDIT &&
					$this->oPage->getMode() !== \Cms\Entity\Page::MODE_PREVIEW	
				) {
					$my_element['content'] = $my_element['public'];
				}

				if(
					($my_element['uptodate'] == 0) && 
					!$element_data['in_template']
				) {
					$this->oPage->setUpToDate(false);
				}
				$element_data['changed'] = $my_element['changed'];
				$element_data['content'] = $my_element['content'];
				$element_data['level_id'] = $my_element['level'];
				$element_data['content_id'] = $my_element['id'];
	
				if($my_element['element'] == "element" && ($my_element['active'] == 1 || $enter == "ok")) {
					$elemcode = "";
					if ($enter == "ok") {
						$elemcode .= "<div>";
					}
	
					$elemcode .= $my_element['content'];
	
					if ($enter == "ok") {
						$elemcode .= "</div>";
					}
	
					$sitecode = str_replace($img,"",$sitecode);
	
					$this->checkCode($elemcode);
	
				} elseif ($my_element['element'] == "content") {
					$elemcode = "";
	
					if ($enter == "ok") {
						$elemcode .= $this->printParserEditBar($element_data['page_id'], $element_data['id'], $my_element['level'], $_VARS['layer'], $string1, $session_data['mode']);
					}
	
					$elemcode .= $my_element['content'];
	
					if ($enter == "ok") {
						$elemcode .= "</div>\n";
					}
	
						$sitecode = str_replace($img,"",$sitecode);
						if($enter == "ok" && $_VARS['layer'] <= $my_element['level']) {
							while(preg_match("/<#content(.{3})#>/",$elemcode,$regs)) {
								$new_number = $regs[1];
								$temp_number = (int)$new_number;
								$my_regs = \DB::getQueryRow("SELECT level FROM cms_content WHERE page_id = '".$element_data['page_id']."' AND number = ".$temp_number." AND active = 1");
								$elemcode = ereg_replace("<#content$new_number#>","<IMG onClick=\"parent.parent.preload.switchLayer('".$my_regs['level']."');\" src=\"$domain/admin/includes/PHPcontent.php?element_id=$new_number&page_id=".$element_data['page_id']."\" border=0>",$elemcode);
							}
							if($enter == "ok" && $_VARS['layer'] <= $my_element['level']) {
								echo ($elemcode);
							} else {
								echo ($elemcode);
							}
						} else {
							$parent_id = $element_data['id'];
							$parent_string = $string1;
							$this->checkCode($elemcode);
						}
	
				} elseif ($my_element['element'] == "modul") {
	
						$bolShowEditDiv = 0;
						if($enter == "ok" && $element_data['in_template'] == false) {
							$bolShowEditDiv = 1;
						}
	
						$elemcode = "";
						if($bolShowEditDiv) {
							$my_modul = \DB::getQueryRow("SELECT * FROM system_elements WHERE file = '".$my_element['file']."'");
							$c_child = \DB::getQueryRows("SELECT * FROM system_elements WHERE parent = '".$my_element['file']."'");
							if($_VARS['layer'] <= $my_element['level']) {
								$table_bg_color = "lime";
								$editablediv = "activeeditablediv";
								$tableId = "edittools_active";
							} else {
								$table_bg_color = "#dededf";
								$editablediv = "editablediv";
								$tableId = "edittools_inactive";
							}
			// wenn Kindmodule vorhanden sind, dann Editarea einblenden
			if(!empty($c_child)) {
				$handler = ' onkeyup="if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.setTableElements(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.repositionArrows();" onmouseup="if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.setTableElements(); if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.stopCellResize(false);" onscroll="if(parent.parent.preload.tEdit) parent.parent.preload.tEdit.repositionArrows()"';
				echo "<script>parent.parent.preload.isparentmod['DIV$string1'] = '".$my_element['file']."';</script>";
				echo "<table id=\"".$tableId."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
				<tr>
				    <td><img src=/admin/media/edit_left.gif></td>
				    <td nowrap><a href=\"".$_SERVER['PHP_SELF']."?layer=".$my_element['level']."\"><img src=/admin/media/edit_layer.gif border=0 alt=\"Layer wechseln\"></a></td>
				    <td><img src=/admin/media/edit_middle.gif></td>
				    <td nowrap>".$my_modul['title']." Modul</td>";
				if($_VARS['layer'] <= $my_element['level']) {
					echo "<td><img src=/admin/media/edit_middle.gif></td>
					    <td><a href=# onclick=\"if(confirm(unescape('M%F6chten Sie dieses Modul wirklich l%F6schen%3F'))) parent.parent.save.location.href='/admin/save.html?element_string=$string1&parent_id=$parent_id&action=delete&title=Modul&page_id=".$element_data['page_id']."&did=".$my_element['id']."';\"><img src=/admin/media/edit_delete.gif border=0 alt=\"Modul l&ouml;schen\"></a></td>
						<td><img src=/admin/media/edit_spacer.gif></td>
					    <td><a href=# onclick=\"parent.parent.preload.save_db(".$element_data['page_id'].",".$element_data['id'].",'DIV$string1');\"><img src=/admin/media/edit_save.png border=0 alt=\"&Auml;nderung speichern\"></a></td>
						<td><img src=/admin/media/edit_spacer.gif></td>
						<td><a id=\"editable_1_".$element_data['id']."\" href=# onclick=\"parent.parent.preload.swap_icons2(1,".$element_data['id']."); parent.parent.preload.changeeditable(".$element_data['page_id'].",".$element_data['id'].",'DIV$string1');\"><img src=/admin/media/edit_content_1.gif border=0 alt=\"Bearbeiten\"></a><a id=\"editable_2_".$element_data['id']."\" style=\"display:none\" href=# onclick=\"parent.parent.preload.swap_icons2(2,".$element_data['id']."); parent.parent.preload.changeeditable(".$element_data['page_id'].",".$element_data['id'].",'DIV$string1');\"><img src=/admin/media/edit_content_2.gif border=0 alt=\"Bearbeiten beenden\"></a></td>
						<td><img src=/admin/media/edit_spacer.gif></td>
					    <td><a id=\"edit_preview1_".$element_data['id']."\" href=# onclick=\"parent.parent.preload.show_code(".$element_data['page_id'].",".$element_data['id'].",'DIV$string1','VAR$string1');\"><img src=/admin/media/edit_code.gif border=0 alt=\"Code Ansicht\"></a></td>
						<td><a id=\"edit_preview2_".$element_data['id']."\" style=\"display:none\" href=# onclick=\"parent.parent.preload.swap_icons('DIV$string1',".$element_data['id']."); parent.parent.preload.show_preview(".$element_data['page_id'].",".$element_data['id'].",'DIV$string1');\"><img src=/admin/media/edit_preview.gif border=0 alt=\"Vorschau Ansicht\"></a></td>";
					    if(is_file(\Util::getDocumentRoot()."system/legacy/admin/extensions/".$my_element['file'].".html")) {
							echo "<td><img src=/admin/media/edit_spacer.gif></td>";
						    echo "<td><a href=# onclick=\"window.open('/admin/frame.html?file=extensions/".$my_element['file']."&page_id=".$element_data['page_id']."&element_id=".$element_data['id']."&content_id=".$element_data['content_id']."','modul','status=no,resizable=yes,menubar=no,scrollbars=yes,width=650,height=450');\"><img src=/admin/media/module_edit.gif border=0 alt=\"Einstellungen vornehmen\"></a></td>";
					    }
						echo "<td><img src=/admin/media/edit_spacer.gif></td>
				    	<td><a href=# onclick=\"window.open('/admin/help.html?help_max=1&help_file=content', 'Hilfe','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');\"><img src=/admin/media/edit_help.gif border=0 alt=\"Hilfe\"></a></td>";
				}
				echo "<td><img src=/admin/media/edit_right.gif></td>
					</tr>
				</table>";
			} else {
				$handler = '';
				echo "
				<table id=\"".$tableId."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" bgcolor=\"$table_bg_color\">
				<tr>
				    <td><img src=/admin/media/edit_left.gif></td>
				    <td nowrap><a href=\"".$_SERVER['PHP_SELF']."?layer=".$my_element['level']."\"><img src=/admin/media/edit_layer.gif border=0 alt=\"Layer wechseln\"></a></td>
				    <td><img src=/admin/media/edit_middle.gif></td>
				    <td nowrap>".$my_modul['title']." Modul</td>";
				if($_VARS['layer'] <= $my_element['level']) {
				    echo "<td><img src=/admin/media/edit_middle.gif></td>
				    <td><a href=# onclick=\"if(confirm(unescape('M%F6chten Sie dieses Modul wirklich l%F6schen%3F'))) parent.parent.save.location.href='/admin/save.html?element_string=$string1&parent_id=$parent_id&action=delete&title=Modul&page_id=".$element_data['page_id']."&did=".$my_element['id']."';\"><img src=/admin/media/edit_delete.gif border=0 alt=\"Modul l&ouml;schen\"></a></td>";
				    if(is_file(\Util::getDocumentRoot()."system/legacy/admin/extensions/".$my_element['file'].".html")) {
							echo "<td><img src=/admin/media/edit_spacer.gif></td>";
						    echo "<td><a href=# onclick=\"window.open('/admin/frame.html?file=extensions/".$my_element['file']."&page_id=".$element_data['page_id']."&element_id=".$element_data['id']."&content_id=".$element_data['content_id']."','modul','status=no,resizable=yes,menubar=no,scrollbars=yes,width=650,height=450');\"><img src=/admin/media/module_edit.gif border=0 alt=\"Einstellungen vornehmen\"></a></td>";
					    }
				    echo "<td><img src=/admin/media/edit_spacer.gif></td>
				    <td><a href=# onclick=\"window.open('/admin/frame.html?file=template&page_id=".$element_data['page_id']."&element_id=".$element_data['id']."&action=template','code','status=no,resizable=yes,menubar=no,scrollbars=yes,width=650,height=450');\"><img src=/admin/media/edit_code.gif border=0 alt=\"Code Ansicht\"></a></td>";
				}
				echo "<td><img src=/admin/media/edit_right.gif></td>
				</tr>
				</table>";
			}
			echo "<div class=\"$editablediv\" $handler style=\"border : 1pt solid $table_bg_color; padding : 1;\" id=\"DIV$string1\">";
		}
		if($my_element['content'] == "") {
			$my_modul = \DB::getQueryRow("SELECT * FROM system_elements WHERE file = '".$my_element['file']."' AND element = 'modul'");
			$my_element['content'] = $my_modul['template'];
		} elseif(is_numeric($my_element['content'])) {
			$my_modul = \DB::getQueryRow("SELECT * FROM system_templates WHERE id = '".$my_element['content']."'");
			$my_element['content'] = $my_modul['template'];
		}
		$element_data['content'] = $my_element['content'];
		if($enter == "ok" && $c_child>0) {
			echo $this->checksubmodul($element_data);
		} else {
			$this->includeExtension($my_element['file'], $element_data);
		}
					$sitecode = str_replace($img,"",$sitecode);
					if($bolShowEditDiv) {
						echo "</div>";
					}
	
				} elseif ($my_element['element'] == "contentblock") {
	
					$my_block = \DB::getQueryRow("SELECT * FROM cms_blocks WHERE block = '".$my_element['file']."'");
	
					if($this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT) {
						echo "<div onDblClick=\"parent.Page.openBlockAdmin('".$my_element['id']."','".$my_block['id']."','".$my_element['number']."','".$page_data['language']."');\" onMouseOver=\"this.style.backgroundColor='#CCDDFF';\" onMouseOut=\"this.style.backgroundColor ='';\">";
					}
					if($my_block['file']) {
						$my_block['content'] = $this->parseBlock($my_block,$my_element['id'],$element_data);
						if(!$my_block['content']) {
							$my_modul = \DB::getQueryRow("SELECT template FROM system_elements WHERE file = '".$my_block['file']."' AND element = 'modul'");
							$my_block['content'] = $my_modul['template'];
						}
						$element_data['content'] = $my_block['content'];
						$element_data['level_id'] = $my_element['level'];
						$element_data['content_id'] = $my_element['id'];
	
						$this->includeExtension($my_block['file'], $element_data);
						
					} else {
						$output = $this->parseBlock($my_block,$my_element['id'],$element_data);
						if($system_data['eval_php'] == "12012001") {
							eval(' ?>' . $output . '<?php ');
						} else {
							echo $output;
						}
					}
	
					if($this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT) {
						echo "</div>";
					}
					$sitecode = str_replace($img,"",$sitecode);
	
				} elseif ($my_element['element'] == "template") {
					$name = $my_element['file'];
					$my_template = \DB::getQueryRow("SELECT * FROM system_elements WHERE file = '$name' AND element = 'template'");
	
					$elemcode = "";
	
					if ($enter == "ok" && $element_data['in_template'] == false) {
						if($_VARS['layer'] <= $my_element['level']) {
							$table_bg_color = "lime";
							$editablediv = "activeeditablediv";
							$handler = ' onkeyup="parent.parent.preload.tEdit.setTableElements(); parent.parent.preload.tEdit.repositionArrows();" onmouseup="parent.parent.preload.tEdit.setTableElements(); parent.parent.preload.tEdit.stopCellResize(false);" onscroll="parent.parent.preload.tEdit.repositionArrows()"';
							$tableId = "edittools_active";
						} else {
							$table_bg_color = "#dededf";
							$editablediv = "editablediv";
							$handler = '';
							$tableId = "edittools_inactive";
						}
						$elemcode .= "
	<table id=\"".$tableId."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
	<tr>
	    <td><img src=/admin/media/edit_left.gif></td>
	    <td nowrap><a href=\"".$_SERVER['PHP_SELF']."?layer=".$my_element['level']."\"><img src=/admin/media/edit_layer.gif border=0 alt=\"Layer wechseln\"></a></td>
	    <td><img src=/admin/media/edit_middle.gif></td>
	    <td nowrap>Layout Vorlage</td>";
			if($_VARS['layer'] <= $my_element['level']) {
				$elemcode .= "<td><img src=/admin/media/edit_middle.gif></td>
	    <td><a href=# onclick=\"if(confirm(unescape('M%F6chten Sie diese Layout Vorlage wirklich l%F6schen%3F'))) parent.save.location.href='/admin/save.html?element_string=$string1&parent_id=$parent_id&action=delete&title=Layout Vorlage&page_id=".$element_data['page_id']."&did=".$my_element['id']."';\"><img src=/admin/media/edit_delete.gif border=0 alt=\"Modul l&ouml;schen\"></a></td>
	    <td><img src=/admin/media/edit_spacer.gif></td>
	    <!-- <td><a href=# onclick=selectmodul(".$element_data['page_id'].",".$element_data['id'].")><img src=/admin/media/edit_modul.gif border=0 alt=\"Modulauswahl\"></a></td> -->
	    <td><img src=/admin/media/edit_spacer.gif></td>
	    <td><a href=# onclick=\"window.open('/admin/template.html?page_id=".$element_data['page_id']."&element_id=".$element_data['id']."&action=template','code','status=no,resizable=yes,menubar=no,scrollbars=yes,width=650,height=450');\"><img src=/admin/media/edit_code.gif border=0 alt=\"Code Ansicht\"></a></td>";
			}
			$elemcode .= "<td><img src=/admin/media/edit_right.gif></td>
	</tr>
	</table>
	<div class=\"$editablediv\" style=\"border : 1pt solid $table_bg_color; padding : 1;\" id=\"DIV$string1\">";
	// $elemcode .= "<a href=# onclick=edit(".$element_data['page_id'].",".$element_data['id'].")><img src=/admin/media/edit.gif border=0></a>";
					}
	
					$arr = explode("|",$my_element['content']);
					$i=0;
					while($arr[$i] != "") {
	
						$old_number = $i+1;
						if ($old_number < 10) {
							$old_number = "00".$old_number;
						} elseif ($new_number < 100) {
							$old_number = "0".$old_number;
						}
	
						$new_number = $arr[$i];
						if ($new_number < 10) {
							$new_number = "00".$new_number;
						} elseif ($new_number < 100) {
							$new_number = "0".$new_number;
						}
						$my_template['template'] = str_replace("<#content$old_number#>","<#content$new_number#>",$my_template['template']);
						$i++;
					}
					if(count($arr)<=1 && strlen($my_element['content'])!=0)
						$elemcode .= $my_element['content'];
					else
						$elemcode .= $my_template['template'];
	
					if ($enter == "ok" && $element_data['in_template'] == false) {
						$elemcode .= "</div>\n";
					}
	
					$sitecode = str_replace($img,"",$sitecode);
					if($enter == "ok" && $_VARS['layer'] <= $my_element['level']) {
						while(preg_match("/<#content(.{3})#>/",$elemcode,$regs)) {
							$new_number = $regs[1];
							$temp_number = (int)$new_number;
							$my_regs = \DB::getQueryRow("SELECT level FROM cms_content WHERE page_id = '".$element_data['page_id']."' AND number = ".$temp_number." AND active = 1");
							$elemcode = ereg_replace("<#content$new_number#>","<IMG onClick=\"parent.parent.preload.switchLayer('".$my_regs['level']."');\" src=\"$domain/admin/includes/PHPcontent.php?element_id=$new_number&page_id=".$element_data['page_id']."\" border=0>",$elemcode);
						}
						echo ($elemcode);
					} else {
						$this->checkCode($elemcode);
					}
				} else {
					$sitecode = str_replace($img,"",$sitecode);
				}
			// Blockmodus
			} else {

				$sitecode = str_replace($img,"",$sitecode);
				// cf: Query wurde um die validfrom und -to erweitert
				// TODO: @MK Diese Query wird im Frontend mehrfach aufgerunfen, wobei sich nur 
				// folgende Stelle ändert: AND number = '".$element_data['id']."' - cachen???

				$query = "
					SELECT 
						id, 
						file, 
						access, 
						content, 
						public, 
						number, 
						level, 
						dynamic, 
						`limit`, 
						UNIX_TIMESTAMP(`changed`) `changed` 
					FROM 
						cms_content 
					WHERE 
						".$validquery."
						page_id = ".(int)$element_data['page_id']." AND
						number = ".(int)$element_data['id']." AND
				";

				if($element_data['parent_id'] !== null) {
					$query .= "parent_id = :parent_id AND ";
				} else {
					$query .= "parent_id IS NULL AND ";
				}

				$query .= "
						element = 'block' AND 
						active = '1' 
					ORDER BY 
						level
				";
				$aSql = [
					'parent_id' => $element_data['parent_id']
				];
				$res_blocks = (array)\DB::getQueryRows($query, $aSql);

				$aBlocks 	= array();
				$aFree 		= array();
				$aBlocked 	= array();

				foreach($res_blocks as $my_blocks) {
					// zugriff kontrollieren
					$my_blocks['access'] = \Util::decodeSerializeOrJson($my_blocks['access']);
					$aAccess = array("yes"=>array(),"no"=>array());
					foreach((array)$my_blocks['access'] as $k=>$v) {
						if($v == 1) {
							$aAccess['yes'][$k] = 1;
						} else {
							$aAccess['no'][$k] = 1;
						}
					}
					$aYesAccess = array_intersect_key((array)$aAccess['yes'],(array)$user_data['access']);
					$aNoAccess 	= array_intersect_key((array)$aAccess['no'],(array)$user_data['access']);
					if ( ((count($aAccess['yes']) == 0 || count($aYesAccess)>0) && (count($aAccess['no']) == 0 || count($aNoAccess)==0)) || $user_data['cms']) {
						// Zufall
						if($my_blocks['level'] > 900) {
							$my_blocks['position'] = $my_blocks['level']-900;
							$my_blocks['position'] = 0;
						// Fest
						} elseif($my_blocks['level'] > 800) {
							$my_blocks['position'] = $my_blocks['level']-800;
							$my_blocks['position'] = $my_blocks['position'];
							$aBlocked[$my_blocks['position']] = 1;
						// Normale Sortierung
						} else {
							$my_blocks['position'] = $my_blocks['level'];
						}
						$aBlocks[] = $my_blocks;
					}
				}
				// Anzahl der Bloecke
				$iTotal = count($aBlocks);
				for($i=1;$i<=$iTotal;$i++) {
					if(!isset($aBlocked[$i]))
						$aFree[] = $i;
				}
				$iFree = count($aFree);
				foreach($aBlocks as $key=>$val) {
					if(!$val['position']) {
						srand ((double)microtime()*1000000);
						$iRand = array_rand($aFree);
						$aBlocks[$key]['position'] = $aFree[$iRand];
						unset($aFree[$iRand]);
					}
				}
				usort($aBlocks, array($this, "cmpLevel"));
				$iBlock=1;

				foreach($aBlocks AS $my_blocks) {
					// Anzahl der Bloecke begrenzen
					if(
						$this->oPage->getMode() !== \Cms\Entity\Page::MODE_EDIT && 
						$my_blocks['limit'] > 0 && 
						$my_blocks['limit'] < $iBlock
					) {
						break;
					}

					$oBlock = \Cms\Entity\Block::getRepository()->getByKey($my_blocks['file']);

					// Kein Block gefunden? 2
					if(empty($oBlock)) {
						echo '<!-- Block "'.$my_blocks['file'].'" not found! -->';
						continue;
					}

					$my_block = $oBlock->getData();

					if(\System::d('debugmode')) {
						echo '<!-- Runtime: '.$this->getCurrentRuntime().'-->';
					}

					$bEdit = false;

					if(
						$this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT &&
						strpos($my_block['content'], '#block:content:') === false
					) {
						$bEdit = true;
					}

					if(
						$bEdit === true &&
						$my_block['inpage_editable'] == 0
					) {
						echo "<div onDblClick=\"parent.Page.openBlockAdmin('".$my_blocks['id']."','".$my_block['id']."','".$my_blocks['number']."','".$page_data['language']."');\" onMouseOver=\"this.style.backgroundColor='#CCDDFF';\" onMouseOut=\"this.style.backgroundColor ='';\">";
					}

					// Standardblock für extensions
					if($my_block['block'] == "extension") {
						$strSql = "
									SELECT 
										`id`,  
										`page_id`,
										`data_id`,
										`content`,  
										`public`,  
										`uptodate`,
										UNIX_TIMESTAMP(`changed`) `changed` 
									FROM  
										cms_blockdata  
									WHERE  
										(
											page_id IS NULL OR
											page_id = :page_id
										) AND
										content_id = :intContentId
									";
						$arrTransfer = array();
						$arrTransfer['intContentId'] = (int)$my_blocks['id'];
						$arrTransfer['page_id'] = (int)$this->oPage->id;
						$arrBlockData = \DB::getPreparedQueryData($strSql, $arrTransfer);

						$strField = 'public';
						if(
							$this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT ||
							$this->oPage->getMode() === \Cms\Entity\Page::MODE_PREVIEW	
						) {
							$strField = 'content';
						}
						foreach($arrBlockData as $arrTemp) {
							if($arrTemp['data_id'] == 1) {
								$my_block['file'] =		$arrTemp[$strField];
							} elseif($arrTemp['data_id'] == 2) {
								if(
									$arrTemp['uptodate'] == 0 && 
									!$element_data['in_template']
								) {
									$this->oPage->setUpToDate(false);
								}
								// last change, for smarty caching
								$my_block['changed'] = 	$arrTemp['changed'];
								$my_block['content'] = 	$arrTemp[$strField];
							}
						}

						$intPos=0;
						$sNeedle = '#sub:';
						$blockCode = $my_block['content'];
						while(false !== ($intPos = strpos($blockCode,$sNeedle,$intPos))) {
							$intEnd = strpos($blockCode,'#',$intPos+1);
							$strVar = substr($blockCode, $intPos+strlen($sNeedle), $intEnd-$intPos-strlen($sNeedle));
							$arrInfo = explode(":",$strVar);
							if(count($arrInfo) > 1) {
								if($arrInfo[0] == "extension") {
									$blockCode = str_replace($sNeedle.$strVar."#", "<#content".str_pad($arrInfo[1], 3, "0", STR_PAD_LEFT)."#>", $blockCode);
								}
							}
							$intPos += strlen($sNeedle);
						}
						$my_block['content'] = $blockCode;
					}

					if($my_block['file']) {

						$my_block['content'] = $this->parseBlock($my_block, $my_blocks['id'], $element_data);
						if(!$my_block['content']) {
							$my_modul = \DB::getQueryRow("SELECT template FROM system_elements WHERE file = '".$my_block['file']."' AND element = 'modul'");
							$my_block['content'] = $my_modul['template'];
						}
						$element_data['changed'] = $my_block['changed'];
						$element_data['content'] = $my_block['content'];
						$element_data['level_id'] = $my_blocks['level'];
						$element_data['content_id'] = $my_blocks['id'];
						$element_data['language'] = $page_data['language'];

						$this->includeExtension($my_block['file'], $element_data);

					} else {

						$element_data['level_id'] = $my_blocks['level'];

						// Hier die Statifizierung einbauen
						if($my_blocks['dynamic'] == 0) {

							if(preg_match("/[a-z0-9]{3}/i",$my_blocks['content'])) {
								echo $my_blocks['content'];
							} else {
								$output = $this->parseBlock($my_block, $my_blocks['id'], $element_data);
								\DB::executeQuery("UPDATE cms_content SET content = '".\DB::escapeQueryString($output)."' WHERE id = '".$my_blocks['id']."'");
								echo $output;
							}

						} else {
							
							$output = $this->parseBlock($my_block, $my_blocks['id'], $element_data);
							echo $output;
							
						}
					}

					if(
						$bEdit === true &&
						$my_block['inpage_editable'] == 0
					) {
						echo "</div>";
					}

					if(\System::d('debugmode')) {
						echo '<!-- Runtime: '.$this->getCurrentRuntime().'-->';
					}

					$iBlock++;
				}
				if($this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT) {
					echo "<a href=\"javascript:void(0);\" onClick=\"parent.Page.addBlock('".$element_data['parent_id']."','".$element_data['id']."','".$element_data['level_id']."');return false;\" class=\"adminlink\">".\L10N::t('Block hinzuf&uuml;gen', 'CMS')."</a><span class=\"adminlink\">&nbsp;&nbsp;</span><a href=\"javascript:void(0);\" onClick=\"parent.Page.editArea('".$element_data['parent_id']."','".$element_data['id']."', '".$page_data['language']."');return false;\" class=\"adminlink\">".\L10N::t('Bereich editieren', 'CMS')."</a>";
				}
			}

			echo "<!-- WD:CONTENT:END:".$sPageElement." -->";

			if(\System::d('debugmode')) {
				echo '<!-- Runtime: '.$this->getCurrentRuntime().'-->';
			}

		} // END WHILE LOOP
		echo (substr($sitecode,$pos_old,100000));

		$this->intLevel--;

	}
	
	// end function checkcontent
	
	function cmpLevel ($a, $b) {
		$a = $a['position'];
		$b = $b['position'];
		if ($a == $b) return 0;
		return ($a < $b) ? -1 : 1;
	}
	
	function parseBlock($aBlock, $contentId, $element_data) {
		global $page_data;

		$blockCode = $aBlock['content'];

		$oContent = \Cms\Entity\Content::getInstance($contentId);
		
		$sSql = "
					SELECT
						id,
						page_id,
						data_id,
						content,
						public,
						uptodate
					FROM
						cms_blockdata
					WHERE
						(
							page_id IS NULL OR
							page_id = :page_id
						) AND
						content_id = :content_id 
						";
		$aSql = array(
			'content_id'=>(int)$contentId,
			'page_id'=>(int)$this->oPage->id
		);

		// sprachübergreifende seite - sprachabhängiger inhalt
		if(
			$page_data['localization'] || 
			$page_data['language'] != ''
		) {
			$sSql .= " AND (language = :language OR language = '') ";
			$aSql['language'] = $page_data['language'];
		}
		$sSql .= "
					ORDER BY
						page_id ASC";
		$aItems = \DB::getQueryRows($sSql, $aSql);
		
		$aContent = array();
		foreach((array)$aItems as $aItem) {
			$aContent[$aItem['data_id']] = $aItem;
		}

		$bCheckCode = false;
		
		$blockCode = str_replace("#block:id#", $contentId, $blockCode);
		
		$pos=0;
		$asTagPre = array('#wd:', '#block:');
		foreach($asTagPre as $sNeedle) {

			while(false !== ($pos = strpos($blockCode,$sNeedle,$pos))) {

			    $end = strpos($blockCode,'#',$pos+1);
		    	$var = substr($blockCode, $pos+strlen($sNeedle), $end-$pos-strlen($sNeedle));
				$info = explode(":",$var);
				// Nur Platzhalter ersetzen, zu denen auch Inhalt gefunden wurde.
				if(count($info) > 1) {

					if($info[0] == "content") {

						$sContentPlaceholderNumber = $oContent->id.'-'.(int)$info[1];

						$sBlockContent = '<#content'.$sContentPlaceholderNumber.'#>';
						
						$bCheckCode = true;
						
						$blockCode = str_replace($sNeedle.$var."#", $sBlockContent, $blockCode);

					} elseif(
						$info[0] == "text" ||
						$info[0] == "table" ||
						$info[0] == "image" ||
						$info[0] == "file" ||
						$info[0] == "link" ||
						$info[0] == "script" ||
						$info[0] == "html" ||
						$info[0] == "imgbuilder" ||
						$info[0] == "page"
					) {

						$my_content = $aContent[$info[1]];

						if(
							$this->oPage->getMode() !== \Cms\Entity\Page::MODE_EDIT &&
							$this->oPage->getMode() !== \Cms\Entity\Page::MODE_PREVIEW	
						) {
							$my_content['content'] = $my_content['public'];
						}
						if(
							isset($my_content['uptodate']) && 
							$my_content['uptodate']== 0
						) {
							$this->oPage->setUpToDate(false);
						}

						$my_content['content'] = ($my_content['content']);

						if($info[0] == "text") {
							$my_content['content'] = nl2br($my_content['content']);
							if(isset($_GET['mode']) && $_GET['mode']=='edit' && trim($my_content['content'])=='') $my_content['content']='&nbsp;';
						} elseif($info[0] == "html") {
							if($_GET['mode']=='edit' && trim($my_content['content'])=='') $my_content['content']='&nbsp;';
						} elseif($info[0] == "table") {

							$sTable = self::getBlockContent($blockCode,$var);
							$blockCode = self::replaceBlockContent($blockCode,$var,$sNeedle.$var."#");
							$arrContent = \Util::decodeSerializeOrJson($my_content['content']);
							$arrRows = self::getBlockContentAll($sTable,"row");
							$sRows = "";
							$c=0;
							foreach((array)$arrContent as $row) {
								$buffer = $arrRows[$row[0]][1];
								for($i=1;$i<count($row);$i++) {
									$buffer = str_replace($sNeedle."cell:".$i."#",nl2br($row[$i]),$buffer);
								}
								$sRows .= $buffer;
								$c++;
							}
							$my_content['content'] = \Cms\Service\PageParser::replaceBlockContent($sTable,"content",$sRows);

						} elseif(
							$info[0] == "image" ||
							$info[0] == "file"
						) {

							$sFile = \Util::getDocumentRoot(false).$my_content['content'];
							if(!is_file($sFile)) {
								$sFile = \Util::getDocumentRoot()."storage/public/".$my_content['content'];
							}
							
							$iW = $info[3];
							$iH = $info[4];
							if(
								is_file($sFile) && 
								$iW > 0 && 
								$iH > 0
							) {
								$my_content['content'] = "temp/".$iW."_".$iH."_".str_replace("/","_",$my_content['content']);
								$sDetail = \Util::getDocumentRoot()."media/".$my_content['content'];
								if(!is_file($sDetail)) {
									saveResizeImage($sFile,$sDetail,$iW,$iH);
								}
							} elseif(is_file($sFile)) {
								$sDetail = $sFile;
							}

						} elseif($info[0] == "imgbuilder") {
							$arrInfo[0] = $info[0];
							$arrInfo[1] = $info[3];
							$arrInfo[2] = $my_content['content'];
							// Wenn kein Inhalt, dann Default-Wert
							if($my_content['content'] == "") {
								$arrInfo[2] = $info[4];
							}
							$my_content['content'] = \imgBuilder::doImgBuilder($arrInfo);
						} elseif($info[0] == "page") {
							$oPage = \Cms\Entity\Page::getInstance($my_content['content']);
							$my_content['content'] = $oPage->getLink($page_data['language']);
						} else {
							$my_content['content'] = $my_content['content'];
						}
						
						// Bearbeitung durch InPage-Editor
						if(
							$this->oPage->getMode() === \Cms\Entity\Page::MODE_EDIT &&
							$aBlock['inpage_editable'] == 1
						) {
							$this->oPage->setInpageEditableElements(true);
							$my_content['content'] = '<div class="content-inpage-'.$info[0].'" id="content-inpage-'.$this->oPage->id.'-'.$oContent->id.'-'.(int)$info[1].'-'.$page_data['language'].'">'.$my_content['content'].'</div>';
						}

						$blockCode = str_replace($sNeedle.$var."#", $my_content['content'], $blockCode);

					} else {
						$pos += strlen($sNeedle);
					}
				} else {
					$pos += strlen($sNeedle);
				}
			}
		}
	
		if($bCheckCode === true) {
			ob_start();
			$this->checkCode($blockCode);
			$blockCode = ob_get_clean();
		}
		
		return $blockCode;
	}

	// check for content
	function getcontent() {
		global $domain_name;
		global $enter;
		global $db_data;
		global $domain,$webmaster_email;
		global $page_data;
		global $user_data;

		echo "\n";
	
		$lev = 0;
	
		$my_content = \DB::getQueryRow("SELECT * FROM cms_content WHERE pages = '$site_name' AND language = '$language' AND number = 0 AND level = 0");
		if(!empty($my_content)) {
			
			if ($my_content['element'] == "template") {
				$name = $my_content['content'];
				$my_template = \DB::getQueryRow("SELECT * FROM system_templates WHERE name = '$name'");
				$templatecode = $my_template['template'];
				$this->checkCode($templatecode);
			} else {
				$sitecode = $my_content['content'];
				$this->checkCode($sitecode);
			}
		} else {
			\Util::handleErrorMessage("Diese Seite enth&auml;lt noch keinen Content.");
		}
	
	}
	
	// gibt template der submodule aus
	function checksubmodul($element_data) {
		global $db_data;
		global $user_data, $system_data, $file_data, $page_data;
	
		$sitecode = $element_data['content'];
		$regs = array();
		while(preg_match("/<#content(.{3})#>/",$sitecode,$regs)) {
			$new_number = $regs[1];
			$temp_number = (int)$new_number;
			$my_element = \DB::getQueryRow("SELECT id,file FROM cms_content WHERE page_id = '".$element_data['page_id']."' AND number = '".$temp_number."'");
			$sitecode = ereg_replace("<#content$new_number#>","<IMG onClick=\"parent.parent.preload.openSubmodul('".$my_element['file']."','".$element_data['page_id']."','".$temp_number."','".$my_element['id']."');\" src=\"$domain/admin/includes/PHPcontent.php?element_id=".$new_number."&page_id=".$element_data['page_id']."\" border=0>",$sitecode);
		}
		return $sitecode;
	}

	function getCurrentRuntime() {
		
		$fCurrentTime = microtime(true);
		
		$fRuntime = $fCurrentTime - $this->fStartTime;

		return $fRuntime;
	}

	// template parser
	static public function checkForBlock(&$template,$block_name) {
		if(
			(!(strpos($template,"<#".$block_name."#>")===false))
			&&(!(strpos($template,"<#/".$block_name."#>")===false))
		)
		{
			$len  = strlen($block_name) + 4;
			$pos  = strpos($template,"<#".$block_name."#>");
			$end  = strpos($template,"<#/".$block_name."#>",$pos);
			$code = substr($template, $pos+$len, $end-$pos-$len);
		}
		return $code;
	}

	static public function replaceBlock($template,$block_name,$replace = "") {
		if(
			(!(strpos($template,"<#".$block_name."#>")===false))
			&&(!(strpos($template,"<#/".$block_name."#>")===false))
		)
		{
			$len  = strlen($block_name) + 4;
			$pos  = strpos($template,"<#".$block_name."#>");
			$end  = strpos($template,"<#/".$block_name."#>",$pos);
			$template = substr($template, 0, $pos)  .  $replace  .  substr($template, $end+$len+1);
		}
		return $template;
	}
	
	private function includeExtension($sExtension, $element_data) {
		global $user_data, $page_data, $_VARS, $oRequest;

		$sError = null;

		try {
			
			$aExtensions = Extensions::getList();
			$aExtension = $aExtensions[$sExtension];
			
			if($aExtension === null) {
				$sFile = \Util::getDocumentRoot()."system/extensions/".$sExtension.".mod";
				include($sFile);
			} elseif(isset($aExtension['file'])) {
				include($aExtension['file']);
			} else {
				echo app()->call([(new $aExtension['class']), 'handle']);			
			}

		} catch (\Error $e) {
			$sError = 'Fehler in Erweiterung "'.$sExtension.'": '.$e->getMessage();
		} catch (\Exception $e) {
			$sError = 'Ausnahmesituation in Erweiterung "'.$sExtension.'": '.$e->getMessage();
		}

		if($sError !== null) {

			if(
				\System::d('debugmode') == 2 &&
				$e instanceof \Throwable
			) {
				$sError .= ' ('.$e->getFile().':'.$e->getLine().', Trace '.$e->getTraceAsString().')';
			}

			$oLog = \Log::getLogger('cms');
			$oLog->error($sError, [$e]);

			\Util::reportError($sError);
			
			echo $sError;
		}

	}

	static public function findCorrelatingEnd(&$sTemplate,$sBlockName, $iStartPos=0)
	{
		$iPos = $iStartPos;
		$iEnd = $iStartPos;
		while($iPos<=$iEnd && $iPos!==false && $iEnd!==false)
		{
			$iPos  = strpos($sTemplate,"<#".$sBlockName."#>",$iPos+4);
			$iEnd = strpos($sTemplate,"<#/".$sBlockName."#>",$iEnd+5);
		}
		return $iEnd;
	}

	static public function checkforblock_clean(&$template,$block_name) {
		// beruecksichtigt Verschachtelung von gleichnahmigen Bloecken in einader
		if(
			(!(strpos($template,"<#".$block_name."#>")===false))
			&&(!(strpos($template,"<#/".$block_name."#>")===false))
		)
		{
			$len  = strlen($block_name) + 4;
			$pos  = strpos($template,"<#".$block_name."#>");
			$end  = self::findCorrelatingEnd($template, $block_name, $pos);
			$code = substr($template, $pos+$len, $end-$pos-$len);
		}
		return $code;
	}


	static public function replaceblock_clean($template,$block_name,$replace = "") {
	#	echo "\n<!--\n".$template."\n####\n".$replace."\n>>>>>>\n".$block_name."\n>>>>>>\n";

		// beruecksichtigt Verschachtelung von gleichnahmigen Bloecken in einader
		if(
			(!(strpos($template,"<#".$block_name."#>")===false))
			&&(!(strpos($template,"<#/".$block_name."#>")===false))
		)
		{
			$len  = strlen($block_name) + 4;
			$pos  = strpos($template,"<#".$block_name."#>");
			$end  = self::findCorrelatingEnd($template,$block_name,$pos);
			$template = substr($template, 0, $pos)  .  $replace  .  substr($template, $end+$len+1);
		}
	#	echo $template."\n-->\n";
		return $template;
	}



	static public function getBlockContent($template,$block_name) {
		if(strstr($template,$block_name)) {
			$len  = strlen($block_name) + 5;
			$pos  = strpos($template,"#wd:".$block_name."#");
			$end  = strpos($template,"#/wd:".$block_name."#",$pos);
			$code = substr($template, $pos+$len, $end-$pos-$len);
		}
		return $code;
	}

	static public function getBlockContentAll($template,$block_name) {
		$offset = 0;
		$arrMatches = array();
		while(strpos($template,"#wd:".$block_name,$offset)) {
			$len  = strlen($block_name) + 5;
			$pos1 = strpos($template,"#wd:".$block_name,$offset);
			$pos  = strpos($template,"#",$pos1+1);
			$id   = substr($template,$pos1+$len,$pos-$pos1-$len);
			$arrId = explode(":",$id);
			$end  = strpos($template,"#/wd:".$block_name,$pos);
			$arrMatches[$arrId[0]] = array($arrId[1],substr($template, $pos+1, $end-$pos-1));
			$offset = $end;
		}
		return $arrMatches;
	}

	static public function replaceBlockContent($template,$block_name,$replace = "") {
		if(!(strpos($template,$block_name)===false)) {
			$len  = strlen($block_name) + 5;
			$pos  = strpos($template,"#wd:".$block_name);
			$end  = strpos($template,"#/wd:".$block_name,$pos);
			$end  = strpos($template,"#",$end+1);
			$template = substr($template, 0, $pos)  .  $replace  .  substr($template, $end+1);
		}
		return $template;
	}

	public function insertStats() {
		global $session_data, $user_data;

		$time = $this->getCurrentRuntime();

		// wenn der user nicht im editmode ist dann in die statistik aufnehmen

		if (!array_key_exists('error',$session_data)){
			$session_data['error'] = false;
		}

		if(
			$this->oPage->stats == 1 && 
			empty($user_data['cms']) && 
			empty($session_data['error'])
		) {

			if($user_data['cookie'] == false) {
				$user_data['session'] = "";
			}

			if (!array_key_exists('HTTP_REFERER',$_SERVER)){
				$_SERVER['HTTP_REFERER'] = "";
			}

			$oSession = \Core\Handler\SessionHandler::getInstance();

			$aData = array(	
				'session'=>(string)$oSession->getId(),
				'referer'=>(string)$_SERVER['HTTP_REFERER'],
				'request_uri'=>(string)$_SERVER['REQUEST_URI'], 
				'host'=>(string)$_SERVER['HTTP_HOST'],
				'agent'=>(string)$_SERVER['HTTP_USER_AGENT'],
				'site_id'=>(int)$this->oPage->site_id,
				'page_id'=>(int)$this->oPage->id,
				'duration'=>(float)$time,
				'queries'=>(int)\DB::getDefaultConnection()->getQueryCount()
			);
			
			// execute hook
			\System::wd()->executeHook('stats_insert', $aData);

			if(\System::d('stats_userinfo')) {
				
				$aData['idUser'] = (int)$user_data['id'];
				$aData['idTable'] = (int)$user_data['idTable'];
				$aData['ip'] = (string)$_SERVER['REMOTE_ADDR'];

			}

			$iStatsId = \DB::insertData('cms_stats', $aData);
			
			// execute hook
			\System::wd()->executeHook('stats_after_insert', $iStatsId);

		}

		$session_data['time'] = $time;

	}
	
}
