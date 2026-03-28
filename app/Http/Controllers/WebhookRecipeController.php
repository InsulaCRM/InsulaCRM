<?php

namespace App\Http\Controllers;

class WebhookRecipeController extends Controller
{
    /**
     * Show the webhook integration recipes/documentation page.
     */
    public function index()
    {
        return view('webhooks.recipes');
    }
}
