<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(isset($oConfig->blog_id)) {

	if(isset($_VARS['entry_id'])) {
		$sFlag = 'one';
	} else {
		$sFlag = 'list';
	}

	$oSmarty = new \Cms\Service\Smarty();

	if(isset($_VARS['task']) && $_VARS['task'] == 'save_comment')
	{
		if($_VARS['name'] == '') { $_VARS['name'] = '---'; }
		if($_VARS['email'] == '') { $_VARS['email'] = '---'; }

		$oComment = new Ext_Blog_BlogComment($_VARS['entry_id']);
		$oComment->__set('name', $_VARS['name']);
		$oComment->__set('email', $_VARS['email']);
		$oComment->__set('comment', $_VARS['comment']);
		$oComment->saveData();
	}

	// ==================================================================================================== // START: A BLOG + ALL ENTRIES -->
	if($sFlag == 'list')
	{
		$oBlog = new Ext_Blog_Blog($oConfig->blog_id);
	
		if(!isset($_VARS['limit'])) {
			$aEntries = $oBlog->getEntriesList(array(0,10));
		} else {
			$aLimit = implode(',', $_VARS['limit']);
			$aEntries = $oBlog->getEntriesList($aLimit);
		}
	
		$i = 0;
		foreach($aEntries as $aEntry)
		{
			$aEntries[$i]['text'] = nl2br($aEntry['text']);

			$oEntry = new Ext_Blog_BlogEntry($oBlog->id , $aEntries[$i]['id']);
			$aComments = $oEntry->getCommentsList();

			$aEntries[$i]['commentsCount'] = count($aComments);

			$sSql = "
				SELECT 
					*
				FROM
					`system_user`
				WHERE
					`id` = :iUserID
			";
			$aSql = array('iUserID' => $aEntries[$i]['user_id']);
			$aMember = DB::getPreparedQueryData($sSql, $aSql);

			$aEntries[$i]['user_name'] = $aMember[0]['firstname'].' '.$aMember[0]['lastname'];
			$i++;
		}

		$oSmarty->assign('aList', $aEntries);
		$oSmarty->assign('oBlog', $oBlog);
	}
	// ==================================================================================================== // END: A BLOG + ALL ENTRIES <--
	
	// ==================================================================================================== // START: A ENTRY + ALL COMMENTS -->
	if($sFlag == 'one')
	{
		$oEntry = new Ext_Blog_BlogEntry($oConfig->blog_id, $_VARS['entry_id']);
		$oEntry->__set('text', nl2br($oEntry->text));

		$sSql = "
				SELECT 
					*
				FROM
					`system_user`
				WHERE
					`id` = :iUserID
			";
		$aSql = array('iUserID' => $oEntry->__get('user_id'));
		$aMember = DB::getPreparedQueryData($sSql, $aSql);

		$aComments = $oEntry->getCommentsList();
	
		$oSmarty->assign('aMember', $aMember);
		$oSmarty->assign('aList', $aComments);
		$oSmarty->assign('oEntry', $oEntry);
	}
	// ==================================================================================================== // END: A ENTRY + ALL COMMENTS <--
	
	$oSmarty->assign('sFlag', $sFlag);
	
	$oSmarty->displayExtension($element_data);
}
?>