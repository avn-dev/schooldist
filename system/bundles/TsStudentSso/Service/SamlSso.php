<?php

namespace TsStudentSso\Service;

use LightSaml\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LightSaml\SamlConstants;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Protocol\Status;
use LightSaml\Binding\BindingFactory;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Protocol\AuthnRequest;
use Illuminate\Foundation\Bus\Dispatchable;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Context\DeserializationContext;
use CodeGreenCreative\SamlIdp\Contracts\SamlContract;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;
use TsStudentSso\Events\Assertion as AssertionEvent;
use CodeGreenCreative\SamlIdp\Exceptions\DestinationMissingException;

use Illuminate\Support\Facades\Storage;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SamlSso extends \CodeGreenCreative\SamlIdp\Jobs\SamlSso {
		
	private $issuer;
    private $certificate;
    private $private_key;
    private $request;
    private $response;
    private $digest_algorithm;

	public function response() {
		
		$access = \Access_Frontend::getInstance();
		$studentLogin = \Ext_TS_Inquiry_Contact_Login::getInstance($access->id);
		$contact = $studentLogin->getContact();

        $this->response = (new Response())
            ->setIssuer(new Issuer($this->issuer))
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($this->destination)
            ->setInResponseTo($this->authn_request->getId());

        $assertion = new Assertion();
        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new Issuer($this->issuer))
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm))
            ->setSubject(
                (new Subject())
                    ->setNameID(
                        new NameID(
                            $contact->id,
                            SamlConstants::NAME_ID_FORMAT_UNSPECIFIED
                        )
                    )
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation())
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData())
                                    ->setInResponseTo($this->authn_request->getId())
                                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                                    ->setRecipient($this->authn_request->getAssertionConsumerServiceURL())
                            )
                    )
            )
            ->setConditions(
                (new Conditions())
                    ->setNotBefore(new \DateTime())
                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                    ->addItem(new AudienceRestriction([$this->authn_request->getIssuer()->getValue()]))
            )
            ->addItem(
                (new AuthnStatement())
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext())->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );

        $attribute_statement = new AttributeStatement();
        event(new AssertionEvent($attribute_statement, $this->guard));
        // Add the attributes to the assertion
        $assertion->addItem($attribute_statement);

        // Encrypt the assertion

        if ($this->encryptAssertion()) {
            $encryptedAssertion = new EncryptedAssertionWriter();
            $encryptedAssertion->encrypt($assertion, KeyHelper::createPublicKey(
                $this->getSpCertificate()
            ));
            $this->response->addEncryptedAssertion($encryptedAssertion);
        } else {
            $this->response->addAssertion($assertion);
        }

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(
                new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm)
            );
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_POST);
    }
	
    /**
     * Check to see if the SP wants to encrypt assertions first
     * If its not set, default to base encryption assertion config
     * Otherwise return true
     *
     * @return boolean
     */
    private function encryptAssertion(): bool {
        return config(
            sprintf('samlidp.sp.%s.encrypt_assertion', $this->getServiceProvider($this->authn_request)),
            config('samlidp.encrypt_assertion', true)
        );
    }
	
    /**
     * [__construct description]
     */
    protected function init()
    {
        $this->issuer = url(config('samlidp.issuer_uri'));
        $this->certificate = $this->getCertificate();
        $this->private_key = $this->getKey();
        $this->digest_algorithm = config('samlidp.digest_algorithm', XMLSecurityDSig::SHA1);
    }

    /**
     * Send a SAML response/request
     *
     * @param  string $binding_type
     * @param  string $as
     * @return string Target URL
     */
    public function send($binding_type)
    {
        // The response will be to the sls URL of the SP
        $bindingFactory = new BindingFactory;
        $binding = $bindingFactory->create($binding_type);
        $messageContext = new MessageContext();
        $messageContext->setMessage($this->response)->asResponse();
        $message = $messageContext->getMessage();
        if (! empty(request()->filled('RelayState'))) {
            $message->setRelayState(request('RelayState'));
        }
        $httpResponse = $binding->send($messageContext);
        // Just return the target URL for proper redirection
        return $httpResponse->getContent();
    }

    /**
     * Get service provider from AuthNRequest
     *
     * @return string
     */
    public function getServiceProvider($request)
    {
        return base64_encode($request->getAssertionConsumerServiceURL());
    }

    /**
     * @return \LightSaml\Credential\X509Certificate
     */
    protected function getCertificate(): X509Certificate
    {
        $certificate = config('samlidp.cert') ?: Storage::disk('samlidp')->get(config('samlidp.certname', 'cert.pem'));

        return (strpos($certificate, 'file://') === 0)
            ? X509Certificate::fromFile($certificate)
            : (new X509Certificate)->loadPem($certificate);
    }

    /**
     * @return \RobRichards\XMLSecLibs\XMLSecurityKey
     */
    protected function getKey(): XMLSecurityKey
    {
        $key = config('samlidp.key') ?: Storage::disk('samlidp')->get(config('samlidp.keyname', 'key.pem'));

        return KeyHelper::createPrivateKey($key, '', strpos($key, 'file://') === 0, XMLSecurityKey::RSA_SHA256);
    }
	
}
