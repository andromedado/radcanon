<?php

abstract class ControllerApp {
	/** @var Request $request */
	public $request = NULL;
	/** @var Response $request */
	public $response = NULL;
	/** @var User $user */
	public $user = NULL;
	/** @var Model $model */
	public $model = NULL;
	protected $modelName = NULL;
	protected $TemplateDir = NULL;
	protected $baseName = null;
	protected $templateModelName = 'modelData';
	
	final public function __construct(Request $req, Response $res, User $user) {
		$this->request = $req;
		$this->response = $res;
		$this->user = $user;
		if (!is_null($this->modelName)) {
			$c = $this->modelName;
			$this->model = new $c;
		}
		$this->baseName = preg_replace('/^Controller/', '', get_class($this));
		$this->load();
	}
	
	protected function load () {
		
	}
	
	protected function prefilterInvocation (&$method, array &$arguments) {
		
	}
	
	public function addS () {
		$args = func_get_args();
		call_user_func_array(array($this->response, 'addS'), $args);
		return $this;
	}
	
	public function addScript () {
		$args = func_get_args();
		call_user_func_array(array($this->response, 'addScript'), $args);
		return $this;
	}
	
	public function addStyle () {
		$args = func_get_args();
		call_user_func_array(array($this->response, 'addStyle'), $args);
		return $this;
	}
	
	/**
	 * Set Template Var to given Val
	 * @return ControllerApp
	 */
	public function set ($var, $val) {
		$this->response->set($var, $val);
		return $this;
	}
	
	public function getTemplateDir () {
		if (!is_null($this->TemplateDir)) return $this->TemplateDir;
		return preg_replace('/^Controller/', '', get_class($this));
	}
	
	public function invoke ($method, array $arguments = array()) {
		$this->prefilterInvocation($method, $arguments);
		$this->response->set('invocation', array(get_class($this), $method, $arguments));
		if (!method_exists($this, $method)) throw new ExceptionBase('Invoke called on with invalid combo: ' . $method);
		$template = $this->getTemplateDir() . DS . $method . '.html.twig';
		if (file_exists(APP_TEMPLATES_DIR . $template) || file_exists(RADCANON_TEMPLATES_DIR . $template)) {
			$this->response->template = $template;
		} elseif (DEBUG) {
			$this->response->template = 'RadCanon' . DS . 'missingTemplate.html.twig';
			$this->response->set('missingTemplate', $template);
		}
		if (file_exists(CSS_DIR . strtolower($this->baseName) . '.css')) {
			$this->addStyle(strtolower($this->baseName));
		}
		if (file_exists(CSS_DIR . strtolower($this->baseName . '-' . $method) . '.css')) {
			$this->addStyle(strtolower($this->baseName . '-' . $method));
		}
		if (file_exists(JS_DIR . strtolower($this->baseName) . '.js')) {
			$this->addScript(strtolower($this->baseName));
		}
		if (file_exists(JS_DIR . strtolower($this->baseName . '-' . $method) . '.js')) {
			$this->addScript(strtolower($this->baseName . '-' . $method));
		}
		$this->response->content = call_user_func_array(array($this, $method), $arguments);
	}
	
	public function notFound() {
		$this->response->addHeader('Not Found', true, 404);
		$this->response->template = 'Pages' . DS . 'notFound.html.twig';
	}
	
	public function notPermitted()
	{
		$this->response->addHeader('Forbidden', true, 403);
		$this->response->template = 'Pages' . DS . 'notPermitted.html.twig';
	}
	
	public function index () {
		if (DEBUG) return get_called_class() . ' index';
		$this->response->redirectTo(APP_SUB_DIR . '/');
	}
	
	/**
	 * Most common update action
	 * @param Int|Model $id Model Id
	 * @param Array $settings
	 * @index modelName {String}
	 * @index successMessage {String}
	 * @index destination {mixed} afer update
	 * @index templateModelName {String} var name for the model data in the template
	 */
	protected function _update($id, array $settings = array())
	{
		if (is_a($id, 'Model')) {
			$Model = $id;
			$id = $Model->id;
		} else {
			if (!isset($settings['modelName'])) $settings['modelName'] = $this->modelName;
			$Model = new $settings['modelName']($id);
		}
		if (!$Model->isValid()) return $this->notFound();
		
		if ($this->request->isPost()) {
			try {
				$Model->safeUpdateVars($this->request->post());
				if (!isset($settings['successMessage'])) $settings['successMessage'] = $Model->whatAmI() . ' Updated';
				$this->response->addMessage($settings['successMessage']);
				if (isset($settings['destination'])) {
					$this->response->redirectTo($settings['destination']);
				}
				return;
			} catch (ExceptionValidation $e) {
				$this->response->addMessage($e);
			}
		}
		
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $Model->getData());
	}
	
	/**
	 * Most common create action
	 * @param Array $settings
	 * @index modelName {String}
	 * @index successMessage {String}
	 * @index destination {mixed} afer update
	 * @index templateModelName {String} var name for the model data in the template
	 */
	protected function _create(array $settings = array())
	{
		if (!isset($settings['modelName'])) $settings['modelName'] = $this->modelName;
		$Model = new $settings['modelName'];
		
		if ($this->request->isPost()) {
			try {
				$Model->safeCreateWithVars($this->request->post());
				if (!isset($settings['successMessage'])) $settings['successMessage'] = $Model->whatAmI() . ' Created';
				$this->response->addMessage($settings['successMessage']);
				if (isset($settings['destination'])) {
					$this->response->redirectTo($settings['destination']);
				}
				return;
			} catch (ExceptionValidation $e) {
				$this->response->addMessage($e);
			}
		}
		
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $Model->getData());
	}
	
	protected function _review($id, array $settings = array())
	{
		if (is_a($id, 'Model')) {
			$Model = $id;
			$id = $Model->id;
		} else {
			if (!isset($settings['modelName'])) $settings['modelName'] = $this->modelName;
			$Model = new $settings['modelName']($id);
		}
		if (!$Model->isValid()) return $this->notFound();
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $Model->getData());
	}
	
}

