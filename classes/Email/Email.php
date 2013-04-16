<?php

/**
 * Email Base Class
 * Elaborate wrapper for PHP's `mail`
 * Optionally implements functionality from RadCanon
 * Dependent on PHP >= 4.0.6 and defined constant `MAIL_FROM`
 * @author Shad Downey
 * @version 1.5
 */
class Email {
	const MAIL_FROM = MAIL_FROM;
	
	protected $to;
	protected $from = '';
	protected $subject;
	public $body;
	protected $attachments = array();
	
	protected $Editable = array('to', 'from', 'subject', 'body');
	
	/**
	 * @param String $to
	 * @param String $subject
	 * @param String $body
	 * @param String $from
	 */
	public function __construct (
		$to = '',
		$subject = '',
		$body = '',
		$from = ''
	) {
		$this->to = $to;
		$this->subject = $subject;
		if (class_exists('HtmlC') && !is_a($body, 'HtmlC')) {
			$body = new HtmlC($body);
		}
		$this->body = $body;
		$this->from = $from;
		$this->load();
	}
	
	/**
	 * Perform any class specific loading
	 */
	protected function load()
	{
		
	}
	
	/**
	 * Add an attachment to this email
	 * @param String $name Name of the file
	 * @param String $type MIME Type of the file
	 * @param String $content Contents of the file
	 * @return Email
	 */
	public function addAttachment ($name, $type, $content)
	{
		$this->attachments[] = array('name' => $name, 'type' => $type, 'content' => $content);
		return $this;
	}
	
	public function __set ($var, $val)
	{
		if (in_array($var, $this->Editable)) {
			$this->$var = $val;
		}
	}
	
	public function __get ($var)
	{
		if (isset($this->$var)) return $this->$var;
		return null;
	}
	
	public function send()
	{
		return $this->sendTo($this->to);
	}
	
	/**
	 * Send this e-mail to the given recipient(s)
	 * Optionally pass in an array of recipients to recurse through
	 * @param String|Array $recipient
	 * @return Boolean|Array
	 */
	public function sendTo($recipient)
	{
		if (is_array($recipient)) {
			$r = array();
			foreach ($recipient as $k => $recip) {
				$r[$k] = $this->sendTo($recip);
			}
			return $r;
		}
		$r = self::sendMail($recipient, $this->subject, strval($this->body), $this->from, '', $this->attachments);
		if (class_exists('ModelLog') && ((defined('DEBUG') && DEBUG) || $r !== true)) {
			ModelLog::mkLog('Mail Delivery' . ($r !== true ? ' Failure' : '') . ': ' . json_encode(array($recipient, $this->subject, strval($this->body), $this->from, '', $this->attachements)), 'email', 1);
		}
		return $r;
	}
	
	public static function stripDown ($str)
	{
		$plainBody = preg_replace('/\n/', '', $str);
		$plainBody = preg_replace('/<br\s*\/>/', "\n", $plainBody);
		$plainBody = preg_replace('/&amp;/i', '&', $plainBody);
		$plainBody = preg_replace('/&nbsp;/i', ' ', $plainBody);
		$plainBody = preg_replace("/&#?[a-z0-9]+;/i", "", strip_tags($plainBody));
		return $plainBody;
	}

