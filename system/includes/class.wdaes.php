<?php

class WDAES
{
	/**
	 * Pre-computed multiplicative inverse in GF(2^8)
	 * 
	 * @var array
	 */
	private $aSBox =  array(
		0x63,0x7c,0x77,0x7b,0xf2,0x6b,0x6f,0xc5,0x30,0x01,0x67,0x2b,0xfe,0xd7,0xab,0x76,
		0xca,0x82,0xc9,0x7d,0xfa,0x59,0x47,0xf0,0xad,0xd4,0xa2,0xaf,0x9c,0xa4,0x72,0xc0,
		0xb7,0xfd,0x93,0x26,0x36,0x3f,0xf7,0xcc,0x34,0xa5,0xe5,0xf1,0x71,0xd8,0x31,0x15,
		0x04,0xc7,0x23,0xc3,0x18,0x96,0x05,0x9a,0x07,0x12,0x80,0xe2,0xeb,0x27,0xb2,0x75,
		0x09,0x83,0x2c,0x1a,0x1b,0x6e,0x5a,0xa0,0x52,0x3b,0xd6,0xb3,0x29,0xe3,0x2f,0x84,
		0x53,0xd1,0x00,0xed,0x20,0xfc,0xb1,0x5b,0x6a,0xcb,0xbe,0x39,0x4a,0x4c,0x58,0xcf,
		0xd0,0xef,0xaa,0xfb,0x43,0x4d,0x33,0x85,0x45,0xf9,0x02,0x7f,0x50,0x3c,0x9f,0xa8,
		0x51,0xa3,0x40,0x8f,0x92,0x9d,0x38,0xf5,0xbc,0xb6,0xda,0x21,0x10,0xff,0xf3,0xd2,
		0xcd,0x0c,0x13,0xec,0x5f,0x97,0x44,0x17,0xc4,0xa7,0x7e,0x3d,0x64,0x5d,0x19,0x73,
		0x60,0x81,0x4f,0xdc,0x22,0x2a,0x90,0x88,0x46,0xee,0xb8,0x14,0xde,0x5e,0x0b,0xdb,
		0xe0,0x32,0x3a,0x0a,0x49,0x06,0x24,0x5c,0xc2,0xd3,0xac,0x62,0x91,0x95,0xe4,0x79,
		0xe7,0xc8,0x37,0x6d,0x8d,0xd5,0x4e,0xa9,0x6c,0x56,0xf4,0xea,0x65,0x7a,0xae,0x08,
		0xba,0x78,0x25,0x2e,0x1c,0xa6,0xb4,0xc6,0xe8,0xdd,0x74,0x1f,0x4b,0xbd,0x8b,0x8a,
		0x70,0x3e,0xb5,0x66,0x48,0x03,0xf6,0x0e,0x61,0x35,0x57,0xb9,0x86,0xc1,0x1d,0x9e,
		0xe1,0xf8,0x98,0x11,0x69,0xd9,0x8e,0x94,0x9b,0x1e,0x87,0xe9,0xce,0x55,0x28,0xdf,
		0x8c,0xa1,0x89,0x0d,0xbf,0xe6,0x42,0x68,0x41,0x99,0x2d,0x0f,0xb0,0x54,0xbb,0x16
	);


	/**
	 * The round constants
	 * 
	 * @var array
	 */
	private $aRCon = array(
		array(0x00, 0x00, 0x00, 0x00),
		array(0x01, 0x00, 0x00, 0x00),
		array(0x02, 0x00, 0x00, 0x00),
		array(0x04, 0x00, 0x00, 0x00),
		array(0x08, 0x00, 0x00, 0x00),
		array(0x10, 0x00, 0x00, 0x00),
		array(0x20, 0x00, 0x00, 0x00),
		array(0x40, 0x00, 0x00, 0x00),
		array(0x80, 0x00, 0x00, 0x00),
		array(0x1b, 0x00, 0x00, 0x00),
		array(0x36, 0x00, 0x00, 0x00)
	);

