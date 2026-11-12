<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;

class DivisionController extends Controller
{
    public function index()
    {
        return Division::orderBy('name')->get(['id as division_id', 'name as division_name']);
    }
}
