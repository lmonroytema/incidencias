<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            // Datos del empleado
            $table->string('dni_type');
            $table->string('dni_number');
            $table->string('full_name');
            $table->string('area_name')->nullable();
            $table->string('corporate_email');

            // Registro de incidencia
            $table->string('category'); // Conectividad, OneDrive/SharePoint, Outlook, Teams, Perfil/Licencias, Otros
            $table->json('apps')->nullable(); // apps afectadas (array)
            $table->text('description');
            $table->string('urgency'); // Critico, Alto, Medio, Bajo

            // Datos para el consultor
            $table->string('hostname')->nullable();
            $table->string('os')->nullable();
            $table->string('office_version')->nullable();
            $table->boolean('first_time')->default(false);
            $table->date('started_at')->nullable();

            // Seguimiento
            $table->string('status')->default('Pendiente'); // Pendiente, En revisión, Resuelto, Cerrado
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->text('consultant_notes')->nullable();
            $table->dateTime('resolution_date')->nullable();
            $table->text('solution_applied')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['status', 'category', 'urgency']);
            $table->index(['area_name']);
            $table->index(['assigned_to_id']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
