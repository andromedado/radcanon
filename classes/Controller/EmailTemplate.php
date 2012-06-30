<?php

class ControllerEmailTemplate extends ControllerSuperAdminOnly {
	/** @var ModelEmailTemplate $model */
	public $model = NULL;
	protected $modelName = 'ModelEmailTemplate';
	protected $permittedMethods = array(
		'notFound', 'review',
	);
	
	public function sendNotification ($patientId = 0, $templateId = NULL) {
		$P = new ModelPatient($patientId);
		if (!$P->isValid()) return $this->notFound();
		Template::addCSS('templates');
		$c = new HtmlC;
		if (is_null($templateId)) {
			Html::n('h1', '', 'Choose template for notification:', $c);
			$c->append(AppLink::newLink('Blank Template', array('EmailTemplate', 'sendNotification', $patientId, '0')));
			$ETs = ModelEmailTemplate::findAll(array(
				'fields' => array(
					'inactive' => NULL,
				),
			));
			$naETs = UtilsArray::filterWithMethod($ETs, 'isAppointmentRequired', array(), true);
			if (!empty($naETs)) {
				Html::n('h2', '', 'Non-Appointment Templates:', $c);
				$ul = Html::n('ul', '', '', $c);
				foreach ($naETs as $naET) {
					$ul->li(AppLink::newLink($naET->getName(), array('EmailTemplate', 'sendNotification', $patientId, $naET->id)) . '<br />' . $naET->subject);
				}
			}
			if ($P->hasAppointment()) {
				$aETs = UtilsArray::filterWithMethod($ETs, 'isAppointmentRequired');
				if (!empty($aETs)) {
					Html::n('h2', '', 'Appointment Templates:', $c);
					$ul = Html::n('ul', '', '', $c);
					foreach ($aETs as $aET) {
						$ul->li(AppLink::newLink($aET->getName(), array('EmailTemplate', 'sendNotification', $patientId, $aET->id)) . '<br />' . $aET->subject);
					}
				}
			}
		} else {
			Html::n('h1', '', 'Send Notification to ' . $P->getName(), $c);
			$T = EmailTemplateFactory::build($templateId);
			if (($T->isAppointmentRequired() && !$P->hasAppointment())) return $this->notFound();//!$T->isValid() || 
			Template::addCSS('email');
			Template::UseTinyMce();
			$d = Html::n('div', 'i:email_wrap2', '', $c);
			$d->append($AF = new AppForm('', array('EmailTemplate', 'send')));
			$AF->addHiddenField($P->idCol, $P->id);
			$AF->addHiddenField('last_email', $T->id);
			$AF->addField('To:', new HtmlC, Html::n('h2', '', $P->email));
			$AF->addField('Subject:', Html::n('input', 't:text;n:subject;c:not_blank large;size:40', $T->translate($T->subject, $P)));
			$AF->addField('Message:', Html::n('textarea', 'n:body;c:tinymce', $T->translate($T->body, $P)));
			$AF->table->class = '';
		}
		return $c;
	}

	public function send () {
		$P = new ModelPatient($this->request->post('pat_id', 0));
		if ($P->isValid() && $P->hasEmail() && $this->request->isPost()) {
			$Em = new Email($P->email, $this->request->post('subject'), new HtmlC($this->request->post('body')));
			$Em->send();
			$P->updateVars(array(
				'last_email' => $this->request->post('last_email'),
				'when_last_email' => time(),
			));
			$_SESSION['msg'] = 'Notification Sent';
			$this->response->redirectTo(array('Patient', 'review', $P->id));
			return;
		}
		return $this->notFound();
	}
	
	public function create ($adi = NULL) {
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			$this->model->loadAs(0);
			try {
				$vars = $this->request->post();
				$vars['adid'] = $this->user->id;
				$this->model->safeCreateWithVars($vars);
				if ($this->model->isValid()) {
					$this->response->redirectTo(array('EmailTemplate', 'review', $this->model->id));
					$_SESSION['msg'] = 'Email Template Created';
					return;
				} else {
					$errors->add('There was a problem');
				}
			} catch (ExceptionValidation $e) {
				$errors->add($e->getMessage());
			}
			
		}
		$M = NULL;
		if ($adi === 'apt') {
			$M = new ModelAptEmailTemplate;
		}
		$c = new HtmlC($errors, $this->renderForm($M));
		return $c;
	}
	
	public function edit ($id = NULL) {
		$errors = new HtmlErrors;
		if ($this->request->isPost()) {
			$ET = EmailTemplateFactory::build($this->request->post($this->model->idCol, 0));
			if ($ET->isValid()) {
				$vars = $this->request->post();
				if (isset($vars['adid'])) unset($vars['adid']);
				try {
					$ET->safeUpdateVars($vars);
					$this->response->redirectTo(array('EmailTemplate', 'review', $ET->id));
					$_SESSION['msg'] = 'Email Template Updated';
					return;
				} catch (ExceptionValidation $e) {
					$errors->add($e->getMessage());
				}
			}
		}
		$ET = EmailTemplateFactory::build($id);
		if (!$ET->isValid()) return $this->notFound();
		$c = new HtmlC($errors, $this->renderForm($ET));
		return $c;
	}
	
	protected function renderForm(ModelEmailTemplate $ET = NULL) {
		Template::UseTinyMce();
		$v = 'edit';
		if (is_null($ET) || !$ET->isValid()) {
			$v = 'create';
			if (is_null($ET)) $ET = new ModelEmailTemplate;
		}
		$AF = new AppForm(ucfirst($v) . ' ' . (is_a($ET, 'ModelAptEmailTemplate') ? '' : 'Non-') . 'Appointment E-mail Template', array('EmailTemplate', $v, $ET->id));
		if ($ET->isValid()) {
			$AF->addHiddenField($ET->idCol, $ET->id);
		}
		$AF->addField('Name', Html::n('input', 't:text;n:name;c:not_blank', $ET->name), REQUIRED_SPAN);
		$vars = $ET->getVars();
		$delim = $ET->getVarDelimiter();
		$varT = Html::n('table', 'c:autoT');
		foreach ($vars as $var => $Exp) {
			$varT->pair($Exp, Html::n('input', 't:text;c:invisible', $delim . $var . $delim));
		}
		$AF->addField('Available Template Fields:', Html::n('input', 't:hidden;n:type', $ET->type), $varT);
		$AF->addField('Subject', Html::n('input', 't:text;n:subject;size:50;c:large not_blank', $ET->subject), REQUIRED_SPAN);
		$AF->addField('Message', Html::n('textarea', 'c:tinymce;n:body;rows:10;cols:60', $ET->body));
		return $AF;
	}
	
	public function highlightFields ($str, ModelEmailTemplate $ET) {
		$fs = $ET->getVars();
		$d = $ET->getVarDelimiter();
		foreach ($fs as $f => $long) {
			$F = $d . $f . $d;
			$str = str_replace($F, '<span class="highlight">' . $F . '</span>', $str);
		}
		return $str;
	}
	
	public function delete ($id = NULL) {
		if ($this->request->isPost()) {
			$ET = EmailTemplateFactory::build($id);
			$ET->updateVar('inactive', 1);
			$_SESSION['msg'] = 'Email Template Deleted';
			$this->response->redirectTo(array('EmailTemplate', 'review'));
		}
	}
	
	public function review ($id = NULL) {
		
		$c = new HtmlC;
		$ET = EmailTemplateFactory::build($id);
		if ($ET->isValid()) {
			Template::addCSS('email');
			$h1 = Html::n('h1', '', 'Review E-mail Template: ' . $ET->getName(), $c);
			if (is_a($this->user, 'SuperAdmin')) {
				AppLink::newLink('edit', array('EmailTemplate', 'edit', $ET->id))->prependTo($h1)->addClass('fr');
			}
			$ew = Html::n('div', 'i:email_wrap', '', $c);
			Html::n('h2', '', 'Subject: ' . $this->highlightFields($ET->subject, $ET), $ew);
			Html::n('h2', '', 'Message:', $ew);
			Html::n('div', 'c:email_body', $this->highlightFields($ET->body, $ET), $ew);
			$c->append($AF = new AppForm('', array('EmailTemplate', 'delete', $ET->id)));
			$AF->submitWrap->align("center");
			$AF->submit->value('Delete Template');
			$AF->form->onsubmit("return confirm('Delete This Template?')");
		} else {
			Html::n('h1', '', 'Review E-mail Templates:', $c);
			$T = Html::n('table', 'c:autoT fw p10', '', $c);
			$T->nTR()->th('Appointment Templates')->th('Non-Appointment Templates');
			$ETs = ModelEmailTemplate::findAll(array(
				'fields' => array(
					'inactive' => NULL,
				),
			), array('EmailTemplateFactory', 'build'));
			$aptTs = UtilsArray::filterWithMethod($ETs, 'isAppointmentRequired');
			$aptC = new HtmlC;
			if (empty($aptTs)) {
				Html::n('h3/i', '', 'None Found', $aptC);
			} else {
				$aptC->append(implode('<br />', UtilsArray::callOnAll($aptTs, 'getLinkedName')));
			}
			Html::n('h5', '', new AppLink('New Appointment Template', array('EmailTemplate', 'create', 'apt')), $aptC);
			$nAptTs = UtilsArray::filterWithMethod($ETs, 'isAppointmentRequired', array(), true);
			$nAptC = new HtmlC;
			if (empty($nAptTs)) {
				Html::n('h3/i', '', 'None Found', $nAptC);
			} else {
				$nAptC->append(implode('<br />', UtilsArray::callOnAll($nAptTs, 'getLinkedName')));
			}
			Html::n('h5', '', new AppLink('New Non-Appointment Template', array('EmailTemplate', 'create')), $nAptC);
			$T->nTR()->td($aptC)->td($nAptC);
		}
		return $c;
	}
	
}

?>