<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Terjemahan rule validasi umum ke Bahasa Indonesia.
    |
    */

    'accepted'             => ':attribute harus diterima.',
    'active_url'           => ':attribute bukan URL yang valid.',
    'after'                => ':attribute harus setelah tanggal :date.',
    'after_or_equal'       => ':attribute harus setelah atau sama dengan tanggal :date.',
    'alpha'                => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'           => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num'            => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                => ':attribute harus berupa array.',
    'before'               => ':attribute harus sebelum tanggal :date.',
    'before_or_equal'      => ':attribute harus sebelum atau sama dengan tanggal :date.',
    'between'              => [
        'array'   => ':attribute harus memiliki :min - :max item.',
        'file'    => ':attribute harus berukuran antara :min - :max kilobyte.',
        'numeric' => ':attribute harus antara :min - :max.',
        'string'  => ':attribute harus antara :min - :max karakter.',
    ],
    'boolean'              => ':attribute harus bernilai benar atau salah.',
    'confirmed'            => ':attribute tidak sesuai dengan konfirmasi.',
    'date'                 => ':attribute bukan tanggal yang valid.',
    'date_equals'          => ':attribute harus sama dengan tanggal :date.',
    'date_format'          => ':attribute tidak sesuai dengan format :format.',
    'different'            => ':attribute dan :other harus berbeda.',
    'digits'               => ':attribute harus terdiri dari :digits digit.',
    'digits_between'       => ':attribute harus antara :min dan :max digit.',
    'dimensions'           => ':attribute memiliki dimensi gambar yang tidak valid.',
    'distinct'             => ':attribute memiliki nilai duplikat.',
    'email'                => ':attribute harus berupa alamat email yang valid.',
    'ends_with'            => ':attribute harus diakhiri dengan salah satu dari: :values',
    'exists'               => ':attribute yang dipilih tidak valid.',
    'file'                 => ':attribute harus berupa berkas.',
    'filled'               => ':attribute wajib diisi.',
    'gt'                   => ':attribute harus lebih besar dari :value.',
    'gte'                  => ':attribute harus lebih besar dari atau sama dengan :value.',
    'image'                => ':attribute harus berupa gambar.',
    'in'                   => ':attribute yang dipilih tidak valid.',
    'in_array'             => ':attribute tidak terdapat di :other.',
    'integer'              => ':attribute harus berupa angka bulat.',
    'ip'                   => ':attribute harus berupa alamat IP yang valid.',
    'ipv4'                 => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6'                 => ':attribute harus berupa alamat IPv6 yang valid.',
    'json'                 => ':attribute harus berupa string JSON yang valid.',
    'lt'                   => ':attribute harus kurang dari :value.',
    'lte'                  => ':attribute harus kurang dari atau sama dengan :value.',
    'max'                  => [
        'array'   => ':attribute tidak boleh lebih dari :max item.',
        'file'    => ':attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string'  => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes'                => ':attribute harus berupa berkas tipe: :values.',
    'mimetypes'            => ':attribute harus berupa berkas tipe: :values.',
    'min'                  => [
        'array'   => ':attribute harus memiliki minimal :min item.',
        'file'    => ':attribute harus berukuran minimal :min kilobyte.',
        'numeric' => ':attribute harus minimal :min.',
        'string'  => ':attribute harus minimal :min karakter.',
    ],
    'not_in'               => ':attribute yang dipilih tidak valid.',
    'not_regex'            => ':attribute tidak sesuai dengan format.',
    'numeric'              => ':attribute harus berupa angka.',
    'present'              => ':attribute harus ada.',
    'regex'                => ':attribute tidak sesuai dengan format.',
    'required'             => ':attribute wajib diisi.',
    'required_if'          => ':attribute wajib diisi jika :other bernilai :value.',
    'required_unless'      => ':attribute wajib diisi kecuali :other bernilai :values.',
    'required_with'        => ':attribute wajib diisi saat :values ada.',
    'required_with_all'    => ':attribute wajib diisi saat :values ada.',
    'required_without'     => ':attribute wajib diisi saat :values tidak ada.',
    'required_without_all' => ':attribute wajib diisi saat :values tidak ada.',
    'same'                 => ':attribute dan :other harus sama.',
    'size'                 => [
        'array'   => ':attribute harus memiliki :size item.',
        'file'    => ':attribute harus berukuran :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string'  => ':attribute harus :size karakter.',
    ],
    'starts_with'          => ':attribute harus diawali dengan salah satu dari: :values',
    'string'               => ':attribute harus berupa teks.',
    'timezone'             => ':attribute harus berupa zona waktu yang valid.',
    'unique'               => ':attribute sudah digunakan.',
    'url'                  => ':attribute bukan URL yang valid.',
    'uuid'                 => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Attribute Names (Bahasa Indonesia)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        // Auth
        'email'                    => 'alamat email',
        'password'                 => 'kata sandi',
        'password_confirmation'    => 'konfirmasi kata sandi',
        'sandi'                    => 'kata sandi',
        'sandi_pengguna'           => 'kata sandi',
        'nim'                      => 'NIM',
        'kode_dosen'               => 'kode dosen',

        // Mahasiswa
        'nik'                      => 'NIK',
        'npm'                      => 'NPM',
        'nisn'                     => 'NISN',
        'nomor_pendaftaran'        => 'nomor pendaftaran',
        'nomor_pendaftaran_ulang'  => 'nomor pendaftaran ulang',
        'program_studi_kode'       => 'program studi',
        'nama_mahasiswa'           => 'nama mahasiswa',
        'tempat_lahir'             => 'tempat lahir',
        'tanggal_lahir'            => 'tanggal lahir',
        'alamat'                   => 'alamat',
        'kota'                     => 'kota',
        'propinsi'                 => 'provinsi',
        'telepon'                  => 'telepon',
        'jenis_kelamin'            => 'jenis kelamin',
        'agama'                    => 'agama',
        'golongan_darah'           => 'golongan darah',
        'kewarganegaraan'          => 'kewarganegaraan',
        'nama_instansi'            => 'nama instansi',
        'nama_ayah'                => 'nama ayah',
        'agama_ayah'               => 'agama ayah',
        'pekerjaan_ayah'           => 'pekerjaan ayah',
        'nama_ibu'                 => 'nama ibu',
        'agama_ibu'                => 'agama ibu',
        'pekerjaan_ibu'            => 'pekerjaan ibu',
        'alamat_orangtua'          => 'alamat orang tua',
        'kota_orangtua'            => 'kota orang tua',
        'propinsi_orangtua'        => 'provinsi orang tua',
        'telepon_orangtua'         => 'telepon orang tua',
        'foto'                     => 'foto',
        'status'                   => 'status',
        'status_pendaftaran'       => 'status pendaftaran',

        // Dosen
        'nama_dosen'               => 'nama dosen',
        'field_studi'              => 'field studi',
        'no_telp'                  => 'nomor telepon',
        'alamat_email'             => 'alamat email',
        'homebase'                 => 'homebase',
        'status_dosen'             => 'status dosen',

        // Matakuliah
        'kode_matakuliah'          => 'kode matakuliah',
        'nama_matakuliah'          => 'nama matakuliah',
        'sks_teori'                => 'SKS teori',
        'sks_praktik'              => 'SKS praktik',
        'block'                    => 'block',

        // Program Studi
        'nama_program_studi'       => 'nama program studi',
        'singkatan_program_studi'  => 'singkatan program studi',
        'kode_fakultas'            => 'kode fakultas',
        'kode_prodi_univ'          => 'kode prodi universitas',
        'kompetensi'               => 'kompetensi',

        // Kelas
        'nama_kelas'               => 'nama kelas',
        'semester'                 => 'semester',
        'kelas_id'                 => 'kelas',

        // KRS
        'kode_krs'                 => 'kode KRS',
        'kode_krs_detail'          => 'kode detail KRS',
        'code_krs_detail'          => 'kode detail KRS',
        'code'                     => 'kode',
        'id_matakuliah'            => 'matakuliah',
        'status_krs'               => 'status KRS',

        // Kurikulum
        'angkatan'                 => 'angkatan',
        'versi'                    => 'versi',
        'kode_nama_kurikulum'      => 'kode nama kurikulum',
        'nama_kurikulum'           => 'nama kurikulum',

        // Tahun Akademik
        'tahun_akademik'           => 'tahun akademik',
        'kode_tahun_akademik'      => 'kode tahun akademik',

        // Penilaian / Assessment
        'code_matakuliah'          => 'kode matakuliah',
        'code_kurikulum_angkatan'  => 'kode kurikulum angkatan',
        'structure'                => 'struktur',
        'node_key'                 => 'node key',
        'score'                    => 'nilai',
        'catatan'                  => 'catatan',
        'code_kelas'               => 'kode kelas',
        'code_mahasiswa'           => 'kode mahasiswa',
        'mahasiswa'                => 'mahasiswa',

        // Pembayaran
        'kode_pembayaran'          => 'kode pembayaran',
        'status_pembayaran'        => 'status pembayaran',
        'jumlah_bayar'             => 'jumlah bayar',
        'tanggal_bayar'            => 'tanggal bayar',
        'metode_bayar'             => 'metode bayar',
        'bukti_bayar'              => 'bukti bayar',
        'sks_override'             => 'override SKS',
        'sks_override_reason'      => 'alasan override SKS',

        // Perwalian
        'code_perwalian'           => 'kode perwalian',
        'code_dosen'               => 'kode dosen',
        'code_dosen_perwakilan'    => 'kode dosen perwakilan',
        'code_dosen_validator'     => 'kode dosen validator',

        // Umum
        'page'                     => 'halaman',
        'per_page'                 => 'item per halaman',
        'search'                   => 'pencarian',
        'sort'                     => 'pengurutan',
        'order'                    => 'urutan',
        'id'                       => 'ID',
        'name'                     => 'nama',
        'type'                     => 'tipe',
        'description'              => 'deskripsi',
        'start_date'               => 'tanggal mulai',
        'end_date'                 => 'tanggal selesai',
    ],

];
