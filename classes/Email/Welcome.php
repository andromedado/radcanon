<?php

class EmailWelcome extends Email
{
    const SUBJECT = 'New User Account';
	/** @var ModelUser $User */
	protected $User = NULL;
	protected $firstPassword;

	public function __construct(ModelUser $U, $firstPassword) {
		if (!$U->isValid()) throw new ExceptionBase('Invalid User');
		$this->User = $U;
		$this->firstPassword = $firstPassword;
        $this->subject = self::SUBJECT;
		parent::__construct($U->email, self::SUBJECT);
	}
	
	protected function load() {
		Html::n('h1', '', 'You now have a user account with ', $this->body);
		$ul = Html::n('ul', '', '', $this->body);
		$ul->li('Login URL: ' . 'http://' . SITE_HOST . FilterRoutes::buildUrl(array('AuthUser', 'login')));
		$ul->li('Username: ' . $this->to);
		$ul->li('Password: ' . $this->firstPassword);
	}
	
}

