<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class EcommerceController extends Controller
{
    public function index()
    {
        return Inertia::render('Ecommerce/Index', [
            'programs' => [],
            'filters' => [],
            'serviceTypes' => []
        ]);
    }

    public function termsAndConditions()
    {
        return Inertia::render('Ecommerce/TermsAndConditions');
    }
}
