 
	<h2><?=L10N::t('Frontend', 'CMS')?></h2>
<?
	printTableStart('35%');
	echo "\t\t<input type=\"hidden\" name=\"x_login1\" value=\"1\">\n";
	echo printFormPageSelect("Loginseite", "login1", $oPage->login, $arrPageSelectOptions+array('hint_id'=>22));

	printFormHidden('x_access1', '1');
	$aGroups = CustomerDb\Helper\Functions::getCustomerGroups();
	$aGroups = array_merge(array(''=>L10N::t('keine Zugriffsbeschränkung', 'CMS')), $aGroups);
	$aAccess = Util::decodeSerializeOrJson($oPage->access);
	$aPageAccess = array_keys((array)$aAccess);
	printFormMultiSelect(L10N::t('Kundendatenbank', 'CMS'), 'access1[]', $aGroups, $aPageAccess, 'size="5"', 1, 23);
	printTableEnd();
	
?>
	<h2><?=L10N::t('Backend', 'CMS')?></h2>

	<h3><?=L10N::t('Berechtigungen', 'CMS')?> - <?=L10N::t('Rollen', 'CMS')?></h3>

<?

	$aRoles = SQL_getRoles();	
	// get all edit rights
	$sSql = "SELECT * FROM `system_rights` WHERE `element` = 'edit' AND `right` NOT LIKE '%template%' ORDER BY `description`";
	$aRights = DB::getQueryData($sSql);

	if($oPage->file != 'index') {

		foreach((array)$aRights as $iKey=>$aRight) {
			if(
				$aRight['right'] == 'edit_add_category' ||
				$aRight['right'] == 'new_page'
			) {
				unset($aRights[$iKey]);
			}
		}
		
	}
	
	// save rights
	if(
		isset($_VARS['save_role_rights']) &&
		$_VARS['save_role_rights'] == 1
	) {
		foreach((array)$_VARS['role_rights'] as $iRole=>$aValues) {
			foreach((array)$aValues as $iRight=>$mValue) {

				if($mValue >= 0) {
					$sSql = "
							REPLACE 
								cms_pages_rights 
							SET 
								`page_id` = :page_id,
								`item` = 'role',
								`item_id` = :role_id,
								`right_id` = :right_id,
								`granted` = :granted
							";
					$aSql = array();
					$aSql['page_id'] = (int)$oPage->id;
					$aSql['role_id'] = (int)$iRole;
					$aSql['right_id'] = (int)$iRight;
					$aSql['granted'] = (bool)$mValue;
					DB::executePreparedQuery($sSql, $aSql);
				} else {
					$sSql = "
							DELETE FROM 
								cms_pages_rights 
							WHERE
								`page_id` = :page_id AND
								`item` = 'role' AND
								`item_id` = :role_id AND
								`right_id` = :right_id
							";
					$aSql = array();
					$aSql['page_id'] = (int)$oPage->id;
					$aSql['role_id'] = (int)$iRole;
					$aSql['right_id'] = (int)$iRight;
					DB::executePreparedQuery($sSql, $aSql);
				}
							
			}
		}
	}

	// get page rights
	$sSql = "SELECT * FROM cms_pages_rights WHERE page_id = :page_id AND `item` = 'role'";
	$aSql = array('page_id'=>$oPage->id);
	$aItems = DB::getPreparedQueryData($sSql, $aSql);
	$aPageRights = array();
	foreach((array)$aItems as $aItem) {
		$aPageRights[$aItem['item_id']][$aItem['right_id']] = $aItem['granted'];	
	}

?>
	<input type="hidden" name="save_role_rights" value="1" />
	<table width="100%" cellpadding="4" cellspacing="0" border="0" class="table">
		<tr>
			<td style="width: auto;">&nbsp;</td>
<?
	$iCount = count($aRights);
	$iWidth = round((866 - 120) / $iCount);
	foreach((array)$aRights as $aRight) {
		$aRight['description'] = str_replace('Inhaltspflege &raquo;', '', $aRight['description']);
?>
			<th style="width: <?=$iWidth?>px;"><?=L10N::t($aRight['description'], 'CMS')?></th>
<?
	}
?>
		</tr>
<?
		
	foreach((array)$aRoles as $iRole=>$sRole) {
		$aRoleRights = array();
		$r_res = (array)DB::getQueryRows("SELECT srr.right_id, sr.right FROM system_roles2rights srr JOIN system_rights sr ON srr.right_id = sr.id WHERE role_id = ".(int)$iRole."");
		foreach($r_res as $right) {
			if($right['right'] == 'admin') {
				continue 2;
			}
			$aRoleRights[$right['right']] = 1;
		}

?>
		<tr>
			<th><?=$sRole?></th>
<?
		foreach((array)$aRights as $aRight) {
?>
			
<?
			if($aRoleRights[$aRight['right']] == 1) {
				
				$bRight = $oPageAccess->checkRoleRightInPath($aRight['right'], $iRole);
				if($bRight) {
					$sColor = '#66e275';
				} else {
					$sColor = '#ff7a73';
				}
				
				$sYes = '';
				$sNo = '';
				if(isset($aPageRights[$iRole][$aRight['id']])) {
					if($aPageRights[$iRole][$aRight['id']] == 1) {
						$sYes = 'selected="selected"';	
					} else {
						$sNo = 'selected="selected"';
					}
				}
?>
			<td style="text-align: center; background-color: <?=$sColor?>;">
				<select name="role_rights[<?=$iRole?>][<?=$aRight['id']?>]">
					<option value="-1"></option>
					<option value="1" <?=$sYes?>><?=L10N::t('Ja')?></option>
					<option value="0" <?=$sNo?>><?=L10N::t('Nein')?></option>
				</select>

<?
			} else {
?>
			<td style="text-align: center;">
				&nbsp;
<?
			}
?>
			</td>
<?
		}
?>
		</tr>
<?
	}
