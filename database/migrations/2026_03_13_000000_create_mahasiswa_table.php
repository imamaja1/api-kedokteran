<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMahasiswaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mahasiswa', function (Blueprint $table) {

            $table->char('nim', 11)->primary();
            $table->char('nik', 20);
            $table->char('npm', 23);
            $table->string('nisn', 20)->nullable();
            $table->char('nomor_pendaftaran', 13);
            $table->char('nomor_pendaftaran_ulang', 13);
            $table->integer('program_studi_kode')->nullable();
            $table->string('nama_mahasiswa', 125);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('alamat', 75)->nullable();
            $table->string('kota', 50)->nullable();

            $provinces = [
                'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Jambi', 'Sumatera Selatan', 'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung', 'Kepulauan Riau', 'Daerah Khusus Ibukota Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Daerah Istimewa Yogyakarta', 'Jawa Timur', 'Banten', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara', 'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua Barat', 'Papua',
            ];

            $table->enum('propinsi', $provinces)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('agama', ['Islam', 'Hindu', 'Kristen', 'Katolik', 'Budha', 'Konghucu'])->nullable();
            $table->enum('golongan_darah', ['O', 'A', 'AB', 'B', '-'])->nullable();
            $table->enum('kewarganegaraan', ['WNI', 'WNA'])->nullable();
            $table->string('nama_instansi', 75)->nullable();
            $table->string('email', 75)->nullable();
            $table->string('nama_ayah', 50)->nullable();
            $table->enum('agama_ayah', ['Islam', 'Hindu', 'Kristen', 'Katolik', 'Budha', 'Konghucu'])->nullable();
            $table->enum('pekerjaan_ayah', ['Pegawai Negeri Sipil', 'Pegawai Swasta', 'Wiraswasta', 'TNI/Polri', 'Dosen', 'Guru', 'Petani', 'Rumah Tangga', 'Lainnya'])->nullable();
            $table->string('nama_ibu', 50)->nullable();
            $table->enum('agama_ibu', ['Islam', 'Hindu', 'Kristen', 'Katolik', 'Budha', 'Konghucu'])->nullable();
            $table->enum('pekerjaan_ibu', ['Pegawai Negeri Sipil', 'Pegawai Swasta', 'Wiraswasta', 'TNI/Polri', 'Dosen', 'Guru', 'Petani', 'Rumah Tangga', 'Lainnya'])->nullable();
            $table->string('alamat_orangtua', 75)->nullable();
            $table->string('kota_orangtua', 50)->nullable();
            $table->enum('propinsi_orangtua', $provinces)->nullable();
            $table->string('telepon_orangtua', 20)->nullable();
            $table->string('foto', 100)->nullable();
            $table->string('sandi', 255)->nullable();
            $table->enum('status', ['A', 'N'])->default('N');
            $table->enum('status_pendaftaran', ['B', 'T', 'L'])->nullable();
            $table->integer('ta_lulus')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mahasiswa');
    }
}
