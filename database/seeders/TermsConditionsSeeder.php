<?php

namespace Database\Seeders;

use App\Models\TermCondition;
use Illuminate\Database\Seeder;

class TermsConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            [
                'title' => 'Aceptación de los Términos',
                'content' => 'Al acceder y utilizar los servicios de Latitud 90, usted acepta estar sujeto a estos términos y condiciones. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestros servicios.',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Descripción del Servicio',
                'content' => 'Latitud 90 ofrece servicios de viajes educativos, tours, excursiones, intercambios estudiantiles y cruceros. Nos reservamos el derecho de modificar, suspender o discontinuar cualquier aspecto de nuestros servicios en cualquier momento.',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Reservas y Pagos',
                'content' => 'Las reservas están sujetas a disponibilidad. Los pagos deben realizarse según las condiciones especificadas para cada programa. Los precios están sujetos a cambios sin previo aviso hasta que se confirme la reserva.',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Cancelaciones y Reembolsos',
                'content' => 'Las políticas de cancelación varían según el programa. Consulte las condiciones específicas de cada viaje. Los reembolsos están sujetos a las políticas de nuestros proveedores y pueden incurrir en cargos administrativos.',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Responsabilidades del Viajero',
                'content' => 'Los viajeros son responsables de obtener la documentación necesaria, incluyendo pasaportes, visas y vacunas requeridas. También deben cumplir con las leyes y regulaciones del país de destino.',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Limitación de Responsabilidad',
                'content' => 'Latitud 90 no será responsable por daños indirectos, incidentales o consecuentes que puedan surgir del uso de nuestros servicios, excepto donde la ley lo requiera.',
                'position' => 6,
                'is_active' => true,
            ],
            [
                'title' => 'Privacidad y Datos Personales',
                'content' => 'Su privacidad es importante para nosotros. Consulte nuestra Política de Privacidad para obtener información sobre cómo recopilamos, usamos y protegemos su información personal.',
                'position' => 7,
                'is_active' => true,
            ],
            [
                'title' => 'Modificaciones',
                'content' => 'Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento. Los cambios entrarán en vigor inmediatamente después de su publicación en nuestro sitio web.',
                'position' => 8,
                'is_active' => true,
            ],
            [
                'title' => 'Contacto',
                'content' => 'Si tiene alguna pregunta sobre estos términos y condiciones, puede contactarnos a través de nuestros canales de atención al cliente disponibles en nuestro sitio web.',
                'position' => 9,
                'is_active' => true,
            ],
        ];

        foreach ($terms as $term) {
            TermCondition::updateOrCreate(
                ['title' => $term['title']],
                $term
            );
        }
    }
}
