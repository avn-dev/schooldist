<?php

namespace FideloSoftware\Verifactu;

use Exception;

/**
 *
 */
class Certificate
{

	/**
	 * @var string
	 */
	private string $certificatePath = '';

	/**
	 * @var string
	 */
	private string $certificatePassphrase = '';

	/**
	 * @var string
	 */
	private string $certificateType = '';

	/**
	 * @return string
	 */
	public function getCertificatePath(): string
	{
		return $this->certificatePath;
	}

	/**
	 * @return string
	 */
	public function getCertificatePassphrase(): string
	{
		return $this->certificatePassphrase;
	}

	/**
	 * @return string
	 */
	public function getCertificateType(): string
	{
		return $this->certificateType;
	}

	/**
	 * @param string $certificatePath
	 * @param string $certificatePassphrase
	 * @return $this
	 */
	public function setPEMCertificate(string $certificatePath, string $certificatePassphrase = ''): Certificate
	{
		$this->certificatePath = $certificatePath;
		$this->certificatePassphrase = $certificatePassphrase;
		$this->certificateType = 'PEM';
		return $this;
	}

	/**
	 * @param string $certificatePath
	 * @param string $certificatePassphrase
	 * @return $this
	 */
	public function setP12Certificate(string $certificatePath, string $certificatePassphrase = ''): Certificate
	{
		$this->certificatePath = $certificatePath;
		$this->certificatePassphrase = $certificatePassphrase;
		$this->certificateType = 'P12';
		return $this;
	}

	/**
	 * @param string $certificatePath
	 * @param string $certificatePassphrase
	 * @param string $outputPemFilePath
	 * @return $this
	 * @throws Exception
	 */
	public function createPEMFromP12(string $certificatePath, string $certificatePassphrase = '', string $outputPemFilePath = ''): Certificate
	{
		$p12Content = file_get_contents($certificatePath);
		if ($p12Content === false) {
			throw new Exception('Unable to read the .p12 file: ' . $certificatePath);
		}
		$certs = [];
		if (!openssl_pkcs12_read($p12Content, $certs, $certificatePassphrase)) {
			throw new Exception('Unable to parse the .p12 file. Check if the passphrase is correct.');
		}
		$pemContent = $certs['cert'] . PHP_EOL . $certs['pkey'];
		if (isset($certs['ca']) && is_array($certs['ca'])) {
			foreach ($certs['ca'] as $ca) {
				$pemContent .= PHP_EOL . $ca;
			}
		}
		if (empty($outputPemFilePath)) {
			$outputPemFilePath = tempnam(sys_get_temp_dir(), 'cert_');
			if ($outputPemFilePath === false) {
				throw new Exception('Unable to create a temporary file.');
			}
		}
		if (file_put_contents($outputPemFilePath, $pemContent) === false) {
			throw new Exception('Unable to write the .pem file: ' . $outputPemFilePath);
		}
		$this->certificatePath = $outputPemFilePath;
		$this->certificatePassphrase = $certificatePassphrase;
		$this->certificateType = 'PEM';
		return $this;
	}
}
