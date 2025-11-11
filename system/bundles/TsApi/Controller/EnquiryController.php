<?php

namespace TsApi\Controller;

use TsApi\Exceptions\ApiError;
use Illuminate\Http\Request;

/**
 * @link https://fideloschoolenquiries.docs.apiary.io/
 */
class EnquiryController extends AbstractController {
	
	protected $handlerClass = \TsApi\Handler\Enquiry::class;
		
}
