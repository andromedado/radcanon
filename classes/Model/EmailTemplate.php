<?php

class ModelEmailTemplate extends Model {
	const TYPE_BASIC = '0';
	const TYPE_APPOINTMENT = '1';
	
	protected $et_id;
	protected $adid = 0;
	protected $type = 0;
	protected $inactive = NULL;
	protected $name = '';
	protected $subject = '';
	protected $body = '';
	
	protected $dbFields = array(
		'et_id',
		'adid',
		'type',
		'inactive',
		'name',
		'subject',
		'body',
	);
	protected $tplVars = array(
		'Patient.surveylink' => 'Survey Link',
		'Patient.fname' => 'Patient First Name',
		'Patient.lname' => 'Patient Last Name',
		'Physician.name' => 'Physician',
		'Office.name' => 'Medical Office',
	);
	protected $requiredFields = array(
		'name' => 'You must name the template',
		'subject' => 'The template must have a subject',
		'body' => 'The template may not be empty',
	);
	protected $varDelimiter = '~';
	protected $whatIAm = 'Email Template';
	protected $table = 'email_templates';
	protected $idCol = 'et_id';
	protected static $WhatIAm = 'Email Template';
	protected static $Table = 'email_templates';
	protected static $IdCol = 'et_id';
	protected static $AllData = array();
	
	public function load () {
		parent::load();
		if (!$this->valid) {
			$src = 'https://' . SITE_HOST . APP_SUB_DIR . '/images/header_700.jpg';
			$this->body = <<<EOT
<table border="0" cellspacing="5" cellpadding="0">
	<tbody>
		<tr>
			<td>
				<img src="{$src}" alt="UCI HealthCare" border="0" width="700" height="101" />
			</td>
		</tr>
		<tr>
			<td>
				<p></p>
			</td>
		</tr>
	</tbody>
</table>
EOT;
		}
	}
	
	public function getVarDelimiter () {
		return $this->varDelimiter;
	}
	
	public function getVars () {
		return $this->tplVars;
	}
	
	public function isAppointmentRequired () {
		return $this->type == self::TYPE_APPOINTMENT;
	}
	
	public function translate ($str, ModelPatient $P) {
		$m = NULL;
		foreach ($this->tplVars as $var => $label) {
			if (strpos($str, $this->varDelimiter . $var . $this->varDelimiter) !== false) {
				list($c, $attr) = explode('.', $var);
				if ($c === 'Patient') {
					$O = $P;
				} else {
					$m = 'get' . $c;
					$O = $P->$m();
				}
				$str = str_replace($this->varDelimiter . $var . $this->varDelimiter, $O->$attr, $str);
			}
		}
		return $str;
	}
	
}

/*
CREATE TABLE email_templates (
 et_id INT IDENTITY NOT NULL PRIMARY KEY,
 adid INT NOT NULL,
 type SMALLINT NOT NULL,
 inactive SMALLINT NULL,
 name VARCHAR(30) NOT NULL,
 subject VARCHAR(60) NOT NULL,
 body TEXT NOT NULL );
 */

?>