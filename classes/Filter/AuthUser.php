<?php

class FilterAuthUser implements Filter
{

    public function filter(Request $Request, Response $Response, User $User) {
        if (is_a($User, 'AuthUser') && !$Request->isPost()) {
            if ($User->requiresPasswordChange()) {
                $Request->setURI(FilterRoutes::buildUrl(array('AuthUser', 'changePassword', 'must')));
            }
        }
    }

}

