<?php

namespace App\Http\Controllers;

use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EcommerceController extends Controller
{
    protected $transbankService;
    protected $khipuService;

    public function __construct(TransbankService $transbankService, KhipuService $khipuService)
    {
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
    }

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
