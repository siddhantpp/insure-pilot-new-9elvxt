<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController; // Laravel 10.0+
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Laravel 10.0+
use Illuminate\Foundation\Validation\ValidatesRequests; // Laravel 10.0+ 
use Illuminate\Foundation\Bus\DispatchesJobs; // Laravel 10.0+

/**
 * Base controller class that all other controllers in the application extend.
 * 
 * This class provides common functionality and traits used across all controllers
 * in the Documents View feature. It serves as a foundation for the controller layer
 * in the MVC architecture.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, DispatchesJobs;
}