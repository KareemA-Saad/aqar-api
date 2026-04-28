<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * Base Controller for the RealEstate Module.
 *
 * Strips the {tenant} route parameter before dispatching to the action method.
 * Without this, Laravel's Controller::callAction() converts the route parameters
 * to a positional array via array_values(), causing {tenant} to be passed as the
 * first argument — which means controller methods like show($id) receive the
 * tenant slug instead of the actual resource ID.
 *
 * @package Modules\RealEstate\Http\Controllers
 */
abstract class BaseController extends Controller
{
    use ApiResponse;

    /**
     * Execute an action on the controller.
     *
     * @param string $method
     * @param array<string, mixed> $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        // The {tenant} parameter is consumed by middleware (tenancy.token)
        // and must not leak into controller method arguments.
        unset($parameters['tenant']);

        return parent::callAction($method, $parameters);
    }
}
