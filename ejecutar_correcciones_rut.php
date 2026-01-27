
<?php
// Ejecutar con: php ejecutar_correcciones_rut.php

require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ============================================
// CORRECCIONES DE RUT
// ============================================

$corrections = [
    [
        'name' => 'Eloísa Paz Carvajal Borghero',
        'old' => '220829306',
        'new' => '220829308',
    ],
    [
        'name' => 'Sofia Esperanza Figueroa Saavedra',
        'old' => '231289180',
        'new' => '231289186',
    ],
    [
        'name' => 'Santiago Ignacio Wilkins Sibona',
        'old' => '231031997',
        'new' => '231031995',
    ],
    [
        'name' => 'Josefa Emilia Pizarro Cerna',
        'old' => '23186414K',
        'new' => '231864148',
    ],
    [
        'name' => 'Florencia Carrasco Bravo',
        'old' => '232727277',
        'new' => '232727276',
    ],
    [
        'name' => 'Alejandra Gimenez Arenas',
        'old' => '242553445',
        'new' => '242553446',
    ],
    [
        'name' => 'Jose Miguel Grez Perez',
        'old' => '233331983',
        'new' => '233331988',
    ],
    [
        'name' => 'Vicente Tomas Cortes Bravo',
        'old' => '232022501',
        'new' => '232022507',
    ],
    [
        'name' => 'Luciano Alfonso Roman Marquez',
        'old' => '221176926',
        'new' => '221176928',
    ],
    [
        'name' => 'Rafaela Antonia Ruidiaz Vasquez',
        'old' => '234638698',
        'new' => '234638696',
    ],
    [
        'name' => 'Maximiliano Lozada Ramirez',
        'old' => '245013437',
        'new' => '245013434',
    ],
    [
        'name' => 'Gabriela Xavier Gloeden',
        'old' => '281371399',
        'new' => '281371398',
    ],
    [
        'name' => 'Nicolas David Arriagada Berg',
        'old' => '234666600',
        'new' => '234666606',
    ],
    [
        'name' => 'Florencia Agustina Corrales Henriquez',
        'old' => '235956658',
        'new' => '235956659',
    ],
    [
        'name' => 'Vania Paola Vega Zuleta',
        'old' => '222894054',
        'new' => '222894050',
    ],
    [
        'name' => 'Martin Ignacio Gonzalez Carrasco',
        'old' => '220875375',
        'new' => '220875377',
    ],
    [
        'name' => 'Magdalena Martinez Jalilie',
        'old' => '229824642',
        'new' => '229824643',
    ],
    [
        'name' => 'Antonia Raquel Hermosilla Vejar',
        'old' => '231043480',
        'new' => '231043489',
    ],
    [
        'name' => 'Ramiro Antonio Mendez Lanas',
        'old' => '13846773K',
        'new' => '138467732',
    ],
    [
        'name' => 'Matilda Galvez Gudenschwager',
        'old' => '233106492',
        'new' => '233106496',
    ],
    [
        'name' => 'Martin Andres Aravena Marin',
        'old' => '230753386',
        'new' => '230753385',
    ],
    [
        'name' => 'Florencia Antonia Larraguibel Arancibia',
        'old' => '229681359',
        'new' => '229681354',
    ],
    [
        'name' => 'Martina Ignacia Muñoz Delgado',
        'old' => '231014699',
        'new' => '231014691',
    ],
    [
        'name' => 'Diego Ignacio Suau Puig',
        'old' => '230831208',
        'new' => '230831203',
    ],
    [
        'name' => 'Diego Alfonso Villouta Darricades',
        'old' => '236211727',
        'new' => '23621172K',
    ],
    [
        'name' => 'Isidora Paz Werner Leitao',
        'old' => '23058212K',
        'new' => '230582122',
    ],
    [
        'name' => 'Tomas Jose Onofre Aspillaga Cook',
        'old' => '234969229',
        'new' => '234969226',
    ],
    [
        'name' => 'Olivia Elena Frasisti Guevara',
        'old' => '238038926',
        'new' => '238038928',
    ],
    [
        'name' => 'Martin Ossa Isla',
        'old' => '233256558',
        'new' => '233256552',
    ],
    [
        'name' => 'Ellen Ostergaard Lenler',
        'old' => '214851905',
        'new' => '214851903',
    ],
    [
        'name' => 'Emilia Ducheylard Rebolledo',
        'old' => '234027030',
        'new' => '234027034',
    ],
];

