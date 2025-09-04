<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;

class AIController extends Controller
{
    public function rate_limit()
    {
        return AIService::rate_limit();
    }
}
