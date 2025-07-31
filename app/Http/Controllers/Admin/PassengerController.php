<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PassengerController extends Controller
{
    public function index(Request $request)
    {
        $query = Passenger::with(['program', 'payments']);

        // Filtros
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $passengers = $query->latest()->paginate(15);

        // Estadísticas
        $stats = [
            'total' => Passenger::count(),
            'confirmed' => Passenger::where('status', 'active')->count(),
            'pending' => Passenger::where('status', 'inactive')->count(),
            'cancelled' => Passenger::where('status', 'withdrawn')->count(),
        ];

        return Inertia::render('Admin/Passengers/Index', [
            'passengers' => $passengers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function create()
    {
        $programs = Program::where('status', 'active')->get();

        return Inertia::render('Admin/Passengers/Create', [
            'programs' => $programs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rut' => 'required|string|unique:passengers',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'status' => 'required|in:active,inactive,withdrawn'
        ]);

        // Obtener el precio del programa
        $program = Program::findOrFail($validated['program_id']);
        $validated['individual_price'] = $program->price_per_passenger;

        $passenger = Passenger::create($validated);

        return redirect()->route('admin.passengers.index')
            ->with('success', 'Pasajero creado exitosamente.');
    }

    public function show(Passenger $passenger)
    {
        $passenger->load(['program', 'payments', 'contracts']);

        return Inertia::render('Admin/Passengers/Show', [
            'passenger' => $passenger
        ]);
    }

    public function edit(Passenger $passenger)
    {
        $programs = Program::where('active', '1')->get();

        return Inertia::render('Admin/Passengers/Edit', [
            'passenger' => $passenger,
            'programs' => $programs
        ]);
    }

    public function update(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'rut' => 'required|string|unique:passengers,rut,' . $passenger->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'status' => 'required|in:active,inactive,withdrawn'
        ]);

        $passenger->update($validated);

        return redirect()->route('admin.passengers.index')
            ->with('success', 'Pasajero actualizado exitosamente.');
    }

    public function destroy(Passenger $passenger)
    {
        $passenger->delete();

        return redirect()->route('admin.passengers.index')
            ->with('success', 'Pasajero eliminado exitosamente.');
    }

    public function updatePrice(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'price_adjustment' => 'required|numeric',
            'adjustment_reason' => 'required|string'
        ]);

        $passenger->update([
            'price_adjustments' => $passenger->price_adjustments + $validated['price_adjustment'],
            'adjustment_reason' => $validated['adjustment_reason']
        ]);

        return back()->with('success', 'Precio del pasajero actualizado.');
    }

    public function generatePaymentLink(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:transbank_webpay,khipu,both',
            'description' => 'required|string',
            'expires_hours' => 'required|integer|min:1|max:168' // Máximo 7 días
        ]);

        $paymentLink = $passenger->paymentLinks()->create([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'description' => $validated['description'],
            'status' => 'active',
            'expires_at' => now()->addHours($validated['expires_hours'])
        ]);

        return back()->with('success', 'Link de pago generado: ' . $paymentLink->url);
    }
}
