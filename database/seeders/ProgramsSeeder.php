<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ServiceType;
use App\Models\Feature;
use App\Models\Requirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProgramsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear Service Types
        $tours = ServiceType::firstOrCreate(['name' => 'Tours'], [
            'description' => 'Viajes turísticos organizados',
            'active' => true
        ]);
        
        $excursiones = ServiceType::firstOrCreate(['name' => 'Excursiones'], [
            'description' => 'Excursiones de día completo',
            'active' => true
        ]);

        // Crear Features (para includes/excludes)
        $features = [
            // Includes
            ['name' => 'Transporte terrestre', 'description' => 'Transporte en bus o van'],
            ['name' => 'Alojamiento', 'description' => 'Hospedaje durante el viaje'],
            ['name' => 'Todas las comidas', 'description' => 'Desayuno, almuerzo y cena'],
            ['name' => 'Guía especializado', 'description' => 'Guía turístico profesional'],
            ['name' => 'Equipo de trekking', 'description' => 'Equipo básico para caminatas'],
            ['name' => 'Seguro de viaje', 'description' => 'Seguro básico de accidentes'],
            ['name' => 'Transporte 4x4', 'description' => 'Vehículo todo terreno'],
            ['name' => 'Entradas a parques', 'description' => 'Tickets de acceso'],
            
            // Excludes  
            ['name' => 'Vuelos', 'description' => 'Pasajes aéreos'],
            ['name' => 'Equipamiento personal', 'description' => 'Ropa y equipo personal'],
            ['name' => 'Bebidas alcohólicas', 'description' => 'Alcohol no incluido'],
            ['name' => 'Propinas', 'description' => 'Gratificaciones'],
            ['name' => 'Gastos personales', 'description' => 'Compras individuales'],
            ['name' => 'Medicamentos', 'description' => 'Medicinas personales'],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(['name' => $feature['name']], $feature);
        }

        // Crear Requirements
        $requirements = [
            ['name' => 'Condición física buena', 'description' => 'Estado físico adecuado'],
            ['name' => 'Experiencia en trekking', 'description' => 'Conocimiento básico de caminatas'],
            ['name' => 'Documentos vigentes', 'description' => 'Cédula de identidad válida'],
            ['name' => 'Seguro médico', 'description' => 'Cobertura de salud'],
            ['name' => 'Ropa de montaña', 'description' => 'Vestimenta adecuada'],
            ['name' => 'Adaptación a altura', 'description' => 'Acostumbrarse a la altitud'],
            ['name' => 'Protección solar', 'description' => 'Bloqueador y gafas'],
        ];

        foreach ($requirements as $requirement) {
            Requirement::firstOrCreate(['name' => $requirement['name']], $requirement);
        }

        // Programa 1: Aventura en Patagonia
        $program1 = Program::create([
            'name' => 'Aventura en Patagonia - Torres del Paine',
            'description' => 'Un viaje épico por uno de los paisajes más impresionantes de Chile. Explora glaciares milenarios, lagos turquesas y montañas imponentes en el corazón de la Patagonia.',
            'service_type_id' => $tours->id,
            'destination' => 'Torres del Paine, Patagonia',
            'departure_date' => Carbon::now()->addDays(45)->format('Y-m-d'),
            'return_date' => Carbon::now()->addDays(52)->format('Y-m-d'),
            'duration_days' => 7,
            'capacity' => 25,
            'base_price' => 1850000.00,
            'itinerary' => 'Día 1: Llegada a Puerto Natales y traslado al parque. Día 2-3: Trekking Base Torres. Día 4-5: Navegación Grey. Día 6: Mirador Cuernos. Día 7: Retorno.',
            'active' => true,
        ]);

        // Relacionar Features (includes) con Programa 1
        $includesP1 = ['Transporte terrestre', 'Alojamiento', 'Todas las comidas', 'Guía especializado', 'Equipo de trekking', 'Seguro de viaje'];
        foreach ($includesP1 as $featureName) {
            $feature = Feature::where('name', $featureName)->first();
            if ($feature) {
                DB::table('programs_features')->insert([
                    'program_id' => $program1->id,
                    'feature_id' => $feature->id,
                    'type' => 'include',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar Features (excludes) con Programa 1
        $excludesP1 = ['Vuelos', 'Equipamiento personal', 'Bebidas alcohólicas', 'Propinas', 'Gastos personales'];
        foreach ($excludesP1 as $featureName) {
            $feature = Feature::where('name', $featureName)->first();
            if ($feature) {
                DB::table('programs_features')->insert([
                    'program_id' => $program1->id,
                    'feature_id' => $feature->id,
                    'type' => 'exclude',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar Requirements con Programa 1
        $requirementsP1 = ['Condición física buena', 'Experiencia en trekking', 'Documentos vigentes', 'Seguro médico', 'Ropa de montaña'];
        foreach ($requirementsP1 as $reqName) {
            $requirement = Requirement::where('name', $reqName)->first();
            if ($requirement) {
                DB::table('programs_requirements')->insert([
                    'program_id' => $program1->id,
                    'requirement_id' => $requirement->id,
                    'type' => 'include',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Programa 2: Descubriendo el Norte
        $program2 = Program::create([
            'name' => 'Descubriendo el Norte - Atacama y Altiplano',
            'description' => 'Sumérgete en la magia del desierto más árido del mundo. Descubre géiseres, lagunas de colores, pueblos andinos y cielos estrellados únicos.',
            'service_type_id' => $excursiones->id,
            'destination' => 'San Pedro de Atacama, Desierto de Atacama',
            'departure_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'return_date' => Carbon::now()->addDays(35)->format('Y-m-d'),
            'duration_days' => 5,
            'capacity' => 18,
            'base_price' => 1250000.00,
            'itinerary' => 'Día 1: Llegada y Valle de la Luna. Día 2: Géiseres del Tatio y Machuca. Día 3: Lagunas Altiplánicas. Día 4: Salar de Atacama. Día 5: Retorno.',
            'active' => true,
        ]);

        // Relacionar Features (includes) con Programa 2
        $includesP2 = ['Transporte 4x4', 'Alojamiento', 'Guía especializado', 'Entradas a parques'];
        foreach ($includesP2 as $featureName) {
            $feature = Feature::where('name', $featureName)->first();
            if ($feature) {
                DB::table('programs_features')->insert([
                    'program_id' => $program2->id,
                    'feature_id' => $feature->id,
                    'type' => 'include',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar Features (excludes) con Programa 2
        $excludesP2 = ['Vuelos', 'Medicamentos', 'Gastos personales'];
        foreach ($excludesP2 as $featureName) {
            $feature = Feature::where('name', $featureName)->first();
            if ($feature) {
                DB::table('programs_features')->insert([
                    'program_id' => $program2->id,
                    'feature_id' => $feature->id,
                    'type' => 'exclude',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar Requirements con Programa 2
        $requirementsP2 = ['Adaptación a altura', 'Protección solar', 'Documentos vigentes'];
        foreach ($requirementsP2 as $reqName) {
            $requirement = Requirement::where('name', $reqName)->first();
            if ($requirement) {
                DB::table('programs_requirements')->insert([
                    'program_id' => $program2->id,
                    'requirement_id' => $requirement->id,
                    'type' => 'include',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
