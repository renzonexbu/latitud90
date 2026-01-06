<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class ImportHardcodedSchools extends Command
{
    protected $signature = 'schools:import-hardcoded';
    protected $description = 'Importa los colegios hardcodeados del frontend a la base de datos';

    public function handle()
    {
        $this->info('Importando colegios hardcodeados...');

        $hardcodedSchools = [
            ["name" => "Alianza Francesa - Viña del Mar", "logo" => "/images/schools/Alianza Francesa - Vina del Mar.png"],
            ["name" => "British High School", "logo" => "/images/schools/British High School.png"],
            ["name" => "Colegio Aleman de Chicureo", "logo" => "/images/schools/Colegio Aleman de Chicureo.png"],
            ["name" => "Colegio Aleman de Santiago", "logo" => "/images/schools/Colegio Aleman de Santiago.png"],
            ["name" => "colegio altamira", "logo" => "/images/schools/colegio altamira.png"],
            ["name" => "Colegio Arrayanes - San Fernando", "logo" => "/images/schools/Colegio Arrayanes - San Fernando.png"],
            ["name" => "Colegio Cahuala-Castro", "logo" => "/images/schools/Colegio Cahuala-Castro.png"],
            ["name" => "Colegio Cambridge College - Providencia", "logo" => "/images/schools/Colegio Cambridge College - Providencia.png"],
            ["name" => "Colegio Campanario", "logo" => "/images/schools/Colegio Campanario.png"],
            ["name" => "colegio carampangue", "logo" => "/images/schools/colegio carampangue.png"],
            ["name" => "Colegio Cordillera", "logo" => "/images/schools/Colegio Cordillera.png"],
            ["name" => "Colegio Cumbres", "logo" => "/images/schools/Colegio Cumbres.png"],
            ["name" => "Colegio Dunalastair", "logo" => "/images/schools/Colegio Dunalastair.jpeg"],
            ["name" => "Colegio Everest", "logo" => "/images/schools/Colegio Everest.png"],
            ["name" => "Colegio Highlands", "logo" => "/images/schools/Colegio Highlands.png"],
            ["name" => "Colegio Ingles de Talca", "logo" => "/images/schools/Colegio Ingles de Talca.png"],
            ["name" => "Colegio Itahue - Concepción", "logo" => "/images/schools/Colegio Itahue - Concepcion.png"],
            ["name" => "Colegio Kilpatrick", "logo" => "/images/schools/Colegio Kilpatrick.png"],
            ["name" => "Colegio Kimen Montessori", "logo" => "/images/schools/Colegio Kimen Montessori.png"],
            ["name" => "Colegio La Cruz-Rancagua", "logo" => "/images/schools/Colegio La Cruz-Rancagua.png"],
            ["name" => "Colegio La maisonnete", "logo" => "/images/schools/Colegio La maisonnete.png"],
            ["name" => "Colegio Los Alerces", "logo" => "/images/schools/Colegio Los Alerces.png"],
            ["name" => "Colegio Mariano de Schoenstatt", "logo" => "/images/schools/Colegio Mariano de Schoenstatt.png"],
            ["name" => "Colegio Mayor de Peñalolén", "logo" => "/images/schools/Colegio Mayor de Penalolen.png"],
            ["name" => "Colegio Nido de Aguilas", "logo" => "/images/schools/Colegio Nido de Aguilas.png"],
            ["name" => "Colegio Padre Hurtado y Juanita de los Andes", "logo" => "/images/schools/Colegio Padre Hurtado y Juanita de los Andes.png"],
            ["name" => "Colegio Pedro de Valdivia", "logo" => "/images/schools/Colegio Pedro de Valdivia.png"],
            ["name" => "Colegio Pinares - Concepción", "logo" => "/images/schools/Colegio Pinares - Concepcion.png"],
            ["name" => "Colegio Pucalán Montessori", "logo" => "/images/schools/Colegio Pucalan Montessori.png"],
            ["name" => "colegio saint george", "logo" => "/images/schools/colegio saint george.png"],
            ["name" => "Colegio San Esteban Diacono", "logo" => "/images/schools/Colegio San Esteban Diacono.png"],
            ["name" => "Colegio San Felipe Diacono", "logo" => "/images/schools/Colegio San Felipe Diacono.png"],
            ["name" => "COLEGIO SAN JUAN EVANGELISTA", "logo" => "/images/schools/COLEGIO SAN JUAN EVANGELISTA.png"],
            ["name" => "COLEGIO SAN LUIS DE ALBA - VALDIVIA", "logo" => "/images/schools/COLEGIO SAN LUIS DE ALBA - VALDIVIA.png"],
            ["name" => "COLEGIO SAN MIGUEL ARCANGEL", "logo" => "/images/schools/COLEGIO SAN MIGUEL ARCANGEL.png"],
            ["name" => "Colegio san nicolas de myra", "logo" => "/images/schools/Colegio san nicolas de myra.png"],
            ["name" => "Colegio San Pedro de Nolasco", "logo" => "/images/schools/Colegio San Pedro de Nolasco.png"],
            ["name" => "COLEGIO SANTA URSULA DE VITACURA", "logo" => "/images/schools/COLEGIO SANTA URSULA DE VITACURA.png"],
            ["name" => "Colegio SSCC de Apoquindo", "logo" => "/images/schools/Colegio SSCC de Apoquindo.png"],
            ["name" => "Colegio St John's-Concepción", "logo" => "/images/schools/Colegio St Johns-Concepcion.png"],
            ["name" => "Colegio Suizo", "logo" => "/images/schools/Colegio Suizo.png"],
            ["name" => "Colegio TEO", "logo" => "/images/schools/Colegio TEO.png"],
            ["name" => "Liceo Manuel de Salas", "logo" => "/images/schools/Liceo Manuel de Salas.png"],
            ["name" => "Lincoln International Academy", "logo" => "/images/schools/Lincoln International Academy.png"],
            ["name" => "Orchard College - Curicó", "logo" => "/images/schools/Orchard College - Curico.png"],
            ["name" => "Redland School", "logo" => "/images/schools/Redland School.png"],
            ["name" => "SAINT GABRIEL SCHOOL", "logo" => "/images/schools/SAINT GABRIEL SCHOOL.png"],
            ["name" => "santiago college", "logo" => "/images/schools/santiago college.png"],
            ["name" => "Scuola Italiana", "logo" => "/images/schools/Scuola Italiana.png"],
            ["name" => "SOUTHERN CROSS SCHOOL", "logo" => "/images/schools/SOUTHERN CROSS SCHOOL.png"],
            ["name" => "St Gaspar College", "logo" => "/images/schools/St Gaspar College.png"],
            ["name" => "ST JOHNS VILLA ACADEMY", "logo" => "/images/schools/ST JOHNS VILLA ACADEMY.png"],
            ["name" => "The Craighouse School", "logo" => "/images/schools/The Craighouse School.png"],
            ["name" => "The Grange School", "logo" => "/images/schools/The Grange School.png"],
            ["name" => "The Newland School", "logo" => "/images/schools/The Newland School.png"],
            ["name" => "The Southland School", "logo" => "/images/schools/The Southland School.png"],
            ["name" => "The Trewhelas School-Chicureo", "logo" => "/images/schools/The Trewhelas School-Chicureo.png"],
            ["name" => "Trebulco School", "logo" => "/images/schools/Trebulco School.png"],
            ["name" => "Verbo Divino Chicureo", "logo" => "/images/schools/Verbo Divino Chicureo.png"],
            ["name" => "VERBO DIVINO", "logo" => "/images/schools/VERBO DIVINO.png"],
            ["name" => "VILLA MARIA", "logo" => "/images/schools/VILLA MARIA.png"],
            ["name" => "WENLOCK", "logo" => "/images/schools/WENLOCK.png"],
        ];

        $order = 1;
        $imported = 0;
        $skipped = 0;

        foreach ($hardcodedSchools as $schoolData) {
            // Verificar si ya existe un colegio con el mismo nombre
            $existing = School::where('name', $schoolData['name'])->first();

            if ($existing) {
                $this->warn("  ⏭  Saltado: {$schoolData['name']} (ya existe)");
                $skipped++;
                continue;
            }

            School::create([
                'name' => $schoolData['name'],
                'logo' => $schoolData['logo'],
                'order' => $order++,
                'is_active' => true,
            ]);

            $this->info("  ✓  Importado: {$schoolData['name']}");
            $imported++;
        }

        $this->newLine();
        $this->info("✅ Importación completada:");
        $this->info("   - Colegios importados: {$imported}");
        $this->info("   - Colegios saltados (ya existían): {$skipped}");
        $this->info("   - Total en base de datos: " . School::count());

        return 0;
    }
}
