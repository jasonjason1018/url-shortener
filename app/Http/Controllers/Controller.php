<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Decorator\ControllerReturnDecorator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function callAction($method, $parameters)
    {
        $result = $this->{$method}(...array_values($parameters));

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $return = new ControllerReturnDecorator($result);

        return $return->decorate();
    }
}
