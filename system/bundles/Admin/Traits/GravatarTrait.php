<?php

namespace Admin\Traits;

use Core\Facade\Cache;

trait GravatarTrait {

	public function getGravatar($iSize=150) {

		$oGravatar = new \thomaswelton\GravatarLib\Gravatar();

		$oGravatar->enableSecureImages();
		$oGravatar->setDefaultImage('mm');
		$oGravatar->setAvatarSize($iSize);
		$oGravatar->setMaxRating('pg');

		$sGravatar = $oGravatar->buildGravatarURL($this->email);

		return $sGravatar;
	}

	public function hasGravatar(): bool {

		$gravatar = new \thomaswelton\GravatarLib\Gravatar();
		$hash = $gravatar->getEmailHash($this->email);

		return Cache::remember('gravatar_'.$hash, 60*60*24, function () use ($hash) {
			$str = file_get_contents( 'https://www.gravatar.com/'.$hash.'.php' );
			return $str !== false;
		});

	}

}
