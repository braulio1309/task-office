<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entity_permissions', function (Blueprint $table) {
            $table->id();

            // Relación con el Rol (Asumiendo tabla 'roles')
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');

            // Polimorfismo: permite asignar a 'App\Models\Folder' o 'App\Models\Document'
            $table->morphs('permissible');

            // Nivel de acceso
            // Ejemplo: 'read', 'write', 'full_access'
            $table->enum('access_level', ['view', 'download', 'upload', 'edit', 'delete']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entity_permissions');
    }
};
