<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class TermsAndConditionsController extends Controller
{
    public function termsAndConditions()
    {
        return Inertia::render('Ecommerce/TermsAndConditions');
    }
}
