<?php

namespace TsMoodle\Service;

use MoodleSDK\Auth\AuthTokenCredential;
use MoodleSDK\Rest\RestApiContext;

abstract class MoodleWebService {
	
	/**
	 * @var \MoodleSDK\Rest\RestApiContext
	 */
	protected $context;
	
	/**
	 * @var \Ext_Thebing_School 
	 */
	protected $school;

	public function __construct(\Ext_Thebing_School $school) 
	{

		$this->school = $school;
		
		$this->context = RestApiContext::instance()
			->setUrl(\System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id, ''))
            ->setCredential(new AuthTokenCredential(\System::d(\TsMoodle\Handler\ExternalApp::KEY_ACCESS_TOKEN.'_'.$school->id, '')));

	}

	/**
	 * @return \Ext_Thebing_School
	 */
	public function getSchool(): \Ext_Thebing_School 
	{
		return $this->school;
	}
	
	/**
	 * @return RestApiContext
	 */
	public function getContext(): RestApiContext 
	{
		return $this->context;
	}
	
}
