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
        if (! Schema::hasTable('faskes_field_definitions')) {
            Schema::create('faskes_field_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_faskes', 50)->nullable()->comment('NULL = berlaku untuk semua jenis faskes');
                $table->string('label', 100);
                $table->string('field_key', 100);
                $table->string('type', 30)->default('select');
                $table->jsonb('options')->comment('Array opsi string, maks 30 opsi');
                $table->boolean('is_required')->default(false);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('jenis_faskes');
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('faskes_field_values')) {
            Schema::create('faskes_field_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faskes_id')->constrained('faskes')->cascadeOnDelete();
                $table->foreignId('field_definition_id')->constrained('faskes_field_definitions')->cascadeOnDelete();
                $table->string('value', 255)->nullable();
                $table->timestamps();

                $table->unique(['faskes_id', 'field_definition_id']);
                $table->index('field_definition_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faskes_field_values');
        Schema::dropIfExists('faskes_field_definitions');
    }
};
