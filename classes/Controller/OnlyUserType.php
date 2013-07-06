<?php

abstract class ControllerOnlyUserType extends ControllerApp {
    protected $UserType = 'AuthUser';
    protected $reRouteTo = array();
    protected $permittedMethods = array(
        'notFound'
    );

    public function prefilterInvocation (&$method, array &$arguments) {
        parent::prefilterInvocation($method, $arguments);
        if (!in_array($method, $this->permittedMethods) && !is_a($this->user, $this->UserType)) {
            $this->throwReRoute();
            if (DEBUG) {
                $this->set('tried', array($method, $arguments));
            }
            $method = 'notFound';
        }
    }

    protected function throwReRoute()
    {
        if (!empty($this->reRouteTo)) {
            throw new ExceptionReroute(FilterRoutes::buildUrl($this->reRouteTo, false, false));
        }
    }

}
