
<?php
// SCRIPT DE REVERSIÓN - Ejecutar con: php revertir_correcciones_rut.php
// IMPORTANTE: Este script REVIERTE las correcciones (cambia RUTs correctos a incorrectos)
// Solo usar en caso de necesitar deshacer después de hacer COMMIT

require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ============================================
// REVERSIÓN DE CORRECCIONES DE RUT
// Cambia: RUT CORRECTO -> RUT INCORRECTO
// ============================================

$corrections = [
    [
        'name' => 'Eloísa Paz Carvajal Borghero',
        'old' => '220829308',  // RUT correcto
        'new' => '220829306',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Sofia Esperanza Figueroa Saavedra',
        'old' => '231289186',  // RUT correcto
        'new' => '231289180',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Santiago Ignacio Wilkins Sibona',
        'old' => '231031995',  // RUT correcto
        'new' => '231031997',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Josefa Emilia Pizarro Cerna',
        'old' => '231864148',  // RUT correcto
        'new' => '23186414K',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Florencia Carrasco Bravo',
        'old' => '232727276',  // RUT correcto
        'new' => '232727277',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Alejandra Gimenez Arenas',
        'old' => '242553446',  // RUT correcto
        'new' => '242553445',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Jose Miguel Grez Perez',
        'old' => '233331988',  // RUT correcto
        'new' => '233331983',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Vicente Tomas Cortes Bravo',
        'old' => '232022507',  // RUT correcto
        'new' => '232022501',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Luciano Alfonso Roman Marquez',
        'old' => '221176928',  // RUT correcto
        'new' => '221176926',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Rafaela Antonia Ruidiaz Vasquez',
        'old' => '234638696',  // RUT correcto
        'new' => '234638698',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Maximiliano Lozada Ramirez',
        'old' => '245013434',  // RUT correcto
        'new' => '245013437',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Gabriela Xavier Gloeden',
        'old' => '281371398',  // RUT correcto
        'new' => '281371399',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Nicolas David Arriagada Berg',
        'old' => '234666606',  // RUT correcto
        'new' => '234666600',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Florencia Agustina Corrales Henriquez',
        'old' => '235956659',  // RUT correcto
        'new' => '235956658',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Vania Paola Vega Zuleta',
        'old' => '222894050',  // RUT correcto
        'new' => '222894054',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Martin Ignacio Gonzalez Carrasco',
        'old' => '220875377',  // RUT correcto
        'new' => '220875375',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Magdalena Martinez Jalilie',
        'old' => '229824643',  // RUT correcto
        'new' => '229824642',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Antonia Raquel Hermosilla Vejar',
        'old' => '231043489',  // RUT correcto
        'new' => '231043480',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Ramiro Antonio Mendez Lanas',
        'old' => '138467732',  // RUT correcto
        'new' => '13846773K',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Matilda Galvez Gudenschwager',
        'old' => '233106496',  // RUT correcto
        'new' => '233106492',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Martin Andres Aravena Marin',
        'old' => '230753385',  // RUT correcto
        'new' => '230753386',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Florencia Antonia Larraguibel Arancibia',
        'old' => '229681354',  // RUT correcto
        'new' => '229681359',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Martina Ignacia Muñoz Delgado',
        'old' => '231014691',  // RUT correcto
        'new' => '231014699',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Diego Ignacio Suau Puig',
        'old' => '230831203',  // RUT correcto
        'new' => '230831208',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Diego Alfonso Villouta Darricades',
        'old' => '23621172K',  // RUT correcto
        'new' => '236211727',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Isidora Paz Werner Leitao',
        'old' => '230582122',  // RUT correcto
        'new' => '23058212K',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Tomas Jose Onofre Aspillaga Cook',
        'old' => '234969226',  // RUT correcto
        'new' => '234969229',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Olivia Elena Frasisti Guevara',
        'old' => '238038928',  // RUT correcto
        'new' => '238038926',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Martin Ossa Isla',
        'old' => '233256552',  // RUT correcto
        'new' => '233256558',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Ellen Ostergaard Lenler',
        'old' => '214851903',  // RUT correcto
        'new' => '214851905',  // RUT incorrecto (revertir)
    ],
    [
        'name' => 'Emilia Ducheylard Rebolledo',
        'old' => '234027034',  // RUT correcto
        'new' => '234027030',  // RUT incorrecto (revertir)
    ],
];

echo "==========================================".PHP_EOL;
echo "⚠️  REVERSIÓN DE CORRECCIONES DE RUT".PHP_EOL;
echo "==========================================".PHP_EOL;
echo "ADVERTENCIA: Este script REVIERTE las correcciones.".PHP_EOL;
echo "Cambiará los RUTs CORRECTOS de vuelta a los INCORRECTOS.".PHP_EOL;
echo "Solo usar si necesitas deshacer las correcciones.".PHP_EOL.PHP_EOL;
echo "Total reversiones a realizar: " . count($corrections) . PHP_EOL.PHP_EOL;
echo "¿ESTÁS SEGURO? Escribe REVERTIR para continuar: ";
$confirm = trim(fgets(STDIN));

if (strtoupper($confirm) !== "REVERTIR") {
    echo "Operación cancelada.".PHP_EOL;
    exit;
}

DB::beginTransaction();

try {
    echo PHP_EOL."Revirtiendo correcciones...".PHP_EOL.PHP_EOL;

    foreach ($corrections as $index => $correction) {
        echo ($index + 1) . ". " . $correction["name"] . PHP_EOL;
        echo "   " . $correction["old"] . " (correcto) -> " . $correction["new"] . " (incorrecto)".PHP_EOL;

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

        // Actualizar participant_program
        $updated["participant_program"] = DB::table("participant_program")
            ->where("enrollment_code", "LIKE", $correction["old"] . "-%")
            ->update(["enrollment_code" => DB::raw("REPLACE(enrollment_code, '".$correction["old"]."', '".$correction["new"]."')")]);

        echo "   Revertidos: participants={$updated["participants"]}, orders_detail={$updated["orders_detail"]}, ";
        echo "ecommerce={$updated["ecommerce_analytics"]}, participant_program={$updated["participant_program"]}".PHP_EOL.PHP_EOL;
    }

    echo PHP_EOL."==========================================".PHP_EOL;
    echo "VERIFICACIÓN".PHP_EOL;
    echo "==========================================".PHP_EOL.PHP_EOL;

    $oldRuts = array_column($corrections, "new");  // Los "incorrectos" ahora

    $count1 = DB::table("participants")->whereIn("document_number", $oldRuts)->count();
    echo "1. participants con RUTs incorrectos: $count1 registros".PHP_EOL;

    $count2 = DB::table("orders_detail")->whereIn("document_number", $oldRuts)->count();
    echo "2. orders_detail con RUTs incorrectos: $count2 registros".PHP_EOL;

    $count3 = DB::table("ecommerce_analytics")->whereIn("participant_rut", $oldRuts)->count();
    echo "3. ecommerce_analytics con RUTs incorrectos: $count3 registros".PHP_EOL;

    $count4 = DB::table("participant_program")
        ->where(function($q) use ($oldRuts) {
            foreach ($oldRuts as $rut) {
                $q->orWhere("enrollment_code", "LIKE", $rut . "-%");
            }
        })
        ->count();
    echo "4. participant_program con RUTs incorrectos: $count4 registros".PHP_EOL.PHP_EOL;

    echo "¿Confirmar reversión? Escribe COMMIT para guardar o ROLLBACK para cancelar: ";
    $action = trim(fgets(STDIN));

    if (strtoupper($action) === "COMMIT") {
        DB::commit();
        echo PHP_EOL."✅ Reversión completada. Los RUTs volvieron a estar incorrectos.".PHP_EOL;
    } else {
        DB::rollBack();
        echo PHP_EOL."❌ Reversión cancelada. No se cambió nada.".PHP_EOL;
    }

} catch (Exception $e) {
    DB::rollBack();
    echo PHP_EOL."❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Todos los cambios fueron revertidos.".PHP_EOL;
}