	/**
	 * Send well-formed E-mail
	 * @param string $to Recipient E-Mail Address
	 * @param string $subject Subject line to use in the E-mail
	 * @param string $htmlBody HTML or plain text body of the e-mail
	 * @param string $from E-mail Address to use in the "FROM/Reply-To" headers
	 * @param string $plainBody Plain text body of e-mail (if particular conventions are desired, rather than Mailer's stripDown method
	 * @param array $attachments Array of attachments in the format array(array('type' => TYPE, 'name' => NAME, 'content' => CONTENT))
	 * @return bool
	 */
	public static function sendMail (
		$to,
		$subject,
		$htmlBody,
		$from = '',
		$plainBody = '',
		array $attachments = null
	) {
		if ($from == '') {
			$from = self::MAIL_FROM;
		}
		if (trim($htmlBody) != '' && $plainBody == '') {
			$plainBody = self::stripDown($htmlBody);
		}
		if (preg_match('/[^\s]<br\s*\/>[^\s]/', $htmlBody)){
			$htmlBody = preg_replace('/<br\s*\/>/', "<br />\n", $htmlBody);
		}
		$Contact_Email = preg_replace('/[\s\S]+<([^>]+)>[\s\S]+/', '$1', $from);

		$mime_boundary = "----=_" . date('Ymd') . rand(100000000000, 1000000000000) . date('_His');
		$nl = "\r\n";
		
		$headers = '';
		$headers .= "Date: " . date('D, j M Y H:i:s O (T)') . $nl; 
		$headers .= 'From: ' . $from . $nl;
		$headers .= 'Reply-To: '.$from . $nl;
		$headers .= "Return-Path: <".$Contact_Email . '>' . $nl;
		$headers .= "MIME-Version: 1.0" . $nl;
		$headers .= 'Content-Type: multipart/' . (empty($attachments) ? 'alternative' : 'mixed') . '; boundary="' . $mime_boundary . '"' . $nl;
		$headers .= "X-Type: html" . $nl;
		$headers .= "Message-ID: <" . floor(array_shift(explode(' ', microtime())) * 1000000) . time() . "@204.232.152.63>" . $nl;
		
		$mail_content = '';
		$mail_content .= "--" . $mime_boundary . $nl;
        if (!empty($attachments)) {
            $mixedBoundary = $mime_boundary;
            $mime_boundary = "----=_" . date('dm_Y') . rand(100000000000, 1000000000000) . date('_His');
            $mail_content .= 'Content-Type: multipart/alternative; boundary="' . $mime_boundary . '"' . $nl . $nl;
            $mail_content .= "--" . $mime_boundary . $nl;
        }
		$mail_content .= "Content-Type: text/plain; charset=\"CHARSET_GOES_HERE\"" . $nl;
		$mail_content .= "Content-Transfer-Encoding: base64" . $nl . $nl;
		$mail_content .= trim(chunk_split(base64_encode($plainBody))) . $nl;
		$mail_content .= "--" . $mime_boundary . $nl;
		$mail_content .= "Content-Type: text/html; charset=\"CHARSET_GOES_HERE\"" . $nl;
		$mail_content .= "Content-Transfer-Encoding: base64" . $nl . $nl;
		$mail_content .= trim(chunk_split(base64_encode($htmlBody))) . $nl;
		$mail_content .= "--" . $mime_boundary;

		if (!empty($attachments)) {
            $mail_content .= '--' . $nl . $nl;
            $mime_boundary = $mixedBoundary;
            $mail_content .= '--' . $mime_boundary;
			foreach ($attachments as $att) {
				$BLARGH = trim(chunk_split(base64_encode($att['content'])));
				$mail_content .= <<<EOT

Content-Type: {$att['type']};name="{$att['name']}"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="{$att['name']}"

{$BLARGH}
--{$mime_boundary}
EOT;
			}
		}
		$mail_content .= "--" . $nl . $nl;
		$mail_content = preg_replace('/CHARSET_GOES_HERE/', mb_detect_encoding($mail_content), $mail_content);
		
		if (!defined('NO_OUTBOUND_EMAIL') || !NO_OUTBOUND_EMAIL) {
			$m = mail($to, $subject, $mail_content, $headers);
		} else {
			$m = true;
		}
		if (defined('DEBUG') && DEBUG && defined('DEBUG_EMAIL_RECIPIENT')) {
			mail(DEBUG_EMAIL_RECIPIENT, '(' . $to . ') ' . $subject, $mail_content, $headers);
		}
		
		return $m;
	}
	
}

