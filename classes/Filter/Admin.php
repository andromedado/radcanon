<?php

class FilterAdmin implements Filter
{

    public function filter(Request $Request, Response $Response, User $User) {
        if (is_a($User, 'Admin') && !$Request->isPost()) {
            if ($User->requiresPasswordChange()) {
                $Request->setURI(FilterRoutes::buildUrl(array('Admin', 'changePassword', 'must'), true));
            }
        }
    }

}

