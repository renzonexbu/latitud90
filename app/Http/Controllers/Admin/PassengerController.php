<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant as Passenger;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Admin\Passengers\StorePassengerRequest;
use App\Http\Requests\Admin\Passengers\UpdatePassengerRequest;
use App\Http\Requests\Admin\Passengers\UpdatePriceRequest;
use App\Http\Requests\Admin\Passengers\GeneratePaymentLinkRequest;
use App\Services\Admin\Passengers\PassengerService;

class PassengerController extends Controller
{
    public function __construct(private PassengerService $passengerService) {}

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

    public function store(StorePassengerRequest $request)
    {
        $this->passengerService->create($request->validated());

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

    public function update(UpdatePassengerRequest $request, Passenger $passenger)
    {
        $this->passengerService->update($request->validated(), $passenger);

        return redirect()->route('admin.passengers.index')
            ->with('success', 'Pasajero actualizado exitosamente.');
    }

    public function destroy(Passenger $passenger)
    {
        $passenger->delete();

        return redirect()->route('admin.passengers.index')
            ->with('success', 'Pasajero eliminado exitosamente.');
    }

    public function updatePrice(UpdatePriceRequest $request, Passenger $passenger)
    {
        $this->passengerService->updatePrice($request->validated(), $passenger);

        return back()->with('success', 'Precio del pasajero actualizado.');
    }

    public function generatePaymentLink(GeneratePaymentLinkRequest $request, Passenger $passenger)
    {
        $url = $this->passengerService->generatePaymentLink($request->validated(), $passenger);
        return back()->with('success', 'Link de pago generado: ' . $url);
    }
}
