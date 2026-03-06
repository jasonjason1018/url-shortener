<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ExceptionDecorator extends Exception
{
    const DEFAULT_MESSAGE = "UNKNOWN ERROR";
    const DEFAULT_CODE = 999;
    protected $_instance;

    public function __construct(Throwable $exception)
    {
        $this->_instance = $exception;
    }

    public function render()
    {
        $message = $this->_instance->getMessage();

        if (!$message) {
            $message = self::DEFAULT_MESSAGE;
        }

        $code = $this->_instance->getCode();

        if (!$code) {
            $code = self::DEFAULT_CODE;
        }

        $result = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        $json = json_encode($result, JSON_UNESCAPED_UNICODE);

        if ($json) {
            $result = $json;
        }

        $status = 500;

        if ($message === 'Unauthorized') {
            $status = 401;
        }

        return response($result, $status);
    }
}
