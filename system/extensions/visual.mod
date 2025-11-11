<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$iGroupID = (int)$oConfig->group_id;

// globals
$iSiteID 	= $page_data['site_id'];
$iPageID 	= $page_data['id'];
$sPath 		= $page_data['path'];
$bFlag 		= false;

// get all visual elements of actually page
$aVisuals = Ext_Visual_Util::getAllElements($page_data['id'], $iGroupID);

if($aVisuals !== false) {

	if(count($aVisuals) > 1) {
		$iRandomKey = array_rand($aVisuals);
		$aVisual = $aVisuals[$iRandomKey];
	} else {
		$aVisual = $aVisuals[0];
	}

} else {

	do {

		// look if we will break the while
		if($bFlag === true) {
			break;
		}

		// look if there is an visual defined
		$aSql = array(
			'path'		=> $sPath,
			'site_id'	=> $iSiteID
		);

		// trim the last path
		$sPath = substr($sPath, 0, strrpos($sPath, '/'));
		$iPos = strrpos($sPath, '/');
		$sPath = substr($sPath, 0, (int)$iPos);

		$sSql = "
			SELECT
				`id`
			FROM
				`cms_pages`
			WHERE
				`path` = :path AND
				(`file` = 'index' OR `file` = '') AND
				`site_id` = :site_id
		";
		$aSystemPages = DB::getPreparedQueryData($sSql, $aSql);

		// look if there is set an visual
		foreach((array)$aSystemPages as $iKey => $mValue)
		{
			// get all visuals of page
			$aVisuals = Ext_Visual_Util::getAllElements($mValue['id'], $iGroupID);

			if($aVisuals !== false)
			{
				if(count($aVisuals) > 1)
				{
					$iRandomKey = array_rand($aVisuals);
					$aVisual = $aVisuals[$iRandomKey];
				}
				else
				{
					$aVisual = $aVisuals[0];
				}
				$bFlag = true;

				break;
			}
		}

	} while(!empty($sPath));	

	// if there is allready no visual found we will get standart of site_id
	if(!$aVisual)
	{
		$sPathOfStandardVisual = Ext_Visual_Util::getStandardSiteVisual($iSiteID, $iGroupID);
	}

	// if no explicit-visual / upper-explicit-visual and no standard visual..
	if(!$aVisual && $sPathOfStandardVisual === false)
	{
		$sVisual = 'Please set a Visual...';
	}

}

if($sPathOfStandardVisual) {
	$sVisual = '/media/'.$sPathOfStandardVisual;
} else if ($aVisual) {
	$sVisual = '/media/'.$aVisual['visual_path'];
}

// start / assign / show smarty
$oVisualSmarty = new \Cms\Service\Smarty();

$oVisualSmarty->assign('VISUAL', $sVisual);

// unset couse of may next visual
unset($aVisuals, $sVisual, $aVisual, $sPathOfStandardVisual);

echo $oVisualSmarty->displayExtension($element_data, false);
