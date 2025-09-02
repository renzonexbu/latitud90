<?php

namespace App\Http\Controllers;

use App\Services\Client\Communication\NewsletterService;
use App\Services\Client\Communication\ContactMessageService;
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
            
            // Si es una petición AJAX, responder con JSON
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json($result);
            }
            
            // Si no es AJAX, responder con redirección (fallback)
            if ($result['success']) {
                return back()->with('contact_success', $result['message']);
            } else {
                return back()->withErrors(['contact_error' => $result['message']]);
            }
        } catch (\Exception $e) {
            $errorMessage = 'Error al enviar el mensaje. Por favor, intenta nuevamente.';
            
            // Si es una petición AJAX, responder con JSON
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422);
            }
            
            // Si no es AJAX, responder con redirección (fallback)
            return back()->withErrors(['contact_error' => $errorMessage]);
        }
    }
}
