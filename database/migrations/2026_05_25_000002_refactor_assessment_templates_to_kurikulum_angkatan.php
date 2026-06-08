<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_templates')) {
            $isSlite = DB::connection()->getDriverName() === 'sqlite';
            
            if ($isSlite) {
                // For SQLite, disable FK constraints to allow schema modifications
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            try {
                // Drop foreign keys (MySQL-specific check)
                Schema::table('assessment_templates', function (Blueprint $table) {
                    if (DB::connection()->getDriverName() === 'mysql') {
                        if ($this->hasForeignKey('assessment_templates', 'assessment_templates_kode_nama_kurikulum_foreign')) {
                            $table->dropForeign('assessment_templates_kode_nama_kurikulum_foreign');
                        }
                        if ($this->hasForeignKey('assessment_templates', 'assessment_templates_kode_tahun_akademik_foreign')) {
                            $table->dropForeign('assessment_templates_kode_tahun_akademik_foreign');
                        }
                    }
                });

                // Drop indexes (MySQL-specific check)
                Schema::table('assessment_templates', function (Blueprint $table) {
                    if (DB::connection()->getDriverName() === 'mysql') {
                        if ($this->hasIndex('assessment_templates', 'assessment_templates_kode_nama_kurikulum_index')) {
                            $table->dropIndex('assessment_templates_kode_nama_kurikulum_index');
                        }
                        if ($this->hasIndex('assessment_templates', 'assessment_templates_kode_tahun_akademik_index')) {
                            $table->dropIndex('assessment_templates_kode_tahun_akademik_index');
                        }
                        if ($this->hasIndex('assessment_templates', 'uq_template')) {
                            $table->dropUnique('uq_template');
                        }
                    }
                });

                // Add new column if not exists
                if (!Schema::hasColumn('assessment_templates', 'kode_kurikulum_angkatan')) {
                    Schema::table('assessment_templates', function (Blueprint $table) {
                        $table->unsignedInteger('kode_kurikulum_angkatan')->nullable()->after('id_matakuliah');
                    });
                }

                // Migrate data with SQLite-compatible syntax
                if ($isSlite) {
                    DB::update("
                        UPDATE assessment_templates
                        SET kode_kurikulum_angkatan = (
                            SELECT ka.kode_kurikulum_angkatan
                            FROM kurikulum_angkatan ka
                            WHERE ka.kode_nama_kurikulum = assessment_templates.kode_nama_kurikulum
                            LIMIT 1
                        )
                        WHERE EXISTS (
                            SELECT 1
                            FROM kurikulum_angkatan ka
                            WHERE ka.kode_nama_kurikulum = assessment_templates.kode_nama_kurikulum
                        )
                    ");
                } else {
                    // MySQL-compatible update with INNER JOIN
                    DB::update("
                        UPDATE assessment_templates ast
                        INNER JOIN kurikulum_angkatan ka
                            ON ast.kode_nama_kurikulum = ka.kode_nama_kurikulum
                        SET ast.kode_kurikulum_angkatan = ka.kode_kurikulum_angkatan
                    ");
                }

                // Add new foreign key and indexes
                Schema::table('assessment_templates', function (Blueprint $table) {
                    if (!Schema::hasColumn('assessment_templates', 'kode_kurikulum_angkatan')) {
                        $table->unsignedInteger('kode_kurikulum_angkatan')->nullable();
                    }
                    
                    $table->foreign('kode_kurikulum_angkatan')
                        ->references('kode_kurikulum_angkatan')
                        ->on('kurikulum_angkatan')
                        ->onDelete('restrict')
                        ->onUpdate('cascade');

                    $table->index('kode_kurikulum_angkatan');
                    $table->unique(['id_matakuliah', 'kode_kurikulum_angkatan', 'versi'], 'uq_assessment_template');
                });

                // Drop old columns (only for MySQL - SQLite handled via PRAGMA)
                if (DB::connection()->getDriverName() === 'mysql') {
                    Schema::table('assessment_templates', function (Blueprint $table) {
                        if (Schema::hasColumn('assessment_templates', 'kode_nama_kurikulum')) {
                            $table->dropColumn('kode_nama_kurikulum');
                        }
                        if (Schema::hasColumn('assessment_templates', 'kode_tahun_akademik')) {
                            $table->dropColumn('kode_tahun_akademik');
                        }
                    });
                } else if ($isSlite) {
                    // For SQLite, we skip dropping old columns to avoid table recreation issues
                    // The columns will remain but be unused
                    // This is acceptable for testing and can be cleaned up manually in production
                }
            } finally {
                if ($isSlite) {
                    // Re-enable FK constraints
                    DB::statement('PRAGMA foreign_keys = ON');
                }
            }
        }
    }

    public function down(): void
    {
        // Simplified down() - just keep the old state for SQLite
        // Full reversal is complex with SQLite schema constraints
        // This migration is primarily for data structure refactoring
    }

    private function hasForeignKey($table, $key): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $constraints = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [DB::connection()->getDatabaseName(), $table, $key]);

        return !empty($constraints);
    }

    private function hasIndex($table, $index): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $indexes = DB::select("
            SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
        ", [DB::connection()->getDatabaseName(), $table, $index]);

        return !empty($indexes);
    }
};
