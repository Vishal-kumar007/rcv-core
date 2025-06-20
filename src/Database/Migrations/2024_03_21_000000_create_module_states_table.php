<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('module_states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('disabled');
            $table->boolean('enabled')->default(false);
            $table->json('applied_migrations')->nullable();
            $table->json('failed_migrations')->nullable();
            $table->timestamp('last_enabled_at')->nullable();
            $table->timestamp('last_disabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_states');
    }
}; 