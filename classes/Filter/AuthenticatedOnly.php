<?php

class FilterAuthenticatedOnly implements Filter
{

    public function filter(Request $Request, Response $Response, User $User)
    {
        if (!is_a($User, 'AuthUser')) {
            if (!$this->mayProceed($Request)) {
                $this->rewriteRequestToLogin($Request);
                return;
            }
        }
    }

    public function rewriteRequestToLogin(Request $Request)
    {
        $Request->setURI(array('AuthUser', 'login'));
    }

    public function mayProceed(Request $Request)
    {
        $currentUri = strtolower(trim($Request->getURI(), '/'));
        $methods = ControllerAuthUser::getAnyoneMethod();
        $couldDo = array();
        foreach ($methods as $method) {
            $possible = strtolower(trim(FilterRoutes::buildUrl(array('AuthUser', $method)), '/'));
            $couldDo[] = $possible;
            if ($currentUri === $possible) {
                return true;
            }
        }
        return false;
    }

}

