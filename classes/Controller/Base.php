<?php

abstract class ControllerBase
{
	/** @var Request $request */
	public $request = null;
	/** @var Response $request */
	public $response = null;
	/** @var User $user */
	public $user = null;
	/** @var Model $model */
	public $model = null;
	protected $modelName = null;
	protected $TemplateDir = null;
	protected $baseName = null;
	protected $templateModelName = 'modelData';
	
	final public function __construct(Request $req, Response $res, User $user) {
		$this->request = $req;
		$this->response = $res;
		$this->user = $user;
		$this->baseName = preg_replace('/^Controller/', '', get_class($this));
		if (is_null($this->modelName)) {
			$this->modelName = 'Model' . $this->baseName;
		}
		$c = $this->modelName;
		if (class_exists($c)) {
			$this->model = new $c;
		}
		$this->load();
	}
	
	protected function serveFile($filepath, $filename = null)
	{
		if (!is_readable($filepath)) throw new ExceptionBase('Unable to read file: ' . $filepath);
		if (is_null($filename)) $filename = basename($filepath);
		$this->response->type = Response::TYPE_FILESTREAM;
		$this->response->contentType = finfo_file(finfo_open(), $filepath);
		if (!$this->response->contentType) $this->response->contentType = 'text/html';
		$this->response->addHeader('Content-disposition: attachment; filename="' . str_replace('"', "'", $filename) . '"');
		return $filepath;
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
	
	protected function prepForForm()
	{
		
	}
	
	/**
	 * Most common index action
	 * Review all models
	 */
	protected function _index(array $settings = array())
	{
		if (!isset($settings['modelName'])) $settings['modelName'] = $this->modelName;
		if (!isset($settings['templateModelName'])) $settings['templateModelName'] = $this->templateModelName;
		$this->set('model', new $this->modelName);
		$this->set(array($settings['templateModelName'] . 's', 'models'), call_user_func(array($settings['modelName'], 'getAllData')));
	}

	protected function afterSave(Model $M, $creating = false)
	{
		
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
				$this->afterSave($Model);
				if (!isset($settings['successMessage'])) $settings['successMessage'] = $Model->whatAmI() . ' Updated';
				$this->response->addMessage($settings['successMessage']);
				if (isset($settings['destination'])) {
					$this->response->redirectTo($settings['destination']);
				}
				return;
			} catch (ExceptionValidation $e) {
				$this->response->addMessage($e);
			} catch (ExceptionPDO $e) {
				if (strpos($e->getInternalMessage(), 'Duplicate entry') !== false) {
					$this->response->addMessage('Duplicate entry for unique field', true);
				} else {
					$this->response->addMessage($e);
				}
			}
		}
		$this->prepForForm();
		$modelData = isset($settings['modelData']) ? $settings['modelData'] : $Model->getData();
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $modelData);
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
		if (isset($settings['Model'])) {
			$Model = $settings['Model'];
		} else {
			if (!isset($settings['modelName'])) $settings['modelName'] = $this->modelName;
			if (!class_exists($settings['modelName'])) throw new ExceptionBase('Class Not Found: ' . $settings['modelName']);
			$Model = new $settings['modelName'];
		}
		
		if ($this->request->isPost()) {
			try {
				$Model->safeCreateWithVars($this->request->post());
				$this->afterSave($Model, true);
				if (!isset($settings['successMessage'])) $settings['successMessage'] = $Model->whatAmI() . ' Created';
				$this->response->addMessage($settings['successMessage']);
				if (isset($settings['destination'])) {
					$this->response->redirectTo($settings['destination']);
				}
				return;
			} catch (ExceptionValidation $e) {
				$this->response->addMessage($e);
			} catch (ExceptionPDO $e) {
				if (strpos($e->getInternalMessage(), 'Duplicate entry') !== false) {
					$this->response->addMessage('Duplicate entry for unique field', true);
				} else {
					$this->response->addMessage($e);
				}
			}
		}
		$this->prepForForm();
		$modelData = isset($settings['modelData']) ? $settings['modelData'] : $Model->getData();
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $modelData);
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
		$modelData = isset($settings['modelData']) ? $settings['modelData'] : $Model->getData();
		$this->set(!isset($settings['templateModelName']) ? $this->templateModelName : $settings['templateModelName'], $modelData);
	}
	
}