	/* ==================================================================================================== */

	/** 
	 * Decrypt a text encrypted by AES
	 *
	 * @param string $sCipherText
	 * @param string $sPassword
	 * @param int $iBits
	 * @return string
	 */
	public function decrypt($sCipherText, $sPassword, $iBits = 256)
	{
		$iBlockSize = 16; // Block size fixed at 16 bytes

		// Only 128, 192 or 256 are allowed
		if(!($iBits == 128 || $iBits == 192 || $iBits == 256))
		{
			return false;
		}
		$sCipherText = base64_decode($sCipherText);

		$iBytes = $iBits / 8;

		$aBytes = $aKey = $aCounterBlock = $aCipherText = array();

		for($i = 0; $i < $iBytes; $i++)
		{
			$aBytes[$i] = ord(substr($sPassword, $i, 1)) & 0xff;
		}

		$aKey = $this->_cipher($aBytes, $this->_keyExpansion($aBytes));
		$aKey = array_merge($aKey, array_slice($aKey, 0, $iBytes - 16)); // Expand the key to 16/24/32 bytes long

		$sCTRText = substr($sCipherText, 0, 8);
		for($i = 0; $i < 8; $i++)
		{
			$aCounterBlock[$i] = ord(substr($sCTRText, $i, 1));
		}

		$aKeySchedule = $this->_keyExpansion($aKey);

		$iBlockCount = ceil((strlen($sCipherText) - 8) / $iBlockSize);

		$aCT = array();
		for($b = 0; $b < $iBlockCount; $b++)
		{
			$aCT[$b] = substr($sCipherText, 8 + $b * $iBlockSize, 16);
		}
		$sCipherText = $aCT;

		$aPlainText = array();

		for($b = 0; $b < $iBlockCount; $b++)
		{
			for($c = 0; $c < 4; $c++)
			{
				$aCounterBlock[15 - $c] = $this->_urs($b, $c * 8) & 0xff;
			}
			for($c = 0; $c < 4; $c++)
			{
				$aCounterBlock[15 - $c - 4] = $this->_urs(($b + 1) / 0x100000000 - 1, $c * 8) & 0xff;
			}

			$aCipherCNTR = $this->_cipher($aCounterBlock, $aKeySchedule);

			$aPlainTextByte = array();
			for($i = 0; $i < strlen($sCipherText[$b]); $i++)
			{
				$aPlainTextByte[$i] = $aCipherCNTR[$i] ^ ord(substr($sCipherText[$b], $i, 1));
				$aPlainTextByte[$i] = chr($aPlainTextByte[$i]);
			}

			$aPlainText[$b] = implode('', $aPlainTextByte);
		}

		$sPlainText = implode('', $aPlainText);

		return $sPlainText;
	}


