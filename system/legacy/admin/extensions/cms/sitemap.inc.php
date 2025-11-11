<?
// $showlang
// $showflags
// $structure left,folder,new,move
// $target
// $extended
// $slanguage
// $sfunction
// $intSiteId

global $value_target;

if($intSiteId) {
	$oSite = Cms\Entity\Site::getInstance($intSiteId);
	$system_data['project_name'] = $oSite->name;
} 

if($_VARS['toggleValue']) {
	if($system_data['sitemap_save']) {
		setcookie("cookie_".$_VARS['toggleFolder'],$_VARS['toggleValue'], time() + 99999999, "/");
	} else {
		setcookie("cookie_".$_VARS['toggleFolder'],$_VARS['toggleValue'], 0, "/");
	}
	$_COOKIE["cookie_".$_VARS['toggleFolder']] = $_VARS['toggleValue'];
}

$sParameter = "";
foreach($_VARS as $k=>$v) {
	if($k != "toggleFolder" && $k != "toggleValue")
		$sParameter .= $k."=".$v."&";
}

if (!$target) {
	$value_target = "opener.document.formular.site_pfad";
} else {
	$value_target = $target;
}

if($structure == "left") {
	$link = '" + children[currentIndex].name + "';
	$mainlink = '" + treeData[1].name + "';
} elseif($structure == "folder") {
	$link = '" + children[currentIndex].name + "';
	$mainlink = '<A HREF=\'#\' onclick=\''.$value_target.'.href= \"sitemap_detail.html?site_id='.$intSiteId.'&id=&path_name=&level=" + treeData[1].level1 + "&level_2=" + treeData[1].level2 + "\"\'>" + treeData[1].name + "</a>';
} elseif($structure == "new") {
	$link = '" + children[currentIndex].name + "';
	$mainlink = '" + treeData[1].name + "';
} elseif($structure == "move") {
	$link = '" + children[currentIndex].name + "';
	$mainlink = '" + treeData[1].name + "';
} else {
	$link = '" + children[currentIndex].name + "';
	$mainlink = '" + treeData[1].name + "';
}

if($showflags == "yes") {
	$img = "";
	$languages = explode(" ", $available_languages);
	$i=0;
	while($languages[$i]) {
		$url = "/";
		if($structure == "left") {
			$link = '<A HREF="javascript:;" onclick="'.$value_target.'.href= \'/\'">';
			$url = "/";
		} elseif($structure == "folder") {
			$link = '<A HREF=\\\'#\\\' onclick=\\\''.$value_target.'.href= \"sitemap_detail.html?site_id='.$intSiteId.'&slanguage='.$languages[$i].'&id=&path_name=&level=0&level_2=1\"\\\'>';
		} elseif($structure == "new") {
			$link = '<A HREF=\\\'#\\\' onclick=\\\''.$value_target.'.value=\"'.$my_sitemap_1['path'].'\";\\\'>';
		} elseif($structure == "move") {
			$link = '<A HREF=\\\'javascript:;\\\' onclick=\\\''.$value_target.'.value=\"'.$my_sitemap_1['path'].'\";window.close();'.$sfunction.'();\\\'>';
		} else {
			$link = '<A HREF=\\\'javascript:;\\\' onclick=\\\''.$value_target.'.value=\"'.idtopath($my_sitemap_1['id']).'\";window.close();\\\'>';
		}
		$img .= " $link<img src=\"/admin/media/flag_$languages[$i].gif\" border=0></a>";
		$i++;
	}
	$root_title = $system_data['project_name'].$img;
} else {
	if($structure == "left") {
		$link = '<A HREF="javascript:;" onclick="'.$value_target.'.href= \'/\'">';
		$url = "/";
	} elseif($structure == "folder") {
		$link = '<A HREF="#" onclick="'.$value_target.'.href= \'sitemap_detail.html?site_id='.$intSiteId.'&slanguage='.$slanguage.'&id=&path_name=&level=0&level_2=1\'">';
	} elseif($structure == "new") {
		$link = '<A HREF="#" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';">';
	} elseif($structure == "move") {
		$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';window.close();'.$sfunction.'();">';
	} else {
		$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.idtopath($my_sitemap_1['id']).'\';window.close();">';
	}
	$root_title = $link.$system_data['project_name']."</a>";
}

