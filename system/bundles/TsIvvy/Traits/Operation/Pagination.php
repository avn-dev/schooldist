<?php

namespace TsIvvy\Traits\Operation;

use TsIvvy\Api\Request;

trait Pagination {

	protected $perPage = 10;

	protected $starAt = 0;

	public function setPerPage(int $perPage): void {
		$this->perPage = $perPage;
	}

	public function setStartAt(int $starAt): void {
		$this->starAt = $starAt;
	}

	protected function setPaginationValues(Request $request) {
		$request->set('perPage', $this->perPage);
		$request->set('start', $this->starAt);
	}

}
