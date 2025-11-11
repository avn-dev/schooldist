<?php

namespace Tc\Service\Wizard;

use Admin\Components\NavigationComponent;
use Admin\Facades\Admin;
use Admin\Facades\Router;
use Admin\Factory\Content;
use Admin\Http\Resources\RouterActionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class StepRedirect extends Step
{
	public function render(Wizard $wizard, Request $request): Response
	{
		if (empty($redirect = $this->getConfig('redirect'))) {
			throw new \RuntimeException('No redirect url given ['.get_called_class().']');
		}

		$admin = Admin::instance();
		if (str_starts_with($redirect, 'navigation:')) {
			/* @var NavigationComponent $navigation */
			$navigation = $admin->getComponent(NavigationComponent::KEY);
			$node = $navigation->findNodeByKey(Str::after($redirect, 'navigation:'), false);

			if (!$node) {
				throw new \RuntimeException(sprintf('Invalid navigation key given [%s]', Str::after($redirect, 'navigation:')));
			}

			$routerAction = $node['action'];
		} else {
			$routerAction = Router::tab(
				md5($redirect),
				'fa fa-star',
				$this->getConfig('redirect_name', $this->getTitle($wizard)),
				Content::iframe($redirect)
			);
		}

		$templateData = [
			'tabKey' => \Util::generateRandomString(10),
			'routerAction' => (new RouterActionResource($routerAction, $admin))->toArray($request),
			'time' => $this->getConfig('time', 10)
		];

		return $this->view($wizard,'@Tc/wizard/redirect_step', $templateData);
	}
}