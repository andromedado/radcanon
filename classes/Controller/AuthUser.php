<?php

/**
 * Class ControllerAuthUser
 * This is left abstract in radcanon, so, just subclass or copy this file into
 * your app and remove abstract if you want it for free.
 */
class ControllerAuthUser extends ControllerMagic
{
    protected $modelName = 'ModelAuthUser',
        $TemplateDir = 'AuthUser',
        $baseName = 'AuthUser',
        $GateKeeperMethods = array(
            'isAuthUser' => null,
        );

    protected static $anytime = array(
        'login', 'forgotpassword', 'resetsent'
    );

    protected function mayProceed($invoked, array $args)
    {
        return parent::mayProceed($invoked, $args) || self::anyoneCanUseMethod($invoked);
    }

    public static function getAnyoneMethod()
    {
        return static::$anytime;
    }

    public static function anyoneCanUseMethod($method) {
        return in_array(strtolower($method), static::$anytime, true);
    }

    protected function getLoggedInDestination(ModelUser $User)
    {
        if ($User->requiresPasswordChange()) {
            return FilterRoutes::buildUrl(array('AuthUser', 'changePassword', 'must'));
        }
        return '/' . APP_SUB_DIR;
    }

    public function forgotPassword ()
    {
        if ($this->request->isPost()) {
            $email = $this->request->post('email', '');
            if (!empty($email) && UtilsString::isEmail($email)) {
                /** @var ModelUser $User */
                $User = ModelUser::findOneByField('email', $email);
                if ($User->isValid()) {
                    $tmpPass = $User->setTemporaryPassword();
                    if ($tmpPass !== false) {
                        $emailBody = $this->response->getTwigEnvironment()->render('AuthUser/forgotPasswordEmail.twig', array(
                            'email' => $User->getEmail(),
                            'temporaryPassword' => $tmpPass,
                            'expires' => date('m/d/Y g:ia T', $User->getTmpPasswordExpires()),
                            'loginHref' => 'http://' . DEFAULT_SITE_HOST . FilterRoutes::buildUrl(array('AuthUser', 'login')),
                            'appName' => APP_NAME,
                        ));
                        Email::sendMail($User->getEmail(), 'Password Reset', $emailBody);
                    }
                }
                $this->response->redirectTo(array('AuthUser', 'resetSent'));
                return;
            }
            $this->response->addMessage('Invalid E-Mail provided', true);
        }
    }

    public function resetSent()
    {}

    public function logout()
    {
        if ($this->user instanceof AuthUser) {
            $this->user->getModel()->logout();
            $this->response->redirectTo(FilterRoutes::buildUrl(array('AuthUser', 'login')));
            $this->response->addMessage('You\'ve been logged out');
            return;
        }
    }

    public function changePassword ($modifier = '') {
        $user = $this->user;
        if ($this->request->isPost() && $user instanceof AuthUser) {
            /** @var AuthUser $user */
            try {
                $user->getModel()->updatePassword($this->request->post());
                $this->response->addMessage('Password Updated');
                $this->response->redirectTo($this->getLoggedInDestination($user->getModel()));
                return;
            } catch (ExceptionValidation $e) {
                $this->response->addMessage($e->getMessage(), true);
            }
        }

        $this->set('modifier', $modifier);
        return;
    }

    public function login()
    {
        if ($this->request->isPost()) {
            $postedEmail = $this->request->post('email');
            if (!empty($postedEmail)) {
                /** @var ModelUser $User */
                $User = ModelUser::findOneByField('email', $postedEmail);
                if ($User->isValid() && $User->passwordAcceptable($this->request->post('pwd'))) {
                    $User->recordLogin();
                    $this->response->addMessage('You Have Logged In');
                    $this->response->redirectTo($this->getLoggedInDestination($User));
                    return;
                }
            }
            $this->response->addMessage('Login Failed', true);
        }
        if ($this->user instanceof AuthUser) {
            $this->response->redirectTo($this->getLoggedInDestination($this->user->getModel()));
            return;
        }
        $this->set('forgotHref', FilterRoutes::buildUrl(array('AuthUser', 'forgotPassword')));
    }

}

