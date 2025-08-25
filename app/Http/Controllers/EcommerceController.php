<?php

namespace App\Http\Controllers;

use App\Services\Client\NewsletterService;
use App\Services\Client\ContactMessageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EcommerceController extends Controller
{
    protected $newsletterService;
    protected $contactMessageService;

    public function __construct(NewsletterService $newsletterService, ContactMessageService $contactMessageService)
    {
        $this->newsletterService = $newsletterService;
        $this->contactMessageService = $contactMessageService;
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

    /**
     * Suscribir al newsletter
     */
    public function subscribeToNewsletter(Request $request)
    {
        try {
            $result = $this->newsletterService->subscribe($request->email);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la suscripción. Por favor, intenta nuevamente.'
            ], 422);
        }
    }

    /**
     * Enviar mensaje de contacto
     */
    public function sendContactMessage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->contactMessageService->sendContactMessage($request->all());
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje. Por favor, intenta nuevamente.'
            ], 422);
        }
    }
}
