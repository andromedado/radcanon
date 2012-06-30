<?php

class EmailWelcome extends Email {
	/** @var ModelUser $User */
	protected $User = NULL;
	protected $firstPassword;
	protected $subject = 'New User Account';
	
	public function __construct(ModelUser $U, $firstPassword) {
		if (!$U->isValid()) throw new ExceptionBase('Invalid Admin');
		$this->User = $U;
		$this->firstPassword = $firstPassword;
		parent::__construct($U->email, $this->subject);
	}
	
	protected function load() {
		Html::n('h1', '', 'You now have a user account with ', $this->body);
		$ul = Html::n('ul', '', '', $this->body);
		$ul->li('Login URL: ' . 'http://' . SITE_HOST . FilterRoutes::buildUrl(array('Admin', 'login')));
		$ul->li('Username: ' . $this->to);
		$ul->li('Password: ' . $this->firstPassword);
	}
	
}

?>