<?php

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

include_once(\Util::getDocumentRoot()."system/extensions/counter/counter.class.php");

////////////////////////////////////////////////////
/////////////// Modul Counter //////////////////////
////////////////////////////////////////////////////
// Dieses Modul zählt die besucheranzahl ///////////
////////////////////////////////////////////////////

$intOffset = $config->offset;

$objCounter = new Counter();

$intVisits = $objCounter->getTotalVisitCount();

$intVisits += $intOffset;

$objSmarty = new \Cms\Service\Smarty();
$objSmarty->assign('intVisits', $intVisits);

$objSmarty->displayExtension($element_data);

?>