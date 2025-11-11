<?php

namespace Ts\Model;

use Ts\Dto\Commission;

class Price {
	
	public function setPrice(float $price) {
		$this->price = $price;
	}
	
	public function getPrice() {
		return $this->price;
	}
	
	public function setCurrency(\Ext_Thebing_Currency $currency) {
		$this->currency = $currency;
	}
	
	public function setSeason(\Ext_Thebing_Marketing_Saison $season) {
		$this->season = $season;
	}
	
	public function getSeason() {
		return $this->season;
	}

	public function setCommission(Commission $commission) {
		$this->commissionRate = $commission->rate;
		$this->commissionType = $commission->type;

		$this->commission = $commission->calculate((float)$this->price);
	}

	public function getCommission() {
		return $this->commission;
	}

}

