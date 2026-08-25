<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function create(Request $request)
    {
        $modules = Module::all();
        $page = "Modules Setup";
        $description = "Select and configure the modules you need for your account.";

        $business = auth()->user()->business;

        return view('auth.modules-setup', compact('modules', 'page', 'description', 'business'));
    }
}
