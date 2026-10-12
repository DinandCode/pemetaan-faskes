<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ekstensi tabel faskes_field_definitions
        if (Schema::hasTable('faskes_field_definitions')) {
            Schema::table('faskes_field_definitions', function (Blueprint $table) {
                if (! Schema::hasColumn('faskes_field_definitions', 'config')) {
                    $table->jsonb('config')->nullable()->after('options');
                }
                if (! Schema::hasColumn('faskes_field_definitions', 'is_filterable')) {
                    $table->boolean('is_filterable')->default(true)->after('is_active');
                }
                if (! Schema::hasColumn('faskes_field_definitions', 'show_in_list')) {
                    $table->boolean('show_in_list')->default(true)->after('is_filterable');
                }
            });

            // Jadikan kolom options nullable (karena tipe text, date, dll. tidak memerlukan options dropdown)
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE faskes_field_definitions ALTER COLUMN options DROP NOT NULL;');
            }
        }

        // 2. Ekstensi tabel faskes_field_values
        if (Schema::hasTable('faskes_field_values')) {
            $driver = DB::getDriverName();

            // Ubah tipe kolom value dari varchar(255) ke text (Postgres / generic)
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE faskes_field_values ALTER COLUMN value TYPE text;');
            }

            Schema::table('faskes_field_values', function (Blueprint $table) {
                if (! Schema::hasColumn('faskes_field_values', 'value_number')) {
                    $table->decimal('value_number', 20, 6)->nullable()->after('value');
                }
                if (! Schema::hasColumn('faskes_field_values', 'value_date')) {
                    $table->date('value_date')->nullable()->after('value_number');
                }
                if (! Schema::hasColumn('faskes_field_values', 'value_bool')) {
                    $table->boolean('value_bool')->nullable()->after('value_date');
                }
                if (! Schema::hasColumn('faskes_field_values', 'value_json')) {
                    $table->jsonb('value_json')->nullable()->after('value_bool');
                }
            });

            // Tambahkan index komposit dan GIN index (idempotent)
            if ($driver === 'pgsql') {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_field_values_def_number ON faskes_field_values (field_definition_id, value_number);');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_field_values_def_date ON faskes_field_values (field_definition_id, value_date);');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_field_values_json_gin ON faskes_field_values USING gin (value_json);');
            } else {
                Schema::table('faskes_field_values', function (Blueprint $table) {
                    $table->index(['field_definition_id', 'value_number'], 'idx_faskes_field_values_def_number');
                    $table->index(['field_definition_id', 'value_date'], 'idx_faskes_field_values_def_date');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('faskes_field_values')) {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS idx_faskes_field_values_json_gin;');
                DB::statement('DROP INDEX IF EXISTS idx_faskes_field_values_def_date;');
                DB::statement('DROP INDEX IF EXISTS idx_faskes_field_values_def_number;');
            }

            Schema::table('faskes_field_values', function (Blueprint $table) {
                if (Schema::hasColumn('faskes_field_values', 'value_json')) {
                    $table->dropColumn('value_json');
                }
                if (Schema::hasColumn('faskes_field_values', 'value_bool')) {
                    $table->dropColumn('value_bool');
                }
                if (Schema::hasColumn('faskes_field_values', 'value_date')) {
                    $table->dropColumn('value_date');
                }
                if (Schema::hasColumn('faskes_field_values', 'value_number')) {
                    $table->dropColumn('value_number');
                }
            });
        }

        if (Schema::hasTable('faskes_field_definitions')) {
            Schema::table('faskes_field_definitions', function (Blueprint $table) {
                if (Schema::hasColumn('faskes_field_definitions', 'show_in_list')) {
                    $table->dropColumn('show_in_list');
                }
                if (Schema::hasColumn('faskes_field_definitions', 'is_filterable')) {
                    $table->dropColumn('is_filterable');
                }
                if (Schema::hasColumn('faskes_field_definitions', 'config')) {
                    $table->dropColumn('config');
                }
            });
        }
    }
};