	/** 
	 * Encrypt a text by 128, 192 or 256 bits
	 *
	 * @param string $sPlainText
	 * @param string $sPassword
	 * @param int $iBits
	 * @return string
	 */
	public function encrypt($sPlainText, $sPassword, $iBits = 256)
	{
		$iBlockSize = 16; // Block size fixed at 16 bytes

		// Only 128, 192 or 256 are allowed
		if(!($iBits == 128 || $iBits == 192 || $iBits == 256))
		{
			return false;
		}

		$iBytes = $iBits / 8;

		$aBytes = $aKey = $aCounterBlock = $aCipherText = array();

		for($i = 0; $i < $iBytes; $i++)
		{
			$aBytes[$i] = ord(substr($sPassword, $i, 1)) & 0xff;
		}

		$aKey = $this->_cipher($aBytes, $this->_keyExpansion($aBytes));
		$aKey = array_merge($aKey, array_slice($aKey, 0, $iBytes-16)); // Expand the key to 16/24/32 bytes long

		$iNonce		= floor(microtime(true) * 1000);
		$iNonceSec	= floor($iNonce / 1000);
		$iNonceMs	= $iNonce % 1000;

		for($i = 0; $i < 4; $i++)
		{
			$aCounterBlock[$i] = $this->_urs($iNonceSec, $i * 8) & 0xff;
		}
		for($i = 0; $i < 4; $i++)
		{
			$aCounterBlock[$i + 4] = $iNonceMs & 0xff;
		}

		$sCTRText = '';
		for($i = 0; $i < 8; $i++)
		{
			$sCTRText .= chr($aCounterBlock[$i]);
		}

		$aKeySchedule = $this->_keyExpansion($aKey);

		$iBlockCount = ceil(strlen($sPlainText) / $iBlockSize);

		for($b = 0; $b < $iBlockCount; $b++)
		{
			for($c = 0; $c < 4; $c++)
			{
				$aCounterBlock[15 - $c] = $this->_urs($b, $c * 8) & 0xff;
			}
			for($c = 0; $c < 4; $c++)
			{
				$aCounterBlock[15 - $c - 4] = $this->_urs($b / 0x100000000, $c * 8);
			}

			$aCipherCNTR = $this->_cipher($aCounterBlock, $aKeySchedule);

			$iBlockLength = $b < $iBlockCount - 1 ? $iBlockSize : (strlen($sPlainText) - 1) % $iBlockSize + 1;
			$aCipherByte = array();

			for($i = 0; $i < $iBlockLength; $i++)
			{
				$aCipherByte[$i] = $aCipherCNTR[$i] ^ ord(substr($sPlainText, $b * $iBlockSize + $i, 1));
				$aCipherByte[$i] = chr($aCipherByte[$i]);
			}

			$aCipherText[$b] = implode('', $aCipherByte);
		}

		$aCipherText = $sCTRText . implode('', $aCipherText);
		$aCipherText = base64_encode($aCipherText);

		return $aCipherText;
	}

	/* ==================================================================================================== */

	/**
	 * Add round keys
	 * 
	 * @param array $aState
	 * @param array $aW
	 * @param int $iRnd
	 * @param int $iNb
	 * @return array
	 */
	private function _addRoundKey($aState, $aW, $iRnd, $iNb)
	{
		for($r = 0; $r < 4; $r++)
		{
			for($c = 0; $c < $iNb; $c++)
			{
				$aState[$r][$c] ^= $aW[$iRnd * 4 + $c][$r];
			}
		}
	
		return $aState;
	}


	/**
	 * Encrypt 'input' with Rijndael algorithm
	 *
	 * @param array $aInput
	 * @param array $aW
	 * @return array
	 */
	private function _cipher($aInput, $aW)
	{
		$iNb = 4;						// The block size
		$iNr = count($aW) / $iNb - 1;	// The number of rounds: 10/12/14 for 128/192/256-bit keys

		$aState = array();
		for($i = 0; $i < 4 * $iNb; $i++)
		{
			$aState[$i % 4][floor($i / 4)] = $aInput[$i];
		}

		$aState = $this->_addRoundKey($aState, $aW, 0, $iNb);

		for($iRound = 1; $iRound<$iNr; $iRound++)
		{
			$aState = $this->_subBytes($aState, $iNb);
			$aState = $this->_shiftRows($aState, $iNb);
			$aState = $this->_mixColumns($aState);
			$aState = $this->_addRoundKey($aState, $aW, $iRound, $iNb);
		}

		$aState = $this->_subBytes($aState, $iNb);
		$aState = $this->_shiftRows($aState, $iNb);
		$aState = $this->_addRoundKey($aState, $aW, $iNr, $iNb);

		$aOutput = array(4 * $iNb);
		for($i = 0; $i < 4 * $iNb; $i++)
		{
			$aOutput[$i] = $aState[$i % 4][floor($i / 4)];
		}

		return $aOutput;
	}