echo "==========================================".PHP_EOL;
echo "CORRECCIÓN MASIVA DE RUTS".PHP_EOL;
echo "==========================================".PHP_EOL.PHP_EOL;
echo "Total correcciones a realizar: " . count($corrections) . PHP_EOL;
echo "¿Deseas continuar? (escribe SI para confirmar): ";
$confirm = trim(fgets(STDIN));

if (strtoupper($confirm) !== "SI") {
    echo "Operación cancelada.".PHP_EOL;
    exit;
}

DB::beginTransaction();

try {
    echo PHP_EOL."Ejecutando correcciones...".PHP_EOL.PHP_EOL;

    foreach ($corrections as $index => $correction) {
        echo ($index + 1) . ". " . $correction["name"] . PHP_EOL;
        echo "   " . $correction["old"] . " -> " . $correction["new"] . PHP_EOL;

        $updated = [];
        $updated["participants"] = DB::table("participants")
            ->where("document_number", $correction["old"])
            ->update(["document_number" => $correction["new"]]);

        $updated["orders_detail"] = DB::table("orders_detail")
            ->where("document_number", $correction["old"])
            ->update(["document_number" => $correction["new"]]);

        $updated["ecommerce_analytics"] = DB::table("ecommerce_analytics")
            ->where("participant_rut", $correction["old"])
            ->update(["participant_rut" => $correction["new"]]);

        // Actualizar participant_program (mayúscula)
        $updated["participant_program"] = DB::table("participant_program")
            ->where("enrollment_code", "LIKE", $correction["old"] . "-%")
            ->update(["enrollment_code" => DB::raw("REPLACE(enrollment_code, '".$correction["old"]."', '".$correction["new"]."')")]);

        // Si el RUT termina en K, también buscar con k minúscula
        if (substr($correction["old"], -1) === 'K') {
            $oldLowerK = substr($correction["old"], 0, -1) . 'k';
            $countLowerK = DB::table("participant_program")
                ->where("enrollment_code", "LIKE", $oldLowerK . "-%")
                ->update(["enrollment_code" => DB::raw("REPLACE(enrollment_code, '".$oldLowerK."', '".$correction["new"]."')")]);
            $updated["participant_program"] += $countLowerK;
        }

        echo "   Actualizados: participants={$updated["participants"]}, orders_detail={$updated["orders_detail"]}, ";
        echo "ecommerce={$updated["ecommerce_analytics"]}, participant_program={$updated["participant_program"]}".PHP_EOL.PHP_EOL;
    }

    echo PHP_EOL."==========================================".PHP_EOL;
    echo "VERIFICACIÓN".PHP_EOL;
    echo "==========================================".PHP_EOL.PHP_EOL;

    $newRuts = array_column($corrections, "new");

    $count1 = DB::table("participants")->whereIn("document_number", $newRuts)->count();
    echo "1. participants: $count1 registros".PHP_EOL;

    $count2 = DB::table("orders_detail")->whereIn("document_number", $newRuts)->count();
    echo "2. orders_detail: $count2 registros".PHP_EOL;

    $count3 = DB::table("ecommerce_analytics")->whereIn("participant_rut", $newRuts)->count();
    echo "3. ecommerce_analytics: $count3 registros".PHP_EOL;

    $count4 = DB::table("participant_program")
        ->where(function($q) use ($newRuts) {
            foreach ($newRuts as $rut) {
                $q->orWhere("enrollment_code", "LIKE", $rut . "-%");
            }
        })
        ->count();
    echo "4. participant_program: $count4 registros".PHP_EOL.PHP_EOL;

    echo "¿Todo se ve correcto? Escribe COMMIT para guardar o ROLLBACK para cancelar: ";
    $action = trim(fgets(STDIN));

    if (strtoupper($action) === "COMMIT") {
        DB::commit();
        echo PHP_EOL."✅ Cambios guardados exitosamente!".PHP_EOL;
    } else {
        DB::rollBack();
        echo PHP_EOL."❌ Cambios revertidos. No se guardó nada.".PHP_EOL;
    }

} catch (Exception $e) {
    DB::rollBack();
    echo PHP_EOL."❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Todos los cambios fueron revertidos.".PHP_EOL;
}
