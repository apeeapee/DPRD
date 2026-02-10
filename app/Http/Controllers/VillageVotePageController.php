<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VillageVotePageController extends Controller
{
    public function index(Request $request)
    {
        return view('village-votes.index');
    }
}