echo '<span style="white-space: nowrap;"><A href="javascript:;" onclick="parent.content.page.location.href= \'/\';" target=main>&nbsp;<B>'.$root_title.'</A></B></span><br>';

$language_f = "german";
$count_rows = 0;
if($slanguage) {
	$language_string = "AND (language = '$slanguage' OR language = '')";
}

function printSitemap($path_name, $level, $level_2) {
	global $_VARS, $intSiteId, $slanguage, $sParameter,$iTotal,$i_cs,$system_data,$db_data,$extended,$sfunction,$language_string,$value_target,$showflags,$structure,$count_rows,$language_f,$language,$level_close,$db_system,$file_ext,$functions,$status;

	if($structure == "folder" || $structure == "new" || $structure == "move") {
		$sQuery = "SELECT *,COUNT(CONCAT(path,file)) as anzahl FROM cms_pages WHERE site_id = ".$intSiteId." AND (path LIKE '$path_name%' AND file = 'index' AND level = '$level_2') $language_string AND element != 'template' AND file != '' AND active != 2 GROUP BY CONCAT(path,file) ORDER BY position, id ASC";
	} else {
		$sQuery = "SELECT *,COUNT(CONCAT(path,file)) as anzahl FROM cms_pages WHERE site_id = ".$intSiteId." AND ((path LIKE '$path_name%' AND (file = 'index' OR file = '') AND level = '$level_2') OR (path LIKE '$path_name%' AND file != 'index' AND file != '' AND level = '$level')) $language_string AND element != 'template' AND active != 2 GROUP BY CONCAT(path,file) ORDER BY position, id ASC";
	}

	$result_sitemap_1 = (array)DB::getQueryRows($sQuery);
	$count_sitemap = count($result_sitemap_1);
	$i_cs[$level_2] = 1;
	$iTotal[$level_2] = $count_sitemap;

	foreach($result_sitemap_1 as $my_sitemap_1) {

		$oPage = Cms\Entity\Page::getInstance($my_sitemap_1['id']);
		$oPageAccess = new Cms\Helper\Access\Page($oPage);
		
		$bOutput = true;
		
		if($structure == 'left' && $my_sitemap_1['file'] != 'index') {
			$bCheck = $oPageAccess->checkRightInPath('edit_view_pages');
			if(!$bCheck) {
				$bOutput = false;
			}
		}

		if($structure == 'new') {
			if($_VARS['pagetype'] == "category") {
				$bCheck = $oPageAccess->checkRightInPath('edit_add_category');
				if(!$bCheck) {
					$bOutput = false;
				}
			} else {
				$bCheck = $oPageAccess->checkRightInPath('new_page');
				if(!$bCheck) {
					$bOutput = false;
				}
			}
		}

		$my_sitemap_1['title'] = "<span class=\"menue\" id=\"sid_".$my_sitemap_1['id']."\">".$my_sitemap_1['title']."</span>";
		if($showflags == "yes") {
			if($my_sitemap_1['anzahl'] > 1) {
				$res_lang = DB::getQueryRows("SELECT path,id,language,level FROM cms_pages WHERE site_id = ".$intSiteId." AND path = '".$my_sitemap_1['path']."' AND file = '".$my_sitemap_1['file']."' AND element != 'template' AND active != 2");
				foreach($res_lang as $my_lang) {
					if($structure == "left") {
						$link = '<A HREF="javascript:;" onclick="'.$value_target.'.href= \''.idtopath($my_lang['id'], $slanguage).'\'">';
					} elseif($structure == "folder") {
						$link = '<A HREF="#" onclick="'.$value_target.'.href= \'sitemap_detail.html?site_id='.$intSiteId.'&slanguage='.$my_lang['language'].'&id='.$my_lang['id'].'&path_name='.$my_lang['path'].'&level='.$my_lang['level'].'&level_2='.($my_lang['level']+1).'\'">';
					} elseif($structure == "new") {
						$link = '<A HREF="#" onclick="'.$value_target.'.value=\''.$my_lang['path'].'\';">';
					} elseif($structure == "move") {
						$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.$my_lang['path'].'\';window.close();'.$sfunction.'();">';
					} else {
						$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.idtopath($my_lang['id'], $slanguage).'\';window.close();">';
					}
					$img = "$link<img src=\"/admin/media/flag_".$my_lang['language'].".gif\" border=\"0\"></a>";
					$my_sitemap_1['title'] = $my_sitemap_1['title']." ".$img;
				}
			} else {
				if($structure == "left") {
					$link = '<A HREF="javascript:;" onclick="'.$value_target.'.href= \''.idtopath($my_sitemap_1['id'], $slanguage).'\'">';
				} elseif($structure == "folder") {
					$link = '<A HREF="#" onclick="'.$value_target.'.href= \'sitemap_detail.html?site_id='.$intSiteId.'&slanguage='.$my_sitemap_1['language'].'&id='.$my_sitemap_1['id'].'&path_name='.$my_sitemap_1['path'].'&level='.$my_sitemap_1['level'].'&level_2='.($my_sitemap_1['level']+1).'\';">';
				} elseif($structure == "new") {
					$link = '<A HREF="#" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';">';
				} elseif($structure == "move") {
					$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';window.close();'.$sfunction.'();">';
				} else {
					$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.idtopath($my_sitemap_1['id'], $slanguage).'\';window.close();">';
				}
				$img = "$link<img src=\"/admin/media/flag_".$my_sitemap_1['language'].".gif\" alt=\"Hier klicken um die Seite im Hauptfenster zu öffnen.\" border=\"0\"></a>";
				$my_sitemap_1['title'] = $my_sitemap_1['title']." ".$img;
			}
		} else {
			if($structure == "left") {
				$link = '<A HREF="javascript:;" onclick="'.$value_target.'.href=\''.idtopath($my_sitemap_1['id'], $slanguage).'\'">';
			} elseif($structure == "folder") {
				$link = '<A HREF="#" onclick="'.$value_target.'.href=\'sitemap_detail.html?site_id='.$intSiteId.'&slanguage='.$slanguage.'&id='.$my_sitemap_1['id'].'&path_name='.$my_sitemap_1['path'].'&level='.$my_sitemap_1['level'].'&level_2='.($my_sitemap_1['level']+1).'\'">';
			} elseif($structure == "new") {
				$link = '<A HREF="#" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';">';
			} elseif($structure == "move") {
				$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.$my_sitemap_1['path'].'\';window.close();'.$sfunction.'();">';
			} else {
				$link = '<A HREF="javascript:;" onclick="'.$value_target.'.value=\''.idtopath($my_sitemap_1['id'], $slanguage).'\';window.close();">';
			}
			$my_sitemap_1['title'] = $link.$my_sitemap_1['title']."</a>";
		}

		if($count_sitemap == $i_cs[$level_2]) {
			if($my_sitemap_1['file'] == "index") {
				$icon = "icon_folder.gif";
				$image = 5;
			} else {
				$icon = "icon_site.gif";
				$image = 3;
			}
			$level_close[$level] = "yes";
		} else {
			$icon = "icon_folder.gif";
			$image = 1;
		}

		$u = $level+1;
		$v = $level_2+1;

		$path_name = $my_sitemap_1['path'];

		$path_name_2 = $my_sitemap_1['path'];

		if ($my_sitemap_1['file'] == "index") {
			$l = 2;
		} else {
			$l = 1;
		}

		$dir_name = explode("/",$path_name_2);
		$path_name_2 = "";
		$j=0;
		$count_dir = count($dir_name) - $l;
		while($j < $count_dir) {
			if(!strstr($dir_name[$j],".".$file_ext)) {
				$path_name_2 .= $dir_name[$j]."/";
			}
			$j++;
		}

		$count_rows++;

		if(!$path_name_2 && !$path_name) {
			$path_name_2 = "root";
		}

		$detail_level_1 = $level + 1;
		$detail_level_2 = $level_2 + 1;

		if((!isset($_COOKIE['cookie_'.$path_name]) || !$system_data['sitemap_save']) && $system_data['sitemap_start_open']) {
			$_COOKIE['cookie_'.$path_name] = "1";
		}

		if($bOutput) {
			
			if($my_sitemap_1['file']=="index") {
				if(
					$_COOKIE['cookie_'.$path_name] == "1" ||
					!empty($extended)
				) {
					$sIcon = "fa-folder-open-o";
				} else {
					$sIcon  ="fa-folder-o";
				}
				if($my_sitemap_1['active'] == 0 || $my_sitemap_1['menue'] == 0) {
					$sIcon .= " text-muted";
				}

				for($i=1;$i<$level_2;$i++) {
					if($iTotal[$i] > $i_cs[$i]) {
						echo '<IMG height=16 src="/admin/media/img-vert-line-0.gif" width=19 align=textTop>';
					} else {
						echo '<IMG height=16 src="/admin/media/img-blank.gif" width=19 align=textTop>';
					}
				}
				echo '<span style="white-space : nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?'.$sParameter.'toggleFolder='.$path_name.'&toggleValue='.((!empty($extended) || $_COOKIE['cookie_'.$path_name] == 1)?"-1":"1").'"><IMG height=16 src="/admin/media/img-'.((!empty($extended) || $_COOKIE['cookie_'.$path_name] == "1")?"minus":"plus").'-'.(($count_sitemap==$i_cs[$level_2])?"end":"cont").'-0.gif" width=19 border="0" align=textTop></a><i id=iid_'.$my_sitemap_1['id'].' alt="'.$my_sitemap_1['id'].' - '.idtopath($my_sitemap_1['id']).'" title="'.$my_sitemap_1['id'].' - '.idtopath($my_sitemap_1['id']).'" class="fa fa-fw '.$sIcon.'" ></i>&nbsp;'.$my_sitemap_1['title'].'</span><br>';
				echo "\n";
			} else {
				if($my_sitemap_1['level'] == 0) $temp_path_name = "root";
				else $temp_path_name = $path_name;
	
				$uptodate = getPageStatus($my_sitemap_1['id']);
	
				$sIcon = "page_";
				if($my_sitemap_1['indexpage'] == 1)
					$sIcon .= "home_";
				else
					$sIcon .= "normal_";
				if($uptodate == 1)
					$sIcon .= "published";
				else
					$sIcon .= "publish";
	
				if($my_sitemap_1['active'] == 0 || $my_sitemap_1['menue'] == 0)
					$sIcon .= "_inactive";
				if($my_sitemap_1['access'] != "")
					$sIcon .= "_secure";
	
				$sIcon .= ".gif";
				for($i=1;$i<$level_2;$i++) {
					if($iTotal[$i] > $i_cs[$i]) {
						echo '<IMG height=16 src="/admin/media/img-vert-line-0.gif" width=19 align=textTop>';
					} else {
						echo '<IMG height=16 src="/admin/media/img-blank.gif" width=19 align=textTop>';
					}
				}
				echo '<span style="white-space: nowrap;"><IMG height=16 src="/admin/media/img-branch-'.(($count_sitemap==$i_cs[$level_2])?"end":"cont").'-0.gif" width=19 align=textTop><IMG id=iid_'.$my_sitemap_1['id'].' height=16 alt="'.$my_sitemap_1['id'].' - '.idtopath($my_sitemap_1['id']).'" title="'.$my_sitemap_1['id'].' - '.idtopath($my_sitemap_1['id']).'" src="/admin/media/'.$sIcon.'" width=16 align=textTop border=0>&nbsp;'.$my_sitemap_1['title'].'</span><br>';
				echo "\n";
			}
		}

		if(
			(
				$my_sitemap_1['file'] == "index" || 
				$my_sitemap_1['file'] == ""
			) && 
			$my_sitemap_1['level'] == $level_2 && 
			(
				$_COOKIE['cookie_'.$path_name] == "1" ||
				!empty($extended)
			)
		) {
			printSitemap($my_sitemap_1['path'], ($level+1), ($level_2+1));
		}
		$i_cs[$level_2]++;
	}
}

if($structure == "folder" || $structure == "new" || $structure == "move") {
	printSitemap('',0,1);
} else {
	printSitemap('',0,1);
}

if($_REQUEST['debug'] == 1) {
	__pout(DB::getQueryHistory());
}

?>