	/**
	 * Performs key expansion on cipher key to generate a key schedule
	 *
	 * @param array $aKey
	 * @return array
	 */
	private function _keyExpansion($aKey)
	{
		$iNb = 4;					// The block size
		$iNk = count($aKey) / 4;	// The key length: 4/6/8 for 128/192/256-bit keys
		$iNr = $iNk + 6;			// The number of rounds: 10/12/14 for 128/192/256-bit keys

		$aW = $aTemp = array();

		for($i = 0; $i < $iNk; $i++)
		{
			$aR = array($aKey[4 * $i], $aKey[4 * $i + 1], $aKey[4 * $i + 2], $aKey[4 * $i + 3]);
			$aW[$i] = $aR;
		}

		for($i = $iNk; $i < ($iNb * ($iNr + 1)); $i++)
		{
			$aW[$i] = array();
			for($t = 0; $t < 4; $t++)
			{
				$aTemp[$t] = $aW[$i - 1][$t];
			}

			if(($i % $iNk) == 0)
			{
				$aTemp = $this->_subWord($this->_rotWord($aTemp));

				for($t = 0; $t < 4; $t++)
				{
					$aTemp[$t] ^= $this->aRCon[$i / $iNk][$t];
				}
			}
			else if($iNk > 6 && ($i % $iNk) == 4)
			{
				$aTemp = $this->_subWord($aTemp);
			}

			for($t = 0; $t < 4; $t++)
			{
				$aW[$i][$t] = $aW[$i - $iNk][$t] ^ $aTemp[$t];
			}
		}

		return $aW;
	}


	/**
	 * Mix the columns
	 * 
	 * @param array $aS
	 * @return array
	 */
	private function _mixColumns($aS)
	{
		for($c = 0; $c < 4; $c++)
		{
			$a = $b = array(4);
			for($i = 0; $i < 4; $i++)
			{
				$a[$i] = $aS[$i][$c];
				$b[$i] = $aS[$i][$c] & 0x80 ? $aS[$i][$c] << 1 ^ 0x011b : $aS[$i][$c] << 1;
			}
	
			$aS[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3];
			$aS[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3];
			$aS[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3];
			$aS[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3];
		}
	
		return $aS;
	}


	/**
	 * Rotate the words
	 * 
	 * @param array $aW
	 * @return array
	 */
	private function _rotWord($aW)
	{
		$aTmp = $aW[0];
		for($i = 0; $i < 3; $i++)
		{
			$aW[$i] = $aW[$i + 1];
		}
		$aW[3] = $aTmp;

		return $aW;
	}


	/**
	 * Shift the rows
	 * 
	 * @param array $aS
	 * @param int $iNb
	 * @return array
	 */
	private function _shiftRows($aS, $iNb)
	{
		$t = array(4);
		for($r = 1; $r < 4; $r++)
		{
			for($c = 0; $c < 4; $c++)
			{
				$t[$c] = $aS[$r][($c + $r) % $iNb];
			}
			for($c = 0; $c < 4; $c++)
			{
				$aS[$r][$c] = $t[$c];
			}
		}
	
		return $aS; 
	}


	/**
	 * Sub the bytes
	 * 
	 * @param array $aS
	 * @param int $iNb
	 * @return array
	 */
	private function _subBytes($aS, $iNb)
	{
		for($r = 0; $r < 4; $r++)
		{
			for($c = 0; $c < $iNb; $c++)
			{
				$aS[$r][$c] = $this->aSBox[$aS[$r][$c]];
			}
		}
	
		return $aS;
	}


	/**
	 * Return the subwords
	 * 
	 * @param array $aW
	 * @return array
	 */
	private function _subWord($aW)
	{
		for($i = 0; $i < 4; $i++)
		{
			$aW[$i] = $this->aSBox[$aW[$i]];
		}

		return $aW;
	}


	/*
	 * Unsigned right shift
	 *
	 * @param int $iX
	 * @param int $iY
	 * @return int
	 */
	private function _urs($iX, $iY)
	{
		$iX &= 0xffffffff;
		$iY &= 0x1f;

		if($iX & 0x80000000 && $iY > 0)
		{
			$iX = ($iX >> 1) & 0x7fffffff;
			$iX = $iX >> ($iY - 1);
		}
		else
		{
			$iX = ($iX >> $iY);
		}

		return $iX;
	}
}