<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\PaymentMode;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProgramsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer payment mode disponible
        $paymentMode = PaymentMode::first();
        
        if (!$paymentMode) {
            // Si no hay payment modes, crear uno por defecto
            $paymentMode = PaymentMode::create([
                'name' => 'Pago en cuotas',
                'description' => 'Pago dividido en cuotas mensuales',
                'active' => true
            ]);
        }

        // Programa 1: Aventura en Patagonia
        Program::create([
            'name' => 'Aventura en Patagonia - Torres del Paine',
            'destination' => 'Torres del Paine, Patagonia',
            'departure_date' => Carbon::now()->addDays(45)->format('Y-m-d'),
            'trip_description' => 'Un viaje épico por uno de los paisajes más impresionantes de Chile. Explora glaciares milenarios, lagos turquesas y montañas imponentes en el corazón de la Patagonia. Este programa incluye trekking por senderos únicos, navegación por lagos cristalinos y la oportunidad de observar fauna nativa en su hábitat natural.',
            'images_folder' => 'programs/patagonia-torres-paine',
            'pillars' => 'Aventura, Educación, Seguridad, Entretenimiento',
            'itinerary_description' => 'Día 1: Llegada a Puerto Natales y traslado al parque nacional. Día 2-3: Trekking Base Torres con vistas panorámicas. Día 4-5: Navegación por el Lago Grey hasta el glaciar. Día 6: Mirador Cuernos del Paine. Día 7: Retorno con recuerdos inolvidables.',
            'itinerary_file' => 'programs/files/itinerario-patagonia.pdf',
            'travel_assistance_coverage' => 'programs/files/cobertura-patagonia.pdf',
            'equipment_list' => 'programs/files/equipo-patagonia.pdf',
            'trip_price' => 1850000.00,
            'final_payment_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'seller_name' => 'María González',
            'payment_mode_id' => $paymentMode->id,
            'active' => true,
        ]);

        // Programa 2: Descubriendo el Norte
        Program::create([
            'name' => 'Descubriendo el Norte - Atacama y Altiplano',
            'destination' => 'San Pedro de Atacama, Desierto de Atacama',
            'departure_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'trip_description' => 'Sumérgete en la magia del desierto más árido del mundo. Descubre géiseres activos, lagunas de colores únicos, pueblos andinos auténticos y cielos estrellados que te dejarán sin aliento. Una experiencia que combina aventura, cultura y conexión con la naturaleza.',
            'images_folder' => 'programs/atacama-altiplano',
            'pillars' => 'Educación, Aventura, Seguridad',
            'itinerary_description' => 'Día 1: Llegada y exploración del Valle de la Luna. Día 2: Madrugada a los Géiseres del Tatio y visita al pueblo de Machuca. Día 3: Tour por las Lagunas Altiplánicas. Día 4: Salar de Atacama y observación astronómica. Día 5: Retorno con experiencias únicas.',
            'itinerary_file' => 'programs/files/itinerario-atacama.pdf',
            'travel_assistance_coverage' => 'programs/files/cobertura-atacama.pdf',
            'equipment_list' => 'programs/files/equipo-atacama.pdf',
            'trip_price' => 1250000.00,
            'final_payment_date' => Carbon::now()->addDays(20)->format('Y-m-d'),
            'seller_name' => 'Carlos Mendoza',
            'payment_mode_id' => $paymentMode->id,
            'active' => true,
        ]);

        // Programa 3: Expedición Antártica
        Program::create([
            'name' => 'Expedición Antártica - Continente Blanco',
            'destination' => 'Península Antártica',
            'departure_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
            'trip_description' => 'Una aventura única al continente más remoto del planeta. Navega entre icebergs gigantes, observa colonias de pingüinos, ballenas y focas en su hábitat natural. Una experiencia que pocos pueden vivir y que te marcará para siempre.',
            'images_folder' => 'programs/expedicion-antartica',
            'pillars' => 'Aventura, Educación, Seguridad, Entretenimiento',
            'itinerary_description' => 'Día 1-2: Vuelo a Punta Arenas y navegación por el Estrecho de Magallanes. Día 3-5: Cruce del Pasaje de Drake. Día 6-10: Exploración de la Península Antártica con desembarcos diarios. Día 11-13: Retorno con recuerdos inolvidables.',
            'itinerary_file' => 'programs/files/itinerario-antartica.pdf',
            'travel_assistance_coverage' => 'programs/files/cobertura-antartica.pdf',
            'equipment_list' => 'programs/files/equipo-antartica.pdf',
            'trip_price' => 3500000.00,
            'final_payment_date' => Carbon::now()->addDays(60)->format('Y-m-d'),
            'seller_name' => 'Ana Rodríguez',
            'payment_mode_id' => $paymentMode->id,
            'active' => true,
        ]);

        // Programa 4: Ruta de los Vinos
        Program::create([
            'name' => 'Ruta de los Vinos - Valle del Maipo',
            'destination' => 'Valle del Maipo, Región Metropolitana',
            'departure_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
            'trip_description' => 'Descubre los mejores vinos de Chile en un recorrido por las viñas más prestigiosas del Valle del Maipo. Degusta vinos premiados, aprende sobre el proceso de elaboración y disfruta de la gastronomía local en un ambiente sofisticado.',
            'images_folder' => 'programs/ruta-vinos-maipo',
            'pillars' => 'Educación, Entretenimiento, Seguridad',
            'itinerary_description' => 'Día 1: Visita a Viña Concha y Toro con degustación premium. Día 2: Tour por Viña Santa Rita y almuerzo en su restaurante. Día 3: Experiencia en Viña Undurraga con cata de vinos boutique.',
            'itinerary_file' => 'programs/files/itinerario-vinos.pdf',
            'travel_assistance_coverage' => 'programs/files/cobertura-vinos.pdf',
            'equipment_list' => 'programs/files/equipo-vinos.pdf',
            'trip_price' => 850000.00,
            'final_payment_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'seller_name' => 'Patricia Silva',
            'payment_mode_id' => $paymentMode->id,
            'active' => true,
        ]);
    }
}
