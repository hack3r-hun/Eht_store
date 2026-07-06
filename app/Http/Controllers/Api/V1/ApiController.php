<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    use RespondsWithJson;
}
