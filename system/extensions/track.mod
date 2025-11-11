<?

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$objWebDynamicsDAO = new Cms\Helper\Data;

if(!$oConfig->seperator)
{
	$oConfig->seperator = ' &raquo; ';
}
if(!$oConfig->startlevel)
{
	$oConfig->startlevel = 0;
}

$aMyPage = $objWebDynamicsDAO->getPageData($page_data['id']);

$aFileData = explode('/', $aMyPage["path"]);

$sOutput = $sLink = '';

$iTmp = 0;

$aTree = array();

while(count($aFileData) > $iTmp)
{
	if($iTmp >= $oConfig->startlevel)
	{
		$sSQL = "
			SELECT
				`id`,
				`title`,
				`path`,
				`file`,
				`language`
			FROM `cms_pages`
			WHERE 
				`path` = '".$sLink."' AND
				`file` = 'index' AND
				`site_id` = '".(int)$aMyPage['site_id']."' AND
				(`language` = '".$aMyPage['language']."' OR `language` = '')
			ORDER BY `language` DESC
			LIMIT 1
		";
		$aMyDir = DB::getQueryRow($sSQL);

		if(empty($aMyDir['language']))
		{
			$aMyDir['language'] = $page_data['language'];
		}

		$aMyDir['link'] = '/';

        $oSite = \Cms\Entity\Site::getInstance($system_data['site_id']);
        
		if(!empty($aMyDir['language']) && $oSite->no_language_folder != 1)
		{
			$aMyDir['link'] .= $aMyDir['language'].'/';
		}
		$aMyDir['link'] .= $aMyDir['path'] . $aMyDir['file'] .'.html';

		$aTree[] = $aMyDir;
	}

	$sLink .= $aFileData[$iTmp] . '/';
	$iTmp++;
}

$aTree[] = $aMyPage;

// Manipulate the $aTree
\System::wd()->executeHook('manage_track_tree', $aTree);

$aLast = array_pop($aTree);

// Create the output string
foreach((array)$aTree as $iKey => $aMyDir)
{
	if($oConfig->bLink)
	{
		$sLink = '<a href="';
		$sLink .= $aMyDir['link'];
		$sLink .= '">' . $aMyDir['title'] . '</a>' . $oConfig->seperator;
	}
	else
	{
		$sLink = $aMyDir['title'] . $oConfig->seperator;
	}

	$sOutput .= $sLink;
}

if($oConfig->bLink)
{
	$sOutput .= '<a href="';
	$sOutput .= $aLast['link'];
	$sOutput .= '">' . $aLast['title'] . '</a>';
}
else
{
	$sOutput .= $aLast['title'];
}

echo stripslashes($sOutput);
