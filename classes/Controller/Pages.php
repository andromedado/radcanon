<?php

class ControllerPages extends ControllerApp
{

	public function catchAll () {
		$m = preg_replace('#[^A-Z\d_-]#i', '', func_get_arg(0));
		if ($this->response->templateExists('Pages' . DS . $m . '.html.twig')) {
			$this->response->template = 'Pages' . DS . $m . '.html.twig';
			return;
		}
		return $this->notFound();
	}

	public function homepage () {
		$this->set('content', 'Hi there');
	}

}

