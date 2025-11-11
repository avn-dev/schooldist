<?php

namespace TsStudentSso\Controller;

use Illuminate\Support\Facades\Storage;

class SamlController extends \Illuminate\Routing\Controller {

	public function metadata(\MVC_Request $request) {
		
		$saml = new \TsStudentSso\Service\Saml;
		$storagePath = $saml->getCertPath(false);
		
		$settingsHelper = new \TsStudentSso\Helper\Settings;
		$settings = $settingsHelper->get();
		
		$keyFile = sprintf('%s/%s', $storagePath, 'key.pem');
        $certFile = sprintf('%s/%s', $storagePath, 'cert.pem');

        $cert = Storage::disk('local')->get($certFile);

        $cert = preg_replace('/^\W+\w+\s+\w+\W+\s(.*)\s+\W+.*$/s', '$1', trim($cert));
        $cert = str_replace(PHP_EOL, "", $cert);

        return response(view('metadata', compact('cert', 'settings')), 200, [
            'Content-Type' => 'application/xml',
        ]);
	}
	
	public function login(\MVC_Request $request) {
		
		if(!$request->filled('SAMLRequest')) {
			abort(403);
		}
		
		$variables = [
			'SAMLRequest' => $request->get('SAMLRequest'),
			'RelayState' => $request->get('RelayState'),
			'session' => \Core\Handler\SessionHandler::getInstance()
		];

		return response(view('login', $variables));
	}
	
	public function executeLogin(\MVC_Request $request) {
		
		$db = \DB::getDefaultConnection();
		$access = new \Access_Frontend($db);
		
		$access->checkManualLogin([
			'customer_login_1' => $request->get('customer_login_1'),
			'customer_login_3' => $request->get('customer_login_3'),
			'table_number' => 77,
			'loginmodul' => 1
		]);

		// Prüfen, ob die Eingabedaten richtig sind
		$success = $access->executeLogin();

		if($success) {
			abort(response(\TsStudentSso\Service\SamlSso::dispatchNow('web')), 302);
		} else {
			
			$session = \Core\Handler\SessionHandler::getInstance();
			$session->getFlashBag()->add('error', $access->getLastErrorCode());
			
			return login();#redirect(\Core\Helper\Routing::generateUrl('TsStudentSso.login'));
		}
	}
	
	public function sso(\MVC_Request $request) {
		
	}
	
	public function slo(\MVC_Request $request) {
		
		$settingsHelper = new \TsStudentSso\Helper\Settings;
		$settings = $settingsHelper->get();
		
		$slo_redirect = $request->session()->get('saml.slo_redirect');
        if (!$slo_redirect) {
            $this->setSloRedirect($request);
            $slo_redirect = $request->session()->get('saml.slo_redirect');
        }

        if (null === $request->session()->get('saml.slo')) {
            $request->session()->put('saml.slo', []);
        }

        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        foreach ($settings as $key => $sp) {
            // Check if the service provider supports SLO
            if (! empty($sp['logout']) && ! in_array($key, $request->session()->get('saml.slo', []))) {
                // Push this SP onto the saml slo array
                $request->session()->push('saml.slo', $key);
                return redirect(SamlSlo::dispatchNow($sp));
            }
        }

        if (config('samlidp.logout_after_slo')) {
            auth()->logout();
            $request->session()->invalidate();
        }

        $request->session()->forget('saml.slo');
        $request->session()->forget('saml.slo_redirect');

        return redirect($slo_redirect);
	}
	
}
