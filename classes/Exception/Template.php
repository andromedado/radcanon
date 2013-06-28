<?php

class ExceptionTemplate extends ExceptionBase
{
    protected $internalMessage = 'Template could not be found',
        $template;
    protected static $publicMessage = 'The system has experienced an error (EC-%d), please try again later';

    public function __construct($template, $msg = '', $code = 1, $previous = NULL) {
        parent::__construct($msg, $code, $previous);
        $this->template = $template;
    }

}

