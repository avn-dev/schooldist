<?

/**
 * Rivest / Shamir / Adelman (RSA) compatibility in PHP to
 * crypt, decrypt or significate the inputs
 */

class WDRSA
{
	/**
	 * The prime numbers
	 * 
	 * @var array
	 */
	public $aPrimes = array(); 
	
	
	/**
	 * Scale
	 * 
	 * @var int 
	 */
	protected $_iScale = 0;

		/**
	 * The constructor
	 */
	public function __construct()
	{
		// Get the primes array
		$oPrimes = new WDRSAPrimes();

		$this->aPrimes = $oPrimes->aPrimes;
	}

	/* =============================================================================== PUBLIC FUNCTIONS === */

	/**
	 * Decrypt the text on M = X^D (mod N)
	 *
	 * @param string $sCoded
	 * @param int $iPrivateKey
	 * @param int $iSharedKey
	 * @return string
	 */
	public function decrypt($sCoded, $iPrivateKey, $iSharedKey)
	{
		$sCoded		= split(' ', $sCoded);
		$sMessage	= '';
		$iMax		= count($sCoded);

		for($i = 0; $i < $iMax; $i++)
		{
			$sCode = bcpowmod($sCoded[$i], $iPrivateKey, $iSharedKey, $this->_iScale);

			while(bccomp($sCode, '0', $this->_iScale) != 0)
			{
				$iASCII		= bcmod($sCode, '256', $this->_iScale);
				$sCode		= bcdiv($sCode, '256', 0);
				$sMessage	.= chr($iASCII);
			}
		}

		return $sMessage;
	}


	/**
	 * Encrypt the text on X = M^E (mod N)
	 *
	 * @param string $sText
	 * @param int $iPublicKey
	 * @param int $iSharedKey
	 * @param int $iRounds
	 * @return string
	 */
	public function encrypt($sText, $iPublicKey, $iSharedKey)
	{
		$sCoded		= '';
		$iRounds	= 5;
		$iMax		= strlen($sText);
		$iPackets	= ceil($iMax / $iRounds);

		for($i = 0; $i < $iPackets; $i++)
		{
			$sPacket	= substr($sText, $i * $iRounds, $iRounds);
			$sCode		= '0';

			for($j = 0; $j < $iRounds; $j++)
			{
				if(isset($sPacket[$j]))
				{
					$sCode = bcadd($sCode, bcmul(ord($sPacket[$j]), bcpow('256', $j, $this->_iScale), $this->_iScale), $this->_iScale);
				}
			}

			$sCode	 = bcpowmod($sCode, $iPublicKey, $iSharedKey, $this->_iScale);
			$sCoded .= $sCode.' ';
		}

		return trim($sCoded);
	}


	/**
	 * Generate keys for encode / decode of text
	 *
	 * @return array with keys:
	 * 		Public key pair is	: PUBLIC and SHARED
	 * 		Private key pair is	: PRIVATE and SHARED
	 */
	public function generateKeys()
	{
		$aKeys	= array();
		$iMax	= count($this->aPrimes);

		$aKeys['P'] = (string)$this->aPrimes[mt_rand(0, $iMax)];

		while(!isset($aKeys['Q']) || $aKeys['P'] == $aKeys['Q'])
		{
			$aKeys['Q'] = (string)$this->aPrimes[mt_rand(0, $iMax)];
		}

		$aKeys['SHARED'] = bcmul($aKeys['P'], $aKeys['Q'], $this->_iScale);
		$aKeys['M'] = bcmul(bcsub($aKeys['P'], 1, $this->_iScale), bcsub($aKeys['Q'], 1,$this->_iScale), $this->_iScale);

		// Get public key
		$aKeys['PUBLIC'] = $this->_getE($aKeys['M']);

		// Get private key
		$aKeys['PRIVATE'] = $this->_getD($aKeys['PUBLIC'], $aKeys['M']);

		return $aKeys;
	}

	/* =========================== DIGITAL SIGNATURES === */

