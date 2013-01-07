<?php

class ControllerMagic extends ControllerApp
{
	protected $GateKeeperMethods = array(
	);
	
	protected function isAdmin()
	{
		return is_a($this->user, 'Admin');
	}
	
	protected function isAuthUser()
	{
		return is_a($this->user, 'AuthUser');
	}
	
	protected function mayProceed($invoked, array $args)
	{
		$permitted = $return = true;
		foreach ($this->GateKeeperMethods as $method => $methodOnFailure) {
			if (!method_exists($this, $method)) {
				throw new ExceptionBase('Invalid Method offered by `GetKeeperMethods`: ' . $method);
			}
			if (call_user_func(array($this, $method), $invoked, $args) !== true) {
				$permitted = false;
				if (is_null($methodOnFailure)) {
					$return = $this->notFound();
				} else {
					$return = call_user_func(array($this, $methodOnFailure), $invoked, $args);
				}
				break;
			}
		}
		return array($permitted, $return);
	}
	
	protected function determineDestination($invoked, array $args)
	{
		if ($this->request->post('_bounceBack', null) !== null) {
			return $this->request->post('_bounceBack');
		}
		switch ($invoked) {
			case "update":
				$id = isset($args[0]) ? $args[0] : 0;
				$dest = array($this->baseName, 'review', $id);
				break;
			default:
				$dest = array($this->baseName);
		}
		return $dest;
	}
	
	protected function determineSettings($invoked, array $args)
	{
		$this->set('_uri', $args);
		$settings = array();
		switch($invoked) {
			case 'delete':
			case 'update':
			case 'create':
				$settings['destination'] = $this->determineDestination($invoked, $args);
				break;
			
		}
		return $settings;
	}
	
	public function create()
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		if (!file_exists(APP_TEMPLATES_DIR . $this->getTemplateDir() . DS . 'create.html.twig')) {
			$this->response->template = 'Model' . DS . 'create.html.twig';
		}
		return $this->_create($this->determineSettings(__FUNCTION__, $args));
	}
	
	public function review($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		if (!file_exists(APP_TEMPLATES_DIR . $this->getTemplateDir() . DS . 'review.html.twig')) {
			$this->response->template = 'Model' . DS . 'review.html.twig';
		}
		return $this->_review($id, $this->determineSettings(__FUNCTION__, $args));
	}
	
	public function update($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		if (!file_exists(APP_TEMPLATES_DIR . $this->getTemplateDir() . DS . 'update.html.twig')) {
			$this->response->template = 'Model' . DS . 'update.html.twig';
		}
		return $this->_update($id, $this->determineSettings(__FUNCTION__, $args));
	}
	
	public function delete($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_delete($id, $this->determineSettings(__FUNCTION__, $args));
	}
	
	public function index()
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		if (!file_exists(APP_TEMPLATES_DIR . $this->getTemplateDir() . DS . 'index.html.twig')) {
			$this->response->template = 'Model' . DS . 'index.html.twig';
		}
		return $this->_index($this->determineSettings(__FUNCTION__, $args));
	}
	
}

