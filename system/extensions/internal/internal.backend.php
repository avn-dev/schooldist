<?php

class internal_backend {

	function executeHook($strHook, & $mixInput) {
		
		switch ($strHook) {

			case 'git_deployment_post_hook':

				if(class_exists('Ext_Internal_Update')) {
					$oUpdate = Ext_Internal_Update::getInstance();
					$oUpdate->saveCommittedFiles($mixInput);
				}

				break;

			case 'git_deployment_commit_info':

				if(class_exists('Ext_Internal_Update')) {
					Ext_Internal_Update::reformatCommitAuthor($mixInput);
				}

				break;
		
			case "navigation_top":
				
				$mixInput[202] = [];
				$mixInput[202]['name'] = 'deployment';
				$mixInput[202]['right'] = 'admin';
				$mixInput[202]['title'] = 'Deployment';
				$mixInput[202]['icon'] = 'fa-git';				
				$mixInput[202]['extension'] = 1;	
				$mixInput[202]['type'] = 'url';
				$mixInput[202]['url'] = '/admin/extensions/internal/update.html';				
				$mixInput[202]['key'] = 'internal.deployment';

				break;
			default :
				break;
		}
	}
}

\System::wd()->addHook('git_deployment_post_hook', 'internal');
\System::wd()->addHook('git_deployment_commit_info', 'internal');
\System::wd()->addHook('navigation_top', 'internal');