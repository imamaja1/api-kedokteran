<?php

use App\Http\Controllers\Api_Staff\AkademikController;
use App\Http\Controllers\Api_Staff\PembayaranController;
use App\Http\Controllers\Api_Staff\PenempatanController;
use App\Http\Controllers\Api_Staff\AssessmentGradeController;
use App\Http\Controllers\Api_Staff\AssessmentScoreController;
use App\Http\Controllers\Api_Staff\AssessmentTemplateController;
use App\Http\Controllers\Api_Staff\DefaultController;
use App\Http\Controllers\Api_Staff\DosenController;
use App\Http\Controllers\Api_Staff\KurikulumAngkatanController;
use App\Http\Controllers\Api_Staff\MahasiswaController;
use App\Http\Controllers\Api_Staff\MatakuliahController;
use App\Http\Controllers\Api_Staff\NamaKurikulumController;
use App\Http\Controllers\Api_Staff\ProgramStudiController;
use App\Http\Controllers\Api_Staff\TahunAkademikController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Protected Staff (auth:staff_web) ─────────────────────────────────
Route::prefix("api/staff")
    ->middleware([
        "sanctum.spa",
        "auth:staff_web",
        "sanctum.cookie",
        "log.activity",
    ])
    ->group(function () {
        // Auth
        Route::get("me", [AuthController::class, "me_staff"]);
        Route::get("tahun-angkatan", [
            DefaultController::class,
            "tahun_angkatan",
        ]);

        Route::get("dosen", [DosenController::class, "search"]);
        Route::get("mahasiswa", [MahasiswaController::class, "search"]);

        // Tahun Akademik Active
        Route::get("tahun-akademik/active", [TahunAkademikController::class, "active"]);

        // Pembayaran
        Route::prefix("pembayaran")->middleware(["throttle:60,1"])->group(function () {
            Route::get("/", [PembayaranController::class, "index"]);
            Route::get("/show", [PembayaranController::class, "show"]);
            Route::post("/", [PembayaranController::class, "store"]);
            Route::put("/", [PembayaranController::class, "update"]);
            Route::get("/sks-limit", [PembayaranController::class, "getSksLimit"]);
            Route::put("/sks-override", [PembayaranController::class, "setSksOverride"]);
        });

        // Penempatan Kelas
        Route::prefix("penempatan")->middleware(["throttle:60,1"])->group(function () {
            Route::get("/", [PenempatanController::class, "index"]);
            Route::post("/", [PenempatanController::class, "store"]);
            Route::delete("/{id}", [PenempatanController::class, "destroy"]);
        });

        // Akademik
        Route::prefix("akademik")
            ->middleware(["throttle:30,1"])
            ->group(function () {
                Route::get("program-studi", [
                    AkademikController::class,
                    "program_studi",
                ]);
                Route::get("nama-kurikulum", [
                    AkademikController::class,
                    "nama_kurikulum",
                ]);
                Route::get("kurikulum", [
                    AkademikController::class,
                    "kurikulum",
                ]);
                Route::get("krs", [AkademikController::class, "krs"]);
                Route::get("krs-detail", [
                    AkademikController::class,
                    "krs_detail",
                ]);
                Route::get("khs", [AkademikController::class, "khs"]);
                Route::get("khs-detail", [
                    AkademikController::class,
                    "khs_detail",
                ]);
                Route::get("petikan-nilai", [
                    AkademikController::class,
                    "petikan_nilai",
                ]);
                // Perwalian routes
                Route::prefix("perwalian")->group(function () {
                    Route::get("/", [AkademikController::class, "perwalian"]);
                    Route::post("/", [
                        AkademikController::class,
                        "storePerwalian",
                    ]);
                    Route::get("dosen", [
                        AkademikController::class,
                        "perwalianByDosen",
                    ]);
                    Route::get("mahasiswa", [
                        AkademikController::class,
                        "perwalianByMahasiswa",
                    ]);
                    Route::put("{code}", [
                        AkademikController::class,
                        "updatePerwalian",
                    ]);
                });
            });

        // Master Data
        Route::prefix("master-data")
            ->middleware(["throttle:60,1"])
            ->group(function () {
                // Matakuliah
                Route::prefix("matakuliah")->group(function () {
                    Route::get("/", [MatakuliahController::class, "index"]);
                    Route::get("/show", [MatakuliahController::class, "show"]);
                    Route::post("/", [MatakuliahController::class, "store"]);
                    Route::put("/", [MatakuliahController::class, "update"]);
                    Route::delete("/{code}", [
                        MatakuliahController::class,
                        "destroy",
                    ]);
                });

                // Program Studi
                Route::prefix("program-studi")->group(function () {
                    Route::get("/", [ProgramStudiController::class, "index"]);
                    Route::get("/show", [
                        ProgramStudiController::class,
                        "show",
                    ]);
                    Route::post("/", [ProgramStudiController::class, "store"]);
                    Route::put("/", [ProgramStudiController::class, "update"]);
                    Route::delete("/{code}", [
                        ProgramStudiController::class,
                        "destroy",
                    ]);
                });

                // Dosen (dengan soft delete)
                Route::prefix("dosen")->group(function () {
                    Route::get("/", [DosenController::class, "index"]);
                    Route::get("/show", [DosenController::class, "show"]);
                    Route::get("/trash", [DosenController::class, "trash"]);
                    Route::post("/", [DosenController::class, "store"]);
                    Route::put("/", [DosenController::class, "update"]);
                    Route::delete("/{code}", [
                        DosenController::class,
                        "destroy",
                    ]);
                    Route::patch("/{code}/restore", [
                        DosenController::class,
                        "restore",
                    ]);
                    Route::delete("/{code}/force", [
                        DosenController::class,
                        "forceDelete",
                    ]);
                });

                // Nama Kurikulum
                Route::prefix("nama-kurikulum")->group(function () {
                    Route::get("/", [NamaKurikulumController::class, "index"]);
                    Route::get("/show", [
                        NamaKurikulumController::class,
                        "show",
                    ]);
                    Route::post("/", [NamaKurikulumController::class, "store"]);
                    Route::put("/", [NamaKurikulumController::class, "update"]);
                    Route::delete("/{code}", [
                        NamaKurikulumController::class,
                        "destroy",
                    ]);
                });

                // Tahun Akademik
                Route::prefix("tahun-akademik")->group(function () {
                    Route::get("/", [TahunAkademikController::class, "index"]);
                    Route::get("/show", [
                        TahunAkademikController::class,
                        "show",
                    ]);
                    Route::post("/", [TahunAkademikController::class, "store"]);
                    Route::put("/", [TahunAkademikController::class, "update"]);
                    Route::delete("/{code}", [
                        TahunAkademikController::class,
                        "destroy",
                    ]);
                });

                // Kurikulum Angkatan (Tahun Kurikulum)
                Route::prefix("kurikulum-angkatan")->group(function () {
                    Route::get("/", [
                        KurikulumAngkatanController::class,
                        "index",
                    ]);
                    Route::get("/show", [
                        KurikulumAngkatanController::class,
                        "show",
                    ]);
                    Route::post("/", [
                        KurikulumAngkatanController::class,
                        "store",
                    ]);
                    Route::put("/", [
                        KurikulumAngkatanController::class,
                        "update",
                    ]);
                    Route::delete("/{code}", [
                        KurikulumAngkatanController::class,
                        "destroy",
                    ]);
                });

                // Mahasiswa (dengan soft delete)
                Route::prefix("mahasiswa")->group(function () {
                    Route::get("/", [MahasiswaController::class, "index"]);
                    Route::get("/show", [MahasiswaController::class, "show"]);
                    Route::get("/trash", [MahasiswaController::class, "trash"]);
                    Route::post("/", [MahasiswaController::class, "store"]);
                    Route::put("/", [MahasiswaController::class, "update"]);
                    Route::delete("/{code}", [
                        MahasiswaController::class,
                        "destroy",
                    ]);
                    Route::patch("/{code}/restore", [
                        MahasiswaController::class,
                        "restore",
                    ]);
                    Route::delete("/{code}/force", [
                        MahasiswaController::class,
                        "forceDelete",
                    ]);
                });
            });

        // ─── Assessment System ────────────────────────────────────────
        Route::prefix("assessment")
            ->middleware(["throttle:60,1"])
            ->group(function () {
                // Templates
                Route::prefix("templates")->group(function () {
                    Route::get("/", [
                        AssessmentTemplateController::class,
                        "index",
                    ]);
                    Route::post("/", [
                        AssessmentTemplateController::class,
                        "store",
                    ]);
                    Route::get("/show", [
                        AssessmentTemplateController::class,
                        "show",
                    ]);
                    Route::put("/update", [
                        AssessmentTemplateController::class,
                        "update",
                    ]);
                });

                // Scores
                Route::prefix("scores")->group(function () {
                    Route::post("/", [
                        AssessmentScoreController::class,
                        "store",
                    ]);
                });

                // Student scores
                Route::get("/students/score", [
                    AssessmentScoreController::class,
                    "getStudentScore",
                ]);
                Route::get("/students/score/breakdown", [
                    AssessmentScoreController::class,
                    "getScoreBreakdown",
                ]);

                // Grade
                Route::prefix("grade")->group(function () {
                    Route::get("/", [
                        AssessmentGradeController::class,
                        "index",
                    ]);
                    Route::post("/", [
                        AssessmentGradeController::class,
                        "store",
                    ]);
                    Route::get("/show", [
                        AssessmentGradeController::class,
                        "show",
                    ]);
                    Route::put("/update", [
                        AssessmentGradeController::class,
                        "update",
                    ]);
                    Route::delete("/{code}", [
                        AssessmentGradeController::class,
                        "destroy",
                    ]);
                });
            });

        // fallback dalam group — return 404 bukan 401
        Route::fallback(
            fn() => response()->json(
                [
                    "status" => false,
                    "message" => "Endpoint tidak ditemukan.",
                    "error" => "NOT_FOUND",
                ],
                404,
            ),
        );
    });
