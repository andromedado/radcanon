<?php

class ControllerAdmin extends ControllerAdminOnly
{
    protected $permittedMethods = array(
        'login', 'forgotPassword', 'resetSent', 'notFound',
    );
    /** @var ModelAdmin $model */
    public $model = NULL;
    protected $modelName = 'ModelAdmin';

    protected function load () {
        parent::load();
        $this->set('logoutAction', FilterRoutes::buildUrl(array('Admin', 'logout')));
    }

    private function edit ($id = NULL) {
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

    private function create () {
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

    private function delete ($id = NULL) {
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

    private function manage ($id = NULL) {
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
        $errors = array();
        if ($this->request->isPost()) {
            try {
                $this->user->updatePassword($this->request->post());
                $this->response->addMessage('Password Updated');
                $this->response->redirectTo(array('Admin'));
                return;
            } catch (ExceptionValidation $e) {
                $this->response->addMessage($e->getMessage(), true);
            }
        }

        $this->set('modifier', $modifier);
        $this->set('action', FilterRoutes::buildUrl(array('Admin', 'changePassword')));
        return;
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
        $this->set('email', $_SESSION['resetSentTo']);
        $_SESSION['resetSentTo'] = NULL;
    }

    public function forgotPassword () {
        $errors = array();
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
            $errors[] = 'Invalid E-Mail provided';
        }

        $this->set('action', FilterRoutes::buildUrl(array("Admin", 'forgotPassword')));
    }

    public function index () {


    }

    public function login ($modifier = NULL) {
        $errors = array();
        if ($this->request->isPost()) {
            $MA = ModelAdmin::findOne(array(
                'fields' => array(
                    'email' => $this->request->post('email'),
                ),
            ));
            if ($MA->isValid() && $MA->passwordAcceptable($this->request->post('pwd'))) {
                $MA->recordLogin();
                $_SESSION['msg'] = 'You have logged in';
                $dest = array('Admin');
                if (!empty($_SESSION['afterLogin'])) {
                    $dest = $_SESSION['afterLogin'];
                    $_SESSION['afterLogin'] = '';
                }
                $this->response->redirectTo($dest);
                return;
            }
            $this->response->cancelRedirect(Response::TYPE_HTML);
            $errors[] = 'Login Failed';
        }

        if (is_a($this->user, 'Admin')) {
            $this->response->redirectTo(array('Admin'));
            return;
        }

        $this->set('errors', $errors);
        $this->set('post', $this->request->post());
        $this->set('forgotHref', FilterRoutes::buildUrl(array('Admin', 'forgotPassword')));
        $this->set('action', FilterRoutes::buildUrl(array('Admin', 'login')));
    }

}

