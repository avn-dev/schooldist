<?php

/**
 * START CMS Stuff
 */
if($user_data['cms'] == true) {

	echo "\n\n<!-- Fidelo Framework Footer -->\n";
	
	if(
		$oPage->getMode() === Cms\Entity\Page::MODE_EDIT &&
		$oPage->hasInpageEditableElements()
	) {
		echo '<script type="text/javascript" src="/tinymce/resource/basic/tinymce.min.js?v='.\System::d('version').'"></script>';
		echo "<script type=\"text/javascript\">
		
			tinymce.init({
				selector: '.content-inpage-text',
				language: 'de',
				inline: true,
				plugins: 'save',
				toolbar: 'undo redo removeformat | save',
				menubar: false,
				save_onsavecallback: function() {
					parent.Page.saveContent(this);
				}
			});
			
			tinymce.init({
				selector: '.content-inpage-html',
				language: '".substr(System::getInterfaceLanguage(), 0, 2)."',
				inline: true,
				plugins:[ 'searchreplace code fullscreen preview table visualblocks visualchars image charmap save',
						 'contextmenu link importcss responsivefilemanager'],
				toolbar1: ' undo redo | searchreplace pastetext visualblocks visualchars link image | preview code fullscreen | table formatselect removeformat | responsivefilemanager ' ,
				toolbar2:'bold italic underline charmap | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | save ',
				menubar: false,
				image_advtab: true ,
				relative_urls: false,
				verify_html: false,
				convert_urls: false,
				remove_script_host: true,
				external_filemanager_path:\"/tinymce/resource/filemanager/\",
				filemanager_title:\"Dateiverwaltung\" ,
				external_plugins: { \"filemanager\" : \"/tinymce/resource/filemanager/plugin.min.js\"},
				save_onsavecallback: function() {
					parent.Page.saveContent(this);
				}
			});
		</script>\n";
	}
	
	echo "<script type=\"text/javascript\">\n";

	if(
		$oPageAccess->checkRightInPath("publish") &&
		(
			$oPage->getMode() === Cms\Entity\Page::MODE_EDIT ||
			$oPage->getMode() === Cms\Entity\Page::MODE_PREVIEW
		)
	) {
		if($oPage->isUpToDate() === false) {
			echo "
if(parent) {
	parent.Page.showPublish();
}\n";
		} else {
			echo "
if(parent) {
	parent.Page.showNoChanges();
}\n";
		}
	} else {
		if($oPage->isUpToDate() === false) {
			echo "
if(parent) {
	parent.Page.showChanges();
}\n";
		} else {
			echo "
if(parent) {
	parent.Page.showNoChanges();
}\n";
		}
	}

	echo "</script>\n";

	if(Access_Backend::checkAccess("edit")) {

		echo "<script type=\"text/javascript\">\n";

?>
if(
	parent && 
	parent.Page
) {
	parent.Page.showActions();
	parent.Page.iPageId	= "<?=$page_data['id']?>";
	parent.Page.sLanguage = '<?=$page_data['language']?>';
	<?php
	if($oPage->getMode() === Cms\Entity\Page::MODE_EDIT) {
		echo "parent.Page.selectTab('edit');";
	} elseif($oPage->getMode() === Cms\Entity\Page::MODE_PREVIEW) {
		echo "parent.Page.selectTab('preview');";
	} elseif($oPage->getMode() === Cms\Entity\Page::MODE_LIVE) {
		echo "parent.Page.selectTab('live');";
	}
	?>

}

<?php

		echo "</script>\n";

	}

}

/**
 * END CMS Stuff
 */
$objPageParser->insertStats();

if($page_data['element'] != "frameset") {

	if(\System::d('debugmode')) {

		if(
			System::d('debugmode') == 2 &&
			isset($oDebugBarRenderer) &&
			$oDebugBarRenderer instanceof \DebugBar\JavascriptRenderer
		) {

			$aQueryHistory = Util::getQueryHistory();

			foreach($aQueryHistory as $aQueryHistoryItem) {
				$oQueryCollector->addQuery($aQueryHistoryItem['query'], $aQueryHistoryItem['duration'], $aQueryHistoryItem['class'], $aQueryHistoryItem['explain']);
			}

			echo $oDebugBarRenderer->render();
		}

	}

	// Individueller Footer
	$sHtmlFooter = '';
	\System::wd()->executeHook('html_footer', $sHtmlFooter);

	echo $sHtmlFooter;

	echo "\n</body>\n";

}

echo "</html>\n";

$_SESSION['history'][] = array('url'=>$_SERVER['PHP_SELF'], 'query'=>$session_data['query_string'], 'page_id'=>$page_data['id']);
$_SESSION['history'] = array_slice($_SESSION['history'], -5);