?>
	</table>

	<h3><?=L10N::t('Berechtigungen', 'CMS')?> - <?=L10N::t('Benutzer', 'CMS')?></h3>

<?

	$sSql = "SELECT * FROM system_user WHERE active = 1 ORDER BY lastname, firstname";
	$aItems = DB::getQueryData($sSql);
	$aUsers = array();
	$aUsersComplete = array();
	foreach((array)$aItems as $aItem) {
		$aUsers[$aItem['id']] = $aItem['lastname'].', '.$aItem['firstname'];	
		$aUsersComplete[$aItem['id']] = $aItem;
	}

	// save rights
	if(
		isset($_VARS['save_user_rights']) &&
		$_VARS['save_user_rights'] == 1
	) {
		foreach((array)$_VARS['user_rights'] as $iUser=>$aValues) {
			foreach((array)$aValues as $iRight=>$mValue) {

				if($mValue >= 0) {
					$sSql = "
							REPLACE 
								cms_pages_rights 
							SET 
								`page_id` = :page_id,
								`item` = 'user',
								`item_id` = :user_id,
								`right_id` = :right_id,
								`granted` = :granted
							";
					$aSql = array();
					$aSql['page_id'] = (int)$oPage->id;
					$aSql['user_id'] = (int)$iUser;
					$aSql['right_id'] = (int)$iRight;
					$aSql['granted'] = (bool)$mValue;
					DB::executePreparedQuery($sSql, $aSql);
				} else {
					$sSql = "
							DELETE FROM 
								cms_pages_rights 
							WHERE
								`page_id` = :page_id AND
								`item` = 'user' AND
								`item_id` = :user_id AND
								`right_id` = :right_id
							";
					$aSql = array();
					$aSql['page_id'] = (int)$oPage->id;
					$aSql['user_id'] = (int)$iUser;
					$aSql['right_id'] = (int)$iRight;
					DB::executePreparedQuery($sSql, $aSql);
				}
							
			}
		}
	}

	// get page rights
	$sSql = "SELECT * FROM cms_pages_rights WHERE page_id = :page_id AND `item` = 'user'";
	$aSql = array('page_id'=>$oPage->id);
	$aItems = DB::getPreparedQueryData($sSql, $aSql);
	$aPageRights = array();
	foreach((array)$aItems as $aItem) {
		$aPageRights[$aItem['item_id']][$aItem['right_id']] = $aItem['granted'];	
	}

?>
	<input type="hidden" name="save_user_rights" value="1" />
	<table width="100%" cellpadding="4" cellspacing="0" border="0" class="table">
		<tr>
			<td style="width: auto;">&nbsp;</td>
<?
	$iCount = count($aRights);
	$iWidth = round((866 - 120) / $iCount);
	foreach((array)$aRights as $aRight) {
		$aRight['description'] = str_replace('Inhaltspflege &raquo;', '', $aRight['description']);
?>
			<th style="width: <?=$iWidth?>px;"><?=L10N::t($aRight['description'], 'CMS')?></th>
<?
	}
?>
		</tr>
<?

	foreach((array)$aUsers as $iUser=>$sUser) {

		$oUser = User::getInstance($iUser);

		$aUserRights = $oUser->getRights();

		if($aUserRights['admin'] == 1) {
			continue;
		}

?>
		<tr>
			<th><?=$sUser?></th>
<?
		foreach((array)$aRights as $aRight) {

			if($aUserRights[$aRight['right']] == 1) {
				
				$bRight = checkRightInPath($aRight['right'], $oPage->id, $iUser);
				if($bRight) {
					$sColor = '#66e275';
				} else {
					$sColor = '#ff7a73';
				}
				
				$sYes = '';
				$sNo = '';
				if(isset($aPageRights[$iUser][$aRight['id']])) {
					if($aPageRights[$iUser][$aRight['id']] == 1) {
						$sYes = 'selected="selected"';	
					} else {
						$sNo = 'selected="selected"';
					}
				}
?>
			<td style="text-align: center; background-color: <?=$sColor?>;">
				<select name="user_rights[<?=$iUser?>][<?=$aRight['id']?>]">
					<option value="-1"></option>
					<option value="1" <?=$sYes?>><?=L10N::t('Ja')?></option>
					<option value="0" <?=$sNo?>><?=L10N::t('Nein')?></option>
				</select>

<?
			} else {
?>
			<td style="text-align: center;">
				&nbsp;
<?
			}
?>
			</td>
<?
		}
?>
		</tr>
<?
	}
?>
	</table>
	
	<p class="note"><?=L10N::t('Es erscheinen nur Einträge, die nicht das Recht "Administration" haben!', 'CMS')?></p>
	