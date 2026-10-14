<!DOCTYPE html>
<html class="dark" lang="id">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Mekanisme Penilaian Blok Kedokteran — SIAKAD Kedokteran</title>

    {{-- Google Fonts: Geist, JetBrains Mono, Material Symbols --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=JetBrains+Mono:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    {{-- Tailwind CSS CDN & Custom Theme Tokens --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "background": "#0b1326",
                        "surface": "#0b1326",
                        "surface-dim": "#0b1326",
                        "surface-bright": "#31394d",
                        "surface-container-lowest": "#060e20",
                        "surface-container-low": "#131b2e",
                        "surface-container": "#171f33",
                        "surface-container-high": "#222a3d",
                        "surface-container-highest": "#2d3449",
                        "on-surface": "#dae2fd",
                        "on-surface-variant": "#c7c4d8",
                        "outline": "#918fa1",
                        "outline-variant": "#464555",
                        "primary": "#c3c0ff",
                        "primary-container": "#4f46e5",
                        "on-primary": "#1d00a5",
                        "on-primary-container": "#dad7ff",
                        "secondary": "#89ceff",
                        "secondary-container": "#00a2e6",
                        "tertiary": "#4ae176",
                        "tertiary-fixed": "#6bff8f",
                        "tertiary-container": "#007030",
                        "on-tertiary": "#003915",
                        "error": "#ffb4ab",
                        "error-container": "#93000a"
                    },
                    fontFamily: {
                        sans: ["Geist", "sans-serif"],
                        mono: ["JetBrains Mono", "monospace"]
                    }
                }
            }
        };
    </script>

    {{-- Mermaid JS CDN for Client-Side Diagram Rendering --}}
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({
            startOnLoad: true,
            theme: 'dark',
            themeVariables: {
                darkMode: true,
                background: '#131b2e',
                mainBkg: '#171f33',
                primaryColor: '#4f46e5',
                primaryTextColor: '#dae2fd',
                primaryBorderColor: '#89ceff',
                lineColor: '#89ceff',
                secondaryColor: '#171f33',
                tertiaryColor: '#222a3d',
                fontFamily: 'Geist, sans-serif'
            }
        });
    </script>

    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #0b1326;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #222a3d;
            border-radius: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #464555;
        }
    </style>
</head>

<body class="bg-background text-on-surface font-sans antialiased selection:bg-primary-container selection:text-on-primary-container min-h-screen flex flex-col">

    {{-- ─── STICKY HEADER ─── --}}
    <header class="h-14 border-b border-[#222d45] bg-surface-container-low/95 backdrop-blur-md sticky top-0 z-50 px-4 md:px-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.docs') }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-semibold border border-[#222d45] hover:border-outline transition-all" title="Kembali ke Dokumentasi API">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>API Docs</span>
            </a>
            <div class="h-4 w-px bg-surface-variant"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-primary-container flex items-center justify-center text-on-primary-container shadow-inner">
                    <span class="material-symbols-outlined text-[18px]">account_tree</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-on-surface flex items-center gap-1.5">
                        Mekanisme Penilaian Blok Kedokteran
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-tertiary-container/30 text-tertiary border border-tertiary/40">SIAKAD v1.0</span>
                    </h1>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <nav class="hidden lg:flex items-center gap-1 text-xs">
                <a href="#matrix-peran" class="px-2.5 py-1 rounded text-outline hover:text-on-surface hover:bg-surface-container transition-colors">Wewenang Nilai</a>
                <a href="#diagram-alur" class="px-2.5 py-1 rounded text-outline hover:text-on-surface hover:bg-surface-container transition-colors">Peta Alur Visual</a>
                <a href="#tahapan-proses" class="px-2.5 py-1 rounded text-outline hover:text-on-surface hover:bg-surface-container transition-colors">6 Tahap Eksekusi</a>
                <a href="#arsitektur-kode" class="px-2.5 py-1 rounded text-outline hover:text-on-surface hover:bg-surface-container transition-colors">Referensi Kode</a>
            </nav>
            <div class="h-4 w-px bg-surface-variant hidden lg:block"></div>
            @auth
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-medium border border-[#222d45] transition-colors">
                <span class="material-symbols-outlined text-[16px]">dashboard</span>
                <span class="hidden sm:inline">Admin Panel</span>
            </a>
            @endauth
        </div>
    </header>

    {{-- ─── MAIN CONTENT ─── --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 md:px-8 py-8 space-y-12">

        {{-- HERO SECTION --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-surface-container-low via-surface-container to-surface-container-low border border-[#222d45] p-6 md:p-8 shadow-2xl">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-primary-container/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-72 h-72 bg-secondary-container/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-4 max-w-4xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-container/20 border border-primary/30 text-primary text-xs font-semibold">
                    <span class="material-symbols-outlined text-[15px]">medical_services</span>
                    Kurikulum Berbasis Blok — Fakultas Kedokteran
                </div>
                <h2 class="text-2xl md:text-4xl font-extrabold tracking-tight text-white">
                    Panduan Lengkap Arsitektur Penilaian Blok
                </h2>
                <p class="text-on-surface-variant text-sm md:text-base leading-relaxed">
                    Dokumentasi menyeluruh dari pembuatan cetak biru <strong class="text-white">Assessment Template</strong> untuk mata kuliah blok, pembukaan kelas perkuliahan, penugasan dosen penguji per komponen (*OSCE, CBT, Tutorial PBL*), validasi bertingkat berjenjang (*bottom-up*), hingga kalkulasi rekursif nilai akhir dan terbitnya KHS mahasiswa.
                </p>

                {{-- KEY DIFFERENCE PILLS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-surface-container-lowest/80 border border-[#222d45] rounded-xl p-4 flex gap-3 items-start">
                        <div class="w-9 h-9 rounded-lg bg-secondary/10 border border-secondary/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-secondary text-[20px]">architecture</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-secondary">1. Assessment Template (Blueprint)</h3>
                            <p class="text-xs text-outline mt-1 leading-normal">
                                Berada di tingkat <strong>Mata Kuliah Blok & Kurikulum Angkatan</strong>. Mengatur pohon hirarki komponen penilaian dan bobot total 100%. Tidak mengenal mahasiswa maupun dosen penilai kelas.
                            </p>
                        </div>
                    </div>

                    <div class="bg-surface-container-lowest/80 border border-[#222d45] rounded-xl p-4 flex gap-3 items-start">
                        <div class="w-9 h-9 rounded-lg bg-tertiary/10 border border-tertiary/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-tertiary text-[20px]">class</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-tertiary">2. Assessment Class (Instance Nyata)</h3>
                            <p class="text-xs text-outline mt-1 leading-normal">
                                Dikenal di database sebagai <code class="text-tertiary font-mono">assessment_dosen</code>. Mengikat Template ke satu <strong>Kelas Kuliah spesifik</strong>, menampung daftar mahasiswa riil, jadwal batas buka-tutup, dan penugasan dosen penguji.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 1: WEWENANG & MATRIX PENGISIAN NILAI (SIAPA BISA APA) --}}
        <section id="matrix-peran" class="space-y-6 scroll-mt-20">
            <div class="border-b border-[#222d45] pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-lg md:text-xl font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[24px]">verified_user</span>
                        Matrix Hak Akses & Wewenang: Siapa yang Boleh Mengisi Nilai?
                    </h3>
                    <p class="text-xs md:text-sm text-outline mt-0.5">Penjelasan tegas hak akses Staff Akademik, Dosen Pengampu, Koordinator Blok, dan Mahasiswa.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Role: Staff --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 flex flex-col justify-between hover:border-primary/50 transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-primary-container/30 text-primary border border-primary/40 font-mono">STAFF AKADEMIK</span>
                            <span class="material-symbols-outlined text-primary">admin_panel_settings</span>
                        </div>
                        <h4 class="text-sm font-bold text-white mt-3">Full Setup & Master Control</h4>
                        <ul class="text-xs text-on-surface-variant space-y-2 mt-3 list-disc list-inside">
                            <li><strong class="text-white">Membuat & Edit Template Blok</strong> (Hanya Staff!)</li>
                            <li>Membuka Kelas Kuliah & plot mahasiswa</li>
                            <li>Mengaitkan Template ke Kelas (<code class="font-mono text-primary text-[10px]">POST /assessment/dosen</code>)</li>
                            <li>Menugaskan Dosen per node kategori</li>
                            <li>Bisa input/override nilai jika diperlukan via Staff Score</li>
                        </ul>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#222d45] text-[11px] text-tertiary flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Akses Penuh Template & Setup
                    </div>
                </div>

                {{-- Role: Dosen Penguji --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 flex flex-col justify-between hover:border-secondary/50 transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-secondary/20 text-secondary border border-secondary/40 font-mono">DOSEN PENGUJI</span>
                            <span class="material-symbols-outlined text-secondary">stethoscope</span>
                        </div>
                        <h4 class="text-sm font-bold text-white mt-3">Input Nilai & Validasi Komponen</h4>
                        <ul class="text-xs text-on-surface-variant space-y-2 mt-3 list-disc list-inside">
                            <li><strong class="text-red-300">TIDAK BISA</strong> ubah Template (Read-Only)</li>
                            <li><strong class="text-white">Input Angka Nilai</strong> (<code class="font-mono text-secondary text-[10px]">POST /input-score</code>) khusus node wewenangnya</li>
                            <li>Memvalidasi kategori per siswa (<code class="font-mono text-secondary text-[10px]">POST /validasi</code>)</li>
                            <li><strong>Validasi Massal Sekelas</strong> (<code class="font-mono text-secondary text-[10px]">POST /validasi-bulk</code>)</li>
                            <li>Mengajukan revisi nilai (cascade rollback)</li>
                        </ul>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#222d45] text-[11px] text-secondary flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Akses Input Nilai & Node Validasi
                    </div>
                </div>

                {{-- Role: Koordinator Blok --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 flex flex-col justify-between hover:border-tertiary/50 transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-tertiary-container/30 text-tertiary border border-tertiary/40 font-mono">KOORDINATOR BLOK</span>
                            <span class="material-symbols-outlined text-tertiary">assignment_turned_in</span>
                        </div>
                        <h4 class="text-sm font-bold text-white mt-3">Penanggung Jawab Root Node</h4>
                        <ul class="text-xs text-on-surface-variant space-y-2 mt-3 list-disc list-inside">
                            <li>Dosen yang memegang penugasan di <strong>Root Node</strong> (Puncak pohon nilai)</li>
                            <li>Memverifikasi seluruh cabang (CBT, OSCE, Tutorial, Sikap) sudah valid</li>
                            <li><strong class="text-white">Validasi Root Node</strong> $\rightarrow$ Memicu kalkulasi otomatis & penerbitan grade KHS</li>
                        </ul>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#222d45] text-[11px] text-tertiary flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Pemegang Otoritas Finalisasi Nilai
                    </div>
                </div>

                {{-- Role: Mahasiswa --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 flex flex-col justify-between hover:border-outline/50 transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-surface-container-high text-outline border border-outline/30 font-mono">MAHASISWA</span>
                            <span class="material-symbols-outlined text-outline">school</span>
                        </div>
                        <h4 class="text-sm font-bold text-white mt-3">Subjek Penilaian</h4>
                        <ul class="text-xs text-on-surface-variant space-y-2 mt-3 list-disc list-inside">
                            <li><strong class="text-red-300">TIDAK BISA</strong> input maupun validasi</li>
                            <li>Tidak bisa melihat proses draf nilai</li>
                            <li>Melihat hasil nilai akhir & huruf mutu secara resmi di KHS/Transkrip setelah Root divalidasi</li>
                        </ul>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#222d45] text-[11px] text-outline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">visibility</span>
                        Read-Only Hasil Akhir KHS
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 2: PETA VISUAL ALUR (MERMAID DIAGRAMS) --}}
        <section id="diagram-alur" class="space-y-6 scroll-mt-20">
            <div class="border-b border-[#222d45] pb-3">
                <h3 class="text-lg md:text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[24px]">timeline</span>
                    Peta Visual Alur (Interactive Flow & Sequence Diagrams)
                </h3>
                <p class="text-xs md:text-sm text-outline mt-0.5">Diagram arsitektur alur data dari tahap inisiasi sampai akhir.</p>
            </div>

            {{-- DIAGRAM 1: MAKRO FLOWCHART --}}
            <div class="bg-surface-container rounded-2xl border border-[#222d45] p-5 md:p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                        1. Alur Makro End-to-End
                    </h4>
                    <span class="text-xs font-mono text-outline">Mermaid Flowchart</span>
                </div>
                <div class="p-4 rounded-xl bg-surface-container-lowest/80 border border-[#222d45] overflow-x-auto custom-scroll">
                    <div class="mermaid text-center">
flowchart LR
    subgraph T1["1. Template Blok"]
        direction TB
        MK["Matakuliah Blok (block=1)"] --> AT["AssessmentTemplate (Bobot 100%)"]
    end

    subgraph T2["2. Buka Kelas"]
        direction TB
        K["Kelas Kuliah"] --> MHS["Peserta (KelasMahasiswa)"]
        K --> DOS["Pengajar (Mengajar)"]
    end

    subgraph T3["3. Binding Kelas"]
        direction TB
        AD["AssessmentDosen (Kelas + Template)"] --> ASSIGN["Plot Dosen per Node (Assignments)"]
    end

    subgraph T4["4. Eksekusi"]
        direction TB
        SCORE["Input Nilai (StudentScore)"] --> VALID["Validasi Node (NodeValidation)"]
        VALID -->|Root Valid| KHS["Nilai Akhir & Grade KHS"]
    end

    AT --> AD
    K --> AD
    ASSIGN --> SCORE
                    </div>
                </div>
            </div>

            {{-- DIAGRAM 2: SEQUENCE DIAGRAM --}}
            <div class="bg-surface-container rounded-2xl border border-[#222d45] p-5 md:p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-tertiary"></span>
                        2. Interaksi Antar-Aktor & Sistem (Sequence Flow)
                    </h4>
                    <span class="text-xs font-mono text-outline">Mermaid Sequence</span>
                </div>
                <div class="p-4 rounded-xl bg-surface-container-lowest/80 border border-[#222d45] overflow-x-auto custom-scroll">
                    <div class="mermaid text-center">
sequenceDiagram
    autonumber

    box rgba(79, 70, 229, 0.15) Role: Staff (auth:staff_web)
    actor Staff as Staff Akademik
    end

    box rgba(137, 206, 255, 0.15) Role: Dosen (auth:dosen_web)
    actor Dosen as Dosen Penilai (Node Komponen)
    actor DosenRoot as Dosen PJ Root (Koordinator)
    end

    box rgba(74, 225, 118, 0.1) Backend SISKA
    participant Sys as SISKA API Service
    participant DB as Database
    end

    box rgba(145, 143, 161, 0.1) Role: Mahasiswa (auth:mahasiswa_web)
    actor Mhs as Mahasiswa
    end

    Note over Staff,DB: FASE 1: SETUP OLEH ROLE STAFF
    Staff->>Sys: POST /api/staff/assessment/templates (Struktur Pohon & Bobot 100%)
    Sys->>DB: Simpan assessment_templates & assessment_node_indexes

    Staff->>Sys: POST /api/staff/kelas (Buka Kelas Perkuliahan)
    Sys->>DB: Simpan kelas & enroll mahasiswa KRS (kelas_mahasiswa)

    Staff->>Sys: POST /api/staff/assessment/dosen (Bind Kelas ke Template)
    Sys->>DB: Buat instance assessment_dosen (status: aktif)

    Staff->>Sys: GET /api/staff/assessment/dosen/assignments (Lihat View Tree, Nodes & Dosen Kelas)
    Sys-->>Staff: Return available_nodes, dosen_pengajar & payload_sample_put

    Staff->>Sys: PUT /api/staff/assessment/dosen/assign (Plotting Dosen ke Node Kategori & Root)
    Sys->>DB: Simpan ke assessment_dosen_assignments

    Note over Dosen,DB: FASE 2: INPUT NILAI & VALIDASI OLEH ROLE DOSEN
    Dosen->>Sys: GET /api/dosen/assessment-dosen/my-nodes (Cek Tugas Node, Jadwal & Sisa Hari)
    Sys-->>Dosen: Daftar node tugas (OSCE, Tutorial PBL, CBT)
    Dosen->>Sys: GET /api/dosen/assessment-dosen/mahasiswa (Daftar Siswa & Status Nilai)
    Sys-->>Dosen: List mahasiswa sekelas & progres pengisian

    alt Mode Input Terproteksi Node Wewenang (Rekomendasi)
        Dosen->>Sys: POST /api/dosen/assessment-dosen/input-score (Input Skor Terproteksi)
    else Mode Input Standar
        Dosen->>Sys: POST /api/dosen/penilaian/input (Input Angka Skor Mahasiswa 0-100)
    end
    Sys->>DB: Simpan nilai mentah ke student_scores

    alt Mode Validasi Individu per Siswa
        Dosen->>Sys: POST /api/dosen/assessment-dosen/validasi (Validasi per Mahasiswa)
    else Mode Validasi Massal Sekelas (Bulk)
        Dosen->>Sys: POST /api/dosen/assessment-dosen/validasi-bulk (Validasi Bulk Sekelas)
    end
    Sys->>DB: Update assessment_node_validations (status: validasi)

    opt Jika Ada Nilai Perlu Dikoreksi
        Dosen->>Sys: POST /api/dosen/assessment-dosen/revisi (Catatan Revisi)
        Sys->>DB: Cascade Reset status ancestor & batalkan draf KHS
    end

    Note over DosenRoot,DB: FASE 3: FINALISASI ROOT OLEH DOSEN PJ ROOT (KOORDINATOR)
    DosenRoot->>Sys: POST /api/dosen/assessment-dosen/validasi (Validasi Root Node)
    Sys->>Sys: ScoreCalculationService kalkulasi agregasi rekursif nilai akhir
    Sys->>Sys: ServiceGrade konversi skor ke Huruf Mutu (A, B+, C, dll.)
    Sys->>DB: Tulis ke khs_detail & update status assessment_dosen = 'selesai'

    Note over Mhs,Sys: FASE 4: MAHASISWA MENERIMA HASIL KHS
    Mhs->>Sys: GET /api/mahasiswa/khs (Akses KHS Semester)
    Sys-->>Mhs: Menampilkan Nilai Akhir & Grade Resmi di KHS
                    </div>
                </div>
            </div>

            {{-- DIAGRAM 3: STRUKTUR POHON NILAI BLOK --}}
            <div class="bg-surface-container rounded-2xl border border-[#222d45] p-5 md:p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                        3. Contoh Pohon Penilaian Blok Kedokteran (Pohon Hirarki)
                    </h4>
                    <span class="text-xs font-mono text-outline">Struktur Tree JSON</span>
                </div>
                <div class="p-4 rounded-xl bg-surface-container-lowest/80 border border-[#222d45] overflow-x-auto custom-scroll">
                    <div class="mermaid text-center">
graph TD
    ROOT["Nilai Akhir Blok (Root: 100%)<br/>[Validator: Koordinator Blok]"]
    
    C1["Kategori CBT / Teori (40%)<br/>[Validator: Dosen A]"]
    C2["Kategori OSCE / Lab (30%)<br/>[Validator: Dosen B]"]
    C3["Kategori Tutorial PBL (20%)<br/>[Validator: Dosen C]"]
    C4["Kategori Sikap / Afektif (10%)<br/>[Validator: Dosen D]"]
    
    ROOT --> C1
    ROOT --> C2
    ROOT --> C3
    ROOT --> C4
    
    I1["Input: Ujian MCQ Blok (100%)"]
    C1 --> I1
    
    I2["Input: Station Jantung (50%)"]
    I3["Input: Station RJP (50%)"]
    C2 --> I2
    C2 --> I3
    
    I4["Input: Skenario 1 (50%)"]
    I5["Input: Skenario 2 (50%)"]
    C3 --> I4
    C3 --> I5
    
    I6["Input: Kehadiran & Etika (100%)"]
    C4 --> I6

    classDef root fill:#4f46e5,stroke:#c3c0ff,stroke-width:2px,color:#fff;
    classDef cat fill:#171f33,stroke:#89ceff,stroke-width:1.5px,color:#89ceff;
    classDef inp fill:#131b2e,stroke:#4ae176,stroke-width:1px,color:#dae2fd;
    
    class ROOT root;
    class C1,C2,C3,C4 cat;
    class I1,I2,I3,I4,I5,I6 inp;
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 3: 6 TAHAP EKSEKUSI DETAIL --}}
        <section id="tahapan-proses" class="space-y-6 scroll-mt-20">
            <div class="border-b border-[#222d45] pb-3">
                <h3 class="text-lg md:text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-tertiary text-[24px]">format_list_numbered</span>
                    Rincian Lengkap 6 Tahapan Pelaksanaan Penilaian Blok
                </h3>
                <p class="text-xs md:text-sm text-outline mt-0.5">Spesifikasi teknis, endpoint API, controller, model, dan aturan validasi.</p>
            </div>

            <div class="space-y-4">
                {{-- STEP 1 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-primary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-primary-container text-white font-mono font-bold flex items-center justify-center shrink-0">1</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Pembuatan Template Blok (Assessment Template)</h4>
                                <span class="text-xs text-primary font-mono font-semibold">POST /api/staff/assessment/templates • Role: Staff Akademik</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Blueprint Level</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Staff membuat struktur komponen nilai untuk mata kuliah blok (<code class="text-white font-mono">block = 1</code>) pada kurikulum angkatan tertentu. Pohon penilaian divalidasi oleh <code class="text-secondary font-mono">TemplateValidatorService</code> untuk memastikan bobot anak node tiap kategori berjumlah persis <strong>100%</strong>.</p>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">assessment_templates</code> (structure JSON, versi, is_active), <code class="text-secondary">assessment_node_indexes</code> (flattened index untuk query cepat).
                        </div>
                    </div>
                </div>

                {{-- STEP 2 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-secondary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-secondary-container text-white font-mono font-bold flex items-center justify-center shrink-0">2</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Pembukaan Kelas & Pemasangan Template (Assessment Class)</h4>
                                <span class="text-xs text-secondary font-mono font-semibold">POST /api/staff/kelas & POST /api/staff/assessment/dosen • Role: Staff Akademik</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Class Level</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Staff membuka kelas perkuliahan (misal Kelas A) di semester berjalan. Kemudian Staff memanggil <code class="text-white font-mono">POST /api/staff/assessment/dosen</code> dengan payload <code class="text-white font-mono">{"code_kelas": "..."}</code>. Sistem secara otomatis mencari Template aktif dari mata kuliah blok tersebut dan mengaitkannya ke tabel <code class="text-secondary font-mono">assessment_dosen</code>.</p>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">kelas</code>, <code class="text-secondary">kelas_mahasiswa</code>, <code class="text-tertiary">assessment_dosen</code> (template_id, kelas_id, status='aktif').
                        </div>
                    </div>
                </div>

                {{-- STEP 3 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-tertiary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-tertiary-container text-white font-mono font-bold flex items-center justify-center shrink-0">3</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Inspeksi Struktur Tree &amp; Plotting Dosen ke Node Kategori</h4>
                                <span class="text-xs text-tertiary font-mono font-semibold">GET &amp; PUT /api/staff/assessment/dosen/assignments • Role: Staff / Koordinator Blok</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Assignment View &amp; Mapping</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Staff memanggil <code class="text-white font-mono">GET /api/staff/assessment/dosen/assignments?code_kelas=...</code> untuk melihat struktur pohon template (<code class="text-secondary font-mono">tree_view</code>), daftar node yang dapat ditugaskan (<code class="text-secondary font-mono">available_nodes</code>), daftar dosen pengajar yang valid untuk kelas (<code class="text-tertiary font-mono">dosen_pengajar</code>), serta contoh payload PUT siap pakai (<code class="text-primary font-mono">payload_sample_put</code>).</p>
                        <p>Setelah memilih dosen untuk node wewenang, Staff memanggil <code class="text-white font-mono">PUT /api/staff/assessment/dosen/assign</code>. Dosen wajib terdaftar di tabel <code class="text-white font-mono">mengajar</code> untuk kelas tersebut, dan node yang ditugaskan harus bertipe kategori atau root (bukan input node).</p>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">assessment_dosen_assignments</code> (assessment_dosen_id, node_key, kode_dosen), <code class="text-secondary">mengajar</code> (kelas_id, kode_dosen).
                        </div>
                    </div>
                </div>

                {{-- STEP 4 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-primary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-primary-container text-white font-mono font-bold flex items-center justify-center shrink-0">4</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Input Angka Nilai Mahasiswa Terproteksi Node Wewenang</h4>
                                <span class="text-xs text-primary font-mono font-semibold">POST /api/dosen/assessment-dosen/input-score • Role: Dosen Penguji / Penilai</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Input Data</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Dosen memasukkan skor numerik (skala 0 - 100) per mahasiswa. <strong class="text-white">Pengecekan Keamanan Berlapis:</strong> Sistem memverifikasi bahwa <code class="text-secondary font-mono">node_key</code> yang diinput benar-benar berada di bawah cabang kategori yang ditugaskan kepada dosen tersebut di <code class="text-primary font-mono">assessment_dosen_assignments</code> (dosen stase A tidak bisa mengisi stase B). Periode pengisian diproteksi oleh jadwal buka-tutup (<code class="text-tertiary font-mono">is_open</code>).</p>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">student_scores</code> (template_id, nim, node_key, score, dosen_kode_dosen), <code class="text-secondary">assessment_node_validations</code> (status: proses).
                        </div>
                    </div>
                </div>

                {{-- STEP 5 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-secondary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-secondary-container text-white font-mono font-bold flex items-center justify-center shrink-0">5</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Validasi Berjenjang: Per Mahasiswa atau Massal Sekelas (Bulk)</h4>
                                <span class="text-xs text-secondary font-mono font-semibold">POST /validasi &amp; POST /validasi-bulk • Role: Dosen Pemegang Node</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Verification</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Dosen yang ditugaskan memvalidasi node kategorinya. Validasi menerapkan aturan ketat <strong class="text-white">Bottom-Up</strong>: Kategori induk tidak dapat divalidasi sebelum seluruh anak nodenya sudah valid. Tersedia dua mode validasi:</p>
                        <ul class="list-disc list-inside space-y-1 text-outline pl-2">
                            <li><code class="text-white font-mono">POST /validasi</code> $\rightarrow$ Validasi individu per satu mahasiswa.</li>
                            <li><code class="text-white font-mono">POST /validasi-bulk</code> $\rightarrow$ <strong>Validasi Massal Sekaligus</strong> (memvalidasi seluruh mahasiswa sekelas yang nilainya sudah lengkap hanya dengan 1 request).</li>
                            <li><code class="text-red-300 font-mono">POST /revisi</code> $\rightarrow$ Jika ada nilai salah, otomatis memicu <strong>cascade reset</strong>: leluhur (*ancestor*) sampai ke root kembali di-reset dan draf nilai KHS dibatalkan.</li>
                        </ul>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">assessment_node_validations</code> (status: validasi / revisi, validated_by, validated_at, catatan).
                        </div>
                    </div>
                </div>

                {{-- STEP 6 --}}
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-5 hover:border-tertiary/40 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-tertiary-container text-white font-mono font-bold flex items-center justify-center shrink-0">6</span>
                            <div>
                                <h4 class="text-base font-bold text-white">Finalisasi Root Node & Penerbitan Nilai KHS Mahasiswa</h4>
                                <span class="text-xs text-tertiary font-mono font-semibold">Pemicu Otomatis saat Root Node Divalidasi • Role: Koordinator Blok</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-surface-container-high text-outline border border-[#222d45]">Final KHS</span>
                    </div>
                    <div class="mt-4 text-xs text-on-surface-variant space-y-2 leading-relaxed">
                        <p>Saat Koordinator Blok memvalidasi <strong>Root Node</strong> (puncak pohon), sistem secara otomatis menjalankan <code class="text-white font-mono">finalizeGrade()</code>:</p>
                        <ol class="list-decimal list-inside space-y-1 pl-2 text-outline">
                            <li><code class="text-white font-mono">ScoreCalculationService</code> menghitung nilai akhir secara rekursif berdasarkan bobot persentase tiap cabang.</li>
                            <li><code class="text-white font-mono">ServiceGrade::konversi</code> mengubah skor angka menjadi huruf mutu (A, B+, C, dll.) dan bobot IP.</li>
                            <li>Sistem mengisi <code class="text-white font-mono">KhsDetail</code>: <code class="text-tertiary font-mono">nilai_akhir</code>, <code class="text-tertiary font-mono">grade</code>, <code class="text-tertiary font-mono">score</code>, dan mengubah <code class="text-tertiary font-mono">tidak_berhak = 'A'</code> (Aktif/Berhak).</li>
                            <li>Jika seluruh mahasiswa kelas tsb selesai divalidasi, status <code class="text-white font-mono">assessment_dosen</code> ditutup menjadi <code class="text-tertiary font-mono">'selesai'</code>. Mahasiswa langsung dapat melihat nilai resminya di KHS!</li>
                        </ol>
                        <div class="p-3 rounded-lg bg-surface-container-lowest font-mono text-[11px] text-outline border border-[#222d45]">
                            <strong>Tabel Terlibat:</strong> <code class="text-primary">khs_detail</code>, <code class="text-secondary">validation_student_scores</code>, <code class="text-tertiary">penilaian_status</code>.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 4: REFERENSI KODE PROGRAM --}}
        <section id="arsitektur-kode" class="space-y-6 scroll-mt-20">
            <div class="border-b border-[#222d45] pb-3">
                <h3 class="text-lg md:text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[24px]">code</span>
                    Pemetaan Berkas & Arsitektur Kode Program di Laravel
                </h3>
                <p class="text-xs md:text-sm text-outline mt-0.5">Lokasi berkas Controller, Service, dan Model di dalam proyek.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container rounded-xl border border-[#222d45] p-4 space-y-2">
                    <h4 class="text-xs font-bold text-primary uppercase font-mono flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">folder</span> Controllers
                    </h4>
                    <ul class="text-xs text-on-surface-variant font-mono space-y-1.5">
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">AssessmentTemplateController</span>
                            <div class="text-[10px] text-outline">Api_Staff/AssessmentTemplateController.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">Staff AssessmentDosenController</span>
                            <div class="text-[10px] text-outline">Api_Staff/AssessmentDosenController.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">Dosen AssessmentDosenController</span>
                            <div class="text-[10px] text-outline">Api_Dosen/AssessmentDosenController.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">PenilaianDosenController</span>
                            <div class="text-[10px] text-outline">Api_Dosen/PenilaianDosenController.php</div>
                        </li>
                    </ul>
                </div>

                <div class="bg-surface-container rounded-xl border border-[#222d45] p-4 space-y-2">
                    <h4 class="text-xs font-bold text-secondary uppercase font-mono flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">build</span> Services
                    </h4>
                    <ul class="text-xs text-on-surface-variant font-mono space-y-1.5">
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">ServiceAssessmentDosen</span>
                            <div class="text-[10px] text-outline">App/Service/ServiceAssessmentDosen.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">TemplateBuilderService</span>
                            <div class="text-[10px] text-outline">App/Service/Assessment/TemplateBuilderService.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">ScoreCalculationService</span>
                            <div class="text-[10px] text-outline">App/Service/Assessment/ScoreCalculationService.php</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">ServiceGrade (Konversi)</span>
                            <div class="text-[10px] text-outline">App/Service/ServiceGrade.php</div>
                        </li>
                    </ul>
                </div>

                <div class="bg-surface-container rounded-xl border border-[#222d45] p-4 space-y-2">
                    <h4 class="text-xs font-bold text-tertiary uppercase font-mono flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">database</span> Eloquent Models
                    </h4>
                    <ul class="text-xs text-on-surface-variant font-mono space-y-1.5">
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">AssessmentTemplate</span>
                            <div class="text-[10px] text-outline">Tabel: assessment_templates</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">AssessmentDosen</span>
                            <div class="text-[10px] text-outline">Tabel: assessment_dosen</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">AssessmentDosenAssignment</span>
                            <div class="text-[10px] text-outline">Tabel: assessment_dosen_assignments</div>
                        </li>
                        <li class="p-2 rounded bg-surface-container-lowest border border-[#222d45]">
                            <span class="text-white font-semibold">AssessmentNodeValidation</span>
                            <div class="text-[10px] text-outline">Tabel: assessment_node_validations</div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

    </main>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#222d45] bg-surface-container-lowest py-6 px-4 md:px-8 text-center text-xs text-outline">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                API SISKA &bull; <strong>SIAKAD Kedokteran</strong> &bull; Sistem Penilaian Blok Terintegrasi {{ date('Y') }}
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.docs') }}" class="hover:text-primary transition-colors">API Docs</a>
                <span>&bull;</span>
                <a href="{{ route('admin.tester') }}" class="hover:text-secondary transition-colors">API Tester</a>
                <span>&bull;</span>
                <a href="#matrix-peran" class="hover:text-tertiary transition-colors">Wewenang Nilai</a>
            </div>
        </div>
    </footer>

</body>

</html>
