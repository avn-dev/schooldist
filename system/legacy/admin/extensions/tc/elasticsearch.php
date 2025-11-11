<?php

$_GET['rs'] = 1;
require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess('control');

Admin_Html::loadAdminHeader();
//ini_set('display_errors',1);
//error_reporting(E_ALL ^ E_NOTICE);
?>

<div class="divHeader">
	<h1>Elasticsearch Tool</h1>
</div>

<div style="padding: 30px; width: 1200px;">

<?php

$sLicense = System::d('license');
$aIndexes = Ext_TC_System_Tools::getToolsService()->getIndexes();
$aPossibleIndices = [];

foreach(array_keys($aIndexes) as $sIndex) {
	$sIndexName = \ElasticaAdapter\Facade\Elastica::buildIndexName($sIndex);
	$aPossibleIndices[$sIndexName] = $sIndex;
}

$aPossibleIndices = \Ext_TC_Util::addEmptyItem($aPossibleIndices);

$aElasticSearchConfig = \ElasticaAdapter\Adapter\Client::getConnectionConfig();

$oClient = new \ElasticaAdapter\Adapter\Client();
$oStatus = $oClient->getStatus();
$aIndicesData = $oStatus->getData();

echo '<h2>All indexes</h2>';
echo '<table class="table" cellpadding="4" cellspacing="0" style="width: 100%;">';
echo '<tr><th>Index</th><th>Types</th><th>Count</th><th>Stack</th></tr>';

$aIndices = array('');
$aIndicesTypes = array();
foreach($aIndicesData['indices'] as $sIndexName=>$aIndex) {

	if(!array_key_exists($sIndexName, $aPossibleIndices)) {
		continue;
	}

	$aIndices[$sIndexName] = $sIndexName;

	$oIndex = $oClient->getIndex($sIndexName);
	$oStats = $oIndex->getStats();
	$aMapping = $oIndex->getMapping();
	$aStats = $oStats->getData();

	$aTypes = (array)array_keys($aMapping);
	
	$aIndicesTypes[$sIndexName] = array(0=>'');
	foreach($aTypes as $sType) {
		$aIndicesTypes[$sIndexName][$sType] = $sType;
	}

	$sTypes = implode(', ', $aTypes);
	if($sTypes == $sIndexName) {
		$sTypes = '';
	}

	$aStack = (array)\Ext_Gui2_Index_Stack::getFromDB($aPossibleIndices[$sIndexName]);
	
	echo '<tr><td>'.$aPossibleIndices[$sIndexName].' ('.$sIndexName.')</td><td>'.$sTypes.'</td><td align="right">'.$aStats['_all']['total']['docs']['count'].'</td><td align="right">'.count((array)$aStack[$aPossibleIndices[$sIndexName]]).'</td></tr>';

}

echo '</table>';

$iResults = null;
if(
	!empty($_VARS['index']) &&
	!empty($_VARS['type'])
) {

	$oIndex = $oClient->getIndex($_VARS['index']);
	//$oStats = $oIndex->getStats();
	//$oStatus = $oIndex->getStatus();
	$aMapping = $oIndex->getMapping();
	
	$oType = $oIndex->getType($_VARS['type']);

	$oQuery = new \Elastica\Query();
	
	if(!empty($_VARS['search'])) {
		$aQuery = array(
			'query' => array(
				'query_string' => array(
					'query' => $_VARS['search'],
				)
			)
		);
		$oQuery->setRawQuery($aQuery);
	}
	
	$oQuery->setStoredFields(array('*'));

    $oResultSet = $oType->search($oQuery, 5);

	$iResults = $oType->count();
	$aMapping = $aMapping[$_VARS['type']];

}

echo '<br><br>';

echo '<form method="post">';
printTableStart();
printFormSelect('Index', 'index', $aPossibleIndices, $_VARS['index'] ?? 0, 'onchange="this.form.submit();"');
if(!empty($_VARS['index'])) {
	printFormSelect('Type', 'type', $aIndicesTypes[$_VARS['index']], $_VARS['type'] ?? 0, 'onchange="this.form.submit();"');
}
printFormText('Search', 'search', $_VARS['search'] ?? '');
printFormCheckbox('Show mapping', 'show_mapping', '1', !empty($_VARS['show_mapping']));
if(isset($oResultSet)) {
	echo '<tr><th>Results</th><td>'.$oResultSet->getTotalHits().'</td></tr>';
}
printTableEnd();
echo '</form>';

if(isset($oResultSet)) {
	echo '<h2>Results</h2>';
	if(0 && extension_loaded('xdebug')) {
		var_dump($oResultSet->getResults());
	} else {
		__out($oResultSet->getResults());
	}

	if(!empty($_VARS['show_mapping'])) {
		echo '<h2>Mapping</h2>';
		if(0 && extension_loaded('xdebug')) {
			var_dump($aMapping);
		} else {
			__out($aMapping);
		}
	}
}

?>

</div>

<?
Admin_Html::loadAdminFooter();
?>