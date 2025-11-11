<?php

class visual_backend {

	function executeHook($strHook, &$mixInput) {
		global $_VARS, $arrConfigData, $objWebDynamics;

		switch($strHook) {

			case "sitemap_overview":

				$mixInput['cols']['visual']['title_style'] = 'width: 100px;';
				$mixInput['cols']['visual']['title'] = 'Visual';
				$mixInput['cols']['visual']['content_style'] = '';
				$mixInput['cols']['visual']['content'] = '<a href="/admin/extensions/visual.html?system_site_id={site_id}&amp;system_page_id={page_id}">bearbeiten</a>';
						
				break;
			default:
				break;
		}

	}

}

\System::wd()->addHook('sitemap_overview', 'visual');

?>