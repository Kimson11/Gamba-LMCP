<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.analytics.index');
    }
}
