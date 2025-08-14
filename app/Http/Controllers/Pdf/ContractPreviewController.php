<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ContractPreviewController extends Controller
{
	public function show(Request $request)
	{
		// Datos de prueba (puedes sobreescribir vía querystring)
		$data = [
			'folio' => $request->query('folio', date('Ymd-His')),
			'empresa_direccion' => 'Carlos Antúnez 1941, Providencia',
			'empresa_region' => 'Región Metropolitana',
			'empresa_telefono' => '+56 9 7909 1738',
			'empresa_sitio' => 'www.latitud90.com',
			'empresa_nombre' => 'Experiencias Educativas y Capacitaciones SpA',
			'ciudad' => 'Santiago de Chile',
			'fecha' => now()->format('d \d\e F \d\e Y'),
			'prestador_nombre' => 'Experiencias Educativas y Capacitaciones SpA',
			'prestador_rut' => '76.203.719-K',
			'representante_nombre' => 'Carolina Emhart García',
			'representante_rut' => '13.670.825-2',
			'domicilio_ciudad' => 'Santiago',
			'domicilio_comuna' => 'Providencia',
			'domicilio_calle' => 'Carlos Antúnez 1941',
			'apoderado_nombre' => 'Juan Pérez',
			'apoderado_rut' => '12.345.678-9',
			'alumno_nombre' => 'María Pérez',
			'alumno_rut' => '26.256.475-4',
			'cotizacion_fecha' => now()->subDays(7)->format('d \d\e F \d\e Y'),
			'programa_anio' => now()->format('Y'),
			'programa_nombre' => 'Viaje Educativo de Ejemplo',
			'prestador_nombre_firma' => 'Experiencias Educativas y Capacitaciones SpA',
			'firmante_nombre' => 'Carmen Gutiérrez M.',
			'firmante_cargo' => 'Jefa área de recaudación',
		];

		$pdf = PDF::loadView('PDF.contract', $data)->setPaper('A4', 'portrait');
		return $pdf->stream('contrato-preview.pdf', ['Attachment' => false]);
	}
}


