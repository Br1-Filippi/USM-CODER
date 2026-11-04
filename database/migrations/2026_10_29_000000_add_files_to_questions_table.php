<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Archivos de texto adjuntos a la pregunta, como {"nombre": "contenido"}.
     * Se cargan en la consola Pyodide y se mandan a Judge0 al calificar.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->json('files')->nullable()->after('execution_mode');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('files');
        });
    }
};
