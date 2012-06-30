<?php

class ControllerAdmin extends ControllerAdminOnly {
	protected $permittedMethods = array(
		'login', 'forgotPassword', 'resetSent', 'notFound',
	);
	/** @var ModelAdmin $model */
	public $model = NULL;
	protected $modelName = 'ModelAdmin';
	
	public function edit ($id = NULL) {
		if (!is_a($this->user, 'SuperAdmin')) return $this->notFound();
		$A = AdminFactory::build($id);
		if (!$A->isValid()) return $this->notFound();
		if ($this->request->isPost()) {
			$vars = $this->request->post();
			$vars['level'] = $this->request->post('superAdmin', 'no') === 'yes' ? ModelSuperAdmin::MIN_VALID_LEVEL : 0;
			$A->safeUpdateVars($vars);
			$this->response->redirectTo(array('Admin', 'manage', $A->id));
			$_SESSION['msg'] = 'Admin Updated';
			return;
		}
		return array('html' => $this->renderForm($A));
	}
	
	public function create () {
		if (!is_a($this->user, 'SuperAdmin')) return $this->notFound();
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			$this->model->loadAs(0);
			$vars = $this->request->post();
			$vars['level'] = $this->request->post('superAdmin', 'no') === 'yes' ? ModelSuperAdmin::MIN_VALID_LEVEL : 0;
			try {
				$this->model->safeCreateWithVars($vars);
				$this->response->redirectTo(array('Admin', 'manage', $this->model->id));
				$_SESSION['msg'] = 'Admin Created';
				return;
			} catch (ExceptionValidation $e) {
				$errors->add($e->getMessage());
			}
		}
		return new HtmlC($errors, $this->renderForm(NULL, $this->request));
	}
	
	public function delete ($id = NULL) {
		if (is_a($this->user, 'SuperAdmin')) {
			if ($this->request->isPost()) {
				$A = AdminFactory::build($id);
				if ($A->isValid()) {
					if ($A->id == $this->user->id) {
						return Html::n('h1', '', 'You may not delete yourself');
					}
					$A->delete();
					$_SESSION['msg'] = 'Admin Deleted';
					$this->response->redirectTo(array('Admin', 'manage'));
					return;
				}
			}
		}
		return $this->notFound();
	}
	
	protected function renderForm (ModelAdmin $A = NULL, Request $req = NULL) {
		if (is_null($A)) $A = new ModelAdmin;
		$v = $A->isValid() ? 'edit' : 'create';
		$AF = new AppForm(ucfirst($v) . ' Admin:', array('Admin', $v, $A->id), array(), $req);
		$AF->addField('First Name:', Html::n('input', 't:text;n:fname;c:not_blank', $A->fname));
		$AF->addField('Last Name:', Html::n('input', 't:text;n:lname;c:not_blank', $A->lname));
		$AF->addField('E-mail Address:', Html::n('input', 't:text;n:email;c:not_blank email_required', $A->email));
		$AF->addField('Super Admin:', UtilsHtm::ynSelect('n:superAdmin', is_a($A, 'ModelSuperAdmin') ? 'yes' : 'no'));
		return $AF;
	}
	
	public function manage ($id = NULL) {
		if (!is_a($this->user, 'SuperAdmin')) return $this->notFound();
		
		$c = new HtmlC;
		if (!is_null($id)) {
			$A = AdminFactory::build($id);
			if (!$A->isValid()) return $this->notFound();
			Html::n('h1', '', 'Admin: ' . $A->getName(), $c);
			$T = Html::n('table', 'c:autoT', '', $c);
			$T->pair('Email Address:', $A->email);
			$T->pair('Super Admin:', is_a($A, 'ModelSuperAdmin') ? 'Yes' : 'No');
			$AF = '';
			if ($A->id != $this->user->id) {
				$AF = new AppForm('', array('Admin', 'delete', $A->id));
				$AF->form->onsubmit("return confirm('Permanently Delete this Admin?')");
				$AF->submit->value('Delete Admin');
			}
			Html::n('table', 'c:fw', '', $c)->nTR()->
				td(Html::n('input', 't:button', 'Edit Admin')->onclick("App.prompt('/Admin/edit/{$A->id}')"), 'align:center')->
				td($AF, 'align:center');
		} else {
			Html::n('h1', '', 'Manage Admins:', $c);
			$Admins = ModelAdmin::findAll(array(
				'sort' => array(
					'level DESC',
				),
			), array('AdminFactory', 'build'));
			if (empty($Admins)) {
				Html::n('h2/i', '', 'None Found', $c);
			} else {
				$T = Html::n('table', 'c:autoT', '', $c);
				$header = true;
				foreach ($Admins as $Ad) {
					$T->append($Ad->getRow($header));
					$header = false;
				}
			}
			Html::n('h2', 'c:ac', AppLink::newLink('Create New Admin', array('Admin', 'create')), $c);
		}
		return $c;
	}
	
	public function changePassword ($modifier = '') {
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			try {
				$this->user->updatePassword($this->request->post());
				$_SESSION['msg'] = 'Password Updated';
				$this->response->redirectTo(array('Patient', 'search'));
			} catch (ExceptionValidation $e) {
				$errors->add($e->getMessage());
			}
		}
		
		$c = new HtmlC($errors, $F = new AppForm(($modifier === 'must' ? 'You Must ' : '') . 'Update Your Password', array('Admin', 'changePassword')));
		$F->addField('New Password', Html::n('input', 't:password;n:new_pass;c:not_blank'));
		$F->addField('Confirm New Password', Html::n('input', 't:password;n:confirm_new_pass;c:not_blank'));
		return $c;
	}
	
	public function logout () {
		if (is_a($this->user, 'Admin')) {
			Admin::removeLoginId();
			session_destroy();
			session_start();
			$_SESSION['msg'] = 'You have logged out';
		}
		$this->response->redirectTo(array('Admin', 'login'));
		return;
	}
	
	public function resetSent () {
		if (empty($_SESSION['resetSentTo'])) {
			$this->response->redirectTo(array('Admin'));
			return;
		}
		$email = $_SESSION['resetSentTo'];
		$_SESSION['resetSentTo'] = NULL;
		return Html::n('h2', '', 'Password reset e-mail sent to ' . $email);
	}
	
	public function forgotPassword () {
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			$_SESSION['resetSentTo'] = NULL;
			$email = $this->request->post('email', '');
			if (!empty($email) && UtilsString::isEmail($email)) {
				$Admin = ModelAdmin::findOne(array(
					'fields' => array(
						'email' => $email,
					),
				));
				if ($Admin->isValid()) {
					$tmpPass = $Admin->setTemporaryPassword();
					if ($tmpPass !== false) {
						$Em = new EmailPasswordReset($Admin, $tmpPass);
						$Em->send();
					}
				}
				$_SESSION['resetSentTo'] = $email;
				$this->response->redirectTo(array('Admin', 'resetSent'));
				return;
			}
			$errors->add('Invalid E-Mail provided');
		}
		
		$c = new HtmlC($errors, $F = new AppForm('Trigger Password Reset:'));
		$F->addField('E-mail Address:', 'email');
		return $c;
	}
	
	public function login ($modifier = NULL) {
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			$MA = ModelAdmin::findOne(array(
				'fields' => array(
					'email' => $this->request->post('email'),
				),
			));
			if ($MA->isValid() && $MA->passwordAcceptable($this->request->post('pwd'))) {
				$MA->recordLogin();
				$_SESSION['msg'] = 'You have logged in';
				$dest = array('Patient', 'search');
				if (!empty($_SESSION['afterLogin'])) {
					$dest = $_SESSION['afterLogin'];
					$_SESSION['afterLogin'] = '';
				}
				$this->response->redirectTo($dest);
				return;
			}
			$this->response->cancelRedirect(Response::TYPE_HTML);
			$errors->add('Login Failed');
		}
		
		if (is_a($this->user, 'Admin')) {
			$this->response->redirectTo(array('Admin'));
//			$_SESSION['fmsg'] = 'You have already logged in';
			return;
		}
		$c = new HtmlC($errors, $F = new AppForm('Login:', array('Admin','login'), array(
			'email' => 'E-mail Address',
			'pwd' => 'Password',
		), $this->request));
		$F->field('pwd')->type('password')->value('');
		Html::n('div', 'c:ar', AppLink::newLink(Html::n('i', '', 'Forgot your Password?'), array('Admin', 'forgotPassword')), $c);
		return $c;
	}
	
}

?>