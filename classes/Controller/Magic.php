<?php

class ControllerMagic extends ControllerApp
{
    const ACTION_ON_UPDATE = 'review',
        SORT_ORDER_FIELD_NAME = 'so';

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

    public function updateSo()
    {
        $ids = $this->request->post('ids');
        $idCol = call_user_func(array($this->modelName, 'getIDCol'));
        foreach ($ids as $so => $id) {
            call_user_func(
                array($this->modelName, 'updateWhere'),
                array(static::SORT_ORDER_FIELD_NAME => $so),
                array($idCol => $id)
            );
        }
        if ($this->request->isAjax()) {
            return array('good' => true);
        }
        $this->response->redirectTo(array($this->modelName, 'index'));
        return;
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
        } else if (session_id() && isset($_SESSION['bounceBack'])) {
            return $_SESSION['bounceBack'];
        }
        switch ($invoked) {
            case "update":
                $id = isset($args[0]) ? $args[0] : 0;
                $dest = array($this->baseName, static::ACTION_ON_UPDATE, $id);
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
        $this->autoAddAssets('form');
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
        $this->autoAddAssets('form');
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
        if (!$permitted) {
            return $return;
        }
        if ($this->request->isAjax()) {
            try {
                $this->defaultTemplate = $this->response->template = $this->getTemplate('ajIndex');
                $this->response->type = Response::TYPE_TEMPLATE_IN_JSON;
            } catch (ExceptionBase $e) {}
        }
        if (!$this->response->templateExists($this->defaultTemplate)) {
            $this->response->template = 'Model' . DS . 'index.html.twig';
        }
        $this->set('listTemplate', $this->getTemplate('renderList'));
        $this->set('liTemplate', $this->getTemplate('renderAsLi'));
        $this->_index($this->determineSettings(__FUNCTION__, $args));
    }

    protected function renderTabularData(array $dataExposers, CSV $csv = null)
    {
        $rows = array();
        $columns = array();
        $tFoot = array('cells' => array());
        $hasTFoot = null;
        $tFootCellValues = array();
        foreach ($dataExposers as $dataExposer) {
            /** @var ExposesTabularData $dataExposer */
            if (!$dataExposer instanceof ExposesTabularData) {
                throw new InvalidArgumentException('All elements passed to ' . __FUNCTION__ . 'must implement ExposesTabularData');
            }
            if (empty($columns)) {
                $columns = $dataExposer->getColumns();
                if (!is_null($csv)) {
                    foreach ($columns as $column) {
                        $csv->addCell($column['name']);
                    }
                    $csv->finishRow();
                }
            }
            if (!isset($hasTFoot)) {
                $hasTFoot = $dataExposer->hasTFoot();
                if ($hasTFoot) {
                    foreach ($columns as $index => $column) {
                        $tFootCellValues[$index] = $dataExposer->getBaseTFootCellValueForColumn($column);
                    }
                }
            }
            $row = $dataExposer->getRowAttributes();
            $row['cells'] = array();
            foreach ($columns as $index => $column) {
                $cell = $dataExposer->getCellAttributesForColumn($column);
                $cell['value'] = $dataExposer->getCellValueForColumn($column);
                if (!is_null($csv)) {
                    $csv->addCell($dataExposer->getCellValueForColumn($column, false));
                }
                $row['cells'][] = $cell;
                if ($hasTFoot) {
                    $dataExposer->mutateTFootCellValueForColumn($tFootCellValues[$index], $column);
                }
            }
            if (!is_null($csv)) {
                $csv->finishRow();
            }
            $rows[] = $row;
        }
        if ($hasTFoot && isset($dataExposer)) {
            $tFoot = $dataExposer->getTFootAttributes();
            $tFoot['cells'] = array();
            foreach ($columns as $index => $column) {
                $footCell = array('value' => $tFootCellValues[$index]);
                $dataExposer->finalTFootCellMutate($footCell, $column);
                $tFoot['cells'][] = $footCell;
            }
        }
        $this->response->template = 'Model/renderTabularData.twig';
        $this->set(array(
            'rowsName' => 'Entries',
            'columns' => $columns,
            'rows' => $rows,
            'hasTFoot' => $hasTFoot,
            'tFoot' => $tFoot,
        ));
    }

}

