<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 */


/**
 * GUI element that will be displayed as a HTML link.
 *
 * The link will point to the specified webDynamics page.
 */
class GUI_PageLink extends GUI_Link {


	/**
	 * Constructor.
	 *
	 * @param array $aConfig
	 * @return void
	 */
	public function __construct(array $aConfig = array()) {

		// the page if must be specified
		if (!array_key_exists('id', $aConfig)) {
			throw new Exception('Page id not specified.');
		}

		// get the specified page id
		$iPageID = (int)$aConfig['id'];
		unset($aConfig['id']);

		// generate the required sql query
		$sQuery = "
			SELECT
				`language`,
				`path`,
				`file`,
				`title`
			FROM
				`cms_pages`
			WHERE
				`id` = :id AND
				`element` = :type
		";
		$sQueryValues = array(
			'id'   => $iPageID,
			'type' => 'page'
		);

		// execute the sql query
		$oDB     = DB::getDefaultConnection();
		$aResult = $oDB->preparedQueryData($sQuery, $sQueryValues);

		// the result must contain at least one row
		if (count($aResult) < 1) {
			throw new Exception('Page "'.$iPageID.'" not found.');
		}
		$aRow = reset($aResult);

		// generate the url of the specified page
		$aConfig['url'] = '/';
		if (strlen($aRow['language']) > 0) {
			$aConfig['url'] .= (string)$aRow['language'].'/';
		}
		if (strlen($aRow['path']) > 0) {
			$aConfig['url'] .= (string)$aRow['path'];
		}
		if (strlen($aRow['file']) > 0) {
			$aConfig['url'] .= (string)$aRow['file'].'.html';
		}

		// generate the text of the link if required
		if (!array_key_exists('text', $aConfig)) {
			$aConfig['text'] = (string)$aRow['title'];
		}

		// call parent constructor
		parent::__construct($aConfig);

	}


}