	/**
	 * Check the significance of input (message or file) by signature
	 *
	 * @param string $sInput
	 * @param string $sSignature
	 * @param int $iPrivateKey
	 * @param int $iSharedKey
	 * @param bool $bIsFile
	 * @return bool
	 */
	public function prove($sInput, $sSignature, $iPrivateKey, $iSharedKey, $bIsFile = false)
	{
		$sMessageDigest = $this->decrypt($sSignature, $iPrivateKey, $iSharedKey);

		if
		(
			(!$bIsFile && $sMessageDigest == md5($sInput))
				||
			($bIsFile && $sMessageDigest == md5_file($sInput))
		)
		{
			return true;
		}

		return false;
	}


	/**
	 * Create a signature for an input (message or file)
	 *
	 * @param string $sInput
	 * @param int $iPublicKey
	 * @param int $iSharedKey
	 * @param bool $bIsFile
	 * @return string
	 */
	public function signate($sInput, $iPublicKey, $iSharedKey, $bIsFile = false)
	{
		if($bIsFile)
		{
			$sMessageDigest = md5_file($sInput);
		}
		else
		{
			$sMessageDigest = md5($sInput);
		}

		// Create the signature
		$sSignature = $this->encrypt($sMessageDigest, $iPublicKey, $iSharedKey);

		return $sSignature;
	}

	/* ============================================================================== PRIVATE FUNCTIONS === */

	/**
	 * Calculate the private key
	 *
	 * @param int $iPublicKey
	 * @param int $iM : The totient (p - 1) * ( q - 1)
	 * @return int
	 */
	private function _getD($iPublicKey, $iM)
	{
		$u1 = '1';
		$u2 = '0';
		$u3 = (string)$iM;
		$v1 = '0';
		$v2 = '1';
		$v3 = (string)$iPublicKey;

		while(bccomp($v3, 0, $this->_iScale) != 0)
		{
			$qq = bcdiv($u3, $v3, $this->_iScale);
			$t1 = bcsub($u1, bcmul($qq, $v1, $this->_iScale), $this->_iScale);
			$t2 = bcsub($u2, bcmul($qq, $v2, $this->_iScale), $this->_iScale);
			$t3 = bcsub($u3, bcmul($qq, $v3, $this->_iScale), $this->_iScale);
			$u1 = $v1;
			$u2 = $v2;
			$u3 = $v3;
			$v1 = $t1;
			$v2 = $t2;
			$v3 = $t3;
			$z  = '1';
		}

		$uu = $u1;
		$vv = $u2;

		if(bccomp($vv, 0, $this->_iScale) == -1)
		{
			$iPrivateKey = bcadd($vv, $iM, $this->_iScale);
		}
		else
		{
			$iPrivateKey = $vv;
		}

		return $iPrivateKey;
	}


	/**
	 * Calculate the GCD (Greatest Common Divisor) for 2 numbers
	 *
	 * @param int $iX
	 * @param int $iY
	 * @return int
	 */
	private function _getGCD($iX, $iY)
	{
		while(bccomp($iX, 0, $this->_iScale) != 0)
		{
			$iZ = bcsub($iY, bcmul($iX, bcdiv($iY, $iX, 0), $this->_iScale), $this->_iScale);
			$iY = $iX;
			$iX = $iZ;
		}

		return $iY;
	}


	/**
	 * Calculate the public key
	 *
	 * @param int $iM : The totient (p - 1) * ( q - 1)
	 * @return int
	 */
	private function _getE($iM)
	{
		$iE = '3';

		if(bccomp($this->_getGCD($iE, $iM), '1', $this->_iScale) != 0)
		{
			$iE		= '5';
			$step	= '2';

			while(bccomp($this->_getGCD($iE, $iM), '1', $this->_iScale) != 0)
			{
				$iE = bcadd($iE, $step, $this->_iScale);

				if($step == '2')
				{
					$step = '4';
				}
				else
				{
					$step = '2';
				}
			}
		}

		return $iE;
	}
}