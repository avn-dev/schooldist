<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Traits\Operation\Pagination;

/**
 * https://developer.ivvy.com/account/get-user-list
 */
class GetAccountUserList extends AbstractOperation {
	use Pagination;

	public function getUri(): string {
		return $this->buildUri('account', 'getUserList');
	}
}
