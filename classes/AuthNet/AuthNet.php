<?php

class AuthNet
{
	const REQUEST_URL = "https://api.authorize.net/xml/v1/request.api";
	protected static $default_login_id = NULL;
	protected static $default_transaction_key = NULL;
	protected $login_id = NULL;
	protected $transaction_key = NULL;
	protected $publicErrorCodes = array(
		'aim' => array(
			'2' => 'The transaction has been declined',
			'41' => 'The transaction has been declined',
			'44' => 'The transaction has been declined',
			'45' => 'The transaction has been declined',
			'3' => 'The transaction has been declined',
			'4' => 'The transaction has been declined',
			'6' => 'Invalid Card Number Submitted',
			'37' => 'Invalid Card Number Submitted',
			'7' => 'Invalid Card Expiration Submitted',
			'8' => 'Card has Expired',
			'11' => 'Information Submitted is a duplicate of an existing order',
			'17' => 'The merchant does not accept this type of credit card',
			'28' => 'The merchant does not accept this type of credit card',
			'19' => 'An error occurred during processing. Please try again in 5 minutes.',
			'20' => 'An error occurred during processing. Please try again in 5 minutes.',
			'21' => 'An error occurred during processing. Please try again in 5 minutes.',
			'22' => 'An error occurred during processing. Please try again in 5 minutes.',
			'23' => 'An error occurred during processing. Please try again in 5 minutes.',
			'25' => 'An error occurred during processing. Please try again in 5 minutes.',
			'26' => 'An error occurred during processing. Please try again in 5 minutes.',
			'27' => 'The transaction resulted in an AVS mismatch. The address provided does not match billing address of cardholder.',
			'33' => 'A required field was left blank',
		),
		'cim' => array(
			"E00039" => 'Information Submitted is a duplicate of an existing order',
			"E00003" => 'Invalid Information Provided [%s]',
			"E00013" => 'Invalid Information Provided [%s]',
			"E00015" => 'Invalid Information Provided [%s]',
			"E00016" => 'Invalid Information Provided [%s]',
			"E00041" => 'Required Field Missing',
			"E00014" => 'Required Field Missing',
			"E00027" => 'Transaction Failed: Card Declined',
		),
		'default' => 'The system has experienced a problem [%s]. Please Try Again Later',
	);
	
	public function getPublicError ($code, $type = 'aim')
	{
		if (isset($this->publicErrorCodes[$type])) {
			if (!isset($this->publicErrorCodes[$type][$code])) {
				if (isset($this->publicErrorCodes[$type]['default'])) {
					$err = $this->publicErrorCodes[$type]['default'];
				} else {
					$err = $this->publicErrorCodes['default'];
				}
			} else {
				$err = $this->publicErrorCodes[$type][$code];
			}
		} else {
			$err = $this->publicErrorCodes['default'];
		}
		return sprintf($err, $code);
	}
	
	/**
	 * Set the Default Authorization Info for all subclasses
	 */
	public static function setAuthorizationInfo ($login_id, $transaction_key)
	{
		self::$default_login_id = $login_id;
		self::$default_transaction_key = $transaction_key;
	}
	
	public function overrideAuthorizationInfo ($login_id = NULL, $transaction_key = NULL)
	{
		$this->login_id = $login_id;
		$this->transaction_key = $transaction_key;
	}
	
}

