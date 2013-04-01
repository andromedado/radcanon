<?php

class EmailPasswordReset extends EmailAdmin {
	protected $subject = 'Password Reset';
	protected $temporaryPassword;
	
	public function __construct(ModelAdmin $Admin, $temporaryPassword) {
		$this->Admin = $Admin;
		$this->temporaryPassword = $temporaryPassword;
		parent::load();
		$this->body = new HtmlC;
		Html::n('h1', '', 'Password Reset:', $this->body);
		Html::n('h2', '', 'A password reset has been requested for your account', $this->body);
		Html::n('h3/i', '', 'If you did not make this request, you may safely ignore this e-mail', $this->body);
		Html::n('p', '', 'Your login is:<br />' . $this->Admin->getEmail(), $this->body);
		Html::n('p', '', 'and your temporary password is:<br />' . $this->temporaryPassword, $this->body);
		Html::n('p', '', 'the temporary password will be valid until used or ' . date('m/d/Y g:ia T', $this->Admin->getTmpPasswordExpires()), $this->body);
		Html::n('a', 'https://' . SITE_HOST . FilterAppRoutes::buildUrl(array('Admin', 'login')), '- ' . APP_NAME, $this->body);
	}
	
}

?>