<?php

namespace App\Http\Controllers;

class ApiDocsController extends Controller
{
    public function index()
    {
        $endpoints = require resource_path('data/api_docs_endpoints.php');

        return view('api_docs.index', ['endpoints' => $endpoints]);
    }
}
