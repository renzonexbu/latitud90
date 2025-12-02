<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\TermCondition;
use Inertia\Inertia;

class TermsAndConditionsController extends Controller
{
    public function termsAndConditions()
    {
        $terms = TermCondition::active()->ordered()->get();

        return Inertia::render('Ecommerce/TermsAndConditions', [
            'terms' => $terms
        ]);
    }
}
