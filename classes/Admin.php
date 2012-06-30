<?php

/**
 * Class representing Admins interacting with the application
 */
class Admin extends AuthUser {
//	const SESSION_KEY = '_admin_login_id';
	const MODEL_CLASS = 'ModelAdmin';
	/** @var ModelAdmin $Model */
	protected $Model = NULL;
	protected $NavItems = array(
	);
	protected $AdiNavItems = array(
	);
	
	public function renderNavItems ($invocation = array()) {
		$c = new HtmlC($ul = Html::n('ul'), Html::n('div', array('style' => 'clear:both;')));
		$test = array();
		if (isset($invocation['This'])) {
			$test['controller'] = str_replace('Controller', '', $invocation['This']);
			if (isset($invocation['Method'])) {
				$test['action'] = $invocation['Method'];
				if (!empty($invocation['Arguments'])) {
					$test['arguments'] = $invocation['Arguments'];
				}
			}
		}
		$Test = FilterRoutes::buildUrl($test);
		foreach ($this->NavItems as $word => $location) {
			$ul->li(AppLink::newLink($word, $location), 'c:' . ($Test === FilterRoutes::buildUrl($location) ? 'current' : ''));
		}
		foreach ($this->AdiNavItems as $word => $location) {
			$ul->li(AppLink::newLink($word, $location), 'c:' . ($Test === FilterRoutes::buildUrl($location) ? 'current' : ''));
		}
		$c->prepend($F = new AppForm('', 'Admin/Logout/'));
		$F->form->addClass('fr');
		$F->submit->value('Logout');
		return $c;
	}
	
}

?>