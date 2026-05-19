# API Endpoints Master Data - Staff

SQL INSERT untuk tabel `api_endpoints` dengan section_id untuk Master Data routes.

## SQL Insert Statements

```sql
-- Pertama, buat section Master Data di tabel api_sections (jalankan ini dulu)
INSERT INTO `api_sections` (`title`, `sort_order`, `created_at`, `updated_at`) VALUES
('Master Data', 3, NOW(), NOW());

-- Master Data Endpoints
-- Matakuliah
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Matakuliah', 'Mengambil daftar semua matakuliah', 'GET', '/api/staff/master-data/matakuliah', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data matakuliah berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 1, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Matakuliah', 'Mengambil detail satu matakuliah', 'GET', '/api/staff/master-data/matakuliah-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data matakuliah berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 2, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Matakuliah', 'Membuat matakuliah baru', 'POST', '/api/staff/master-data/matakuliah', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Matakuliah berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 3, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Matakuliah', 'Mengubah data matakuliah', 'PUT', '/api/staff/master-data/matakuliah', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Matakuliah berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 4, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Matakuliah', 'Menghapus matakuliah', 'DELETE', '/api/staff/master-data/matakuliah/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Matakuliah berhasil dihapus."\r\n}', 5, NOW(), NOW());

-- Program Studi
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Program Studi', 'Mengambil daftar semua program studi', 'GET', '/api/staff/master-data/program-studi', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data program studi berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 6, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Program Studi', 'Mengambil detail satu program studi', 'GET', '/api/staff/master-data/program-studi-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data program studi berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 7, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Program Studi', 'Membuat program studi baru', 'POST', '/api/staff/master-data/program-studi', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Program studi berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 8, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Program Studi', 'Mengubah data program studi', 'PUT', '/api/staff/master-data/program-studi', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Program studi berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 9, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Program Studi', 'Menghapus program studi', 'DELETE', '/api/staff/master-data/program-studi/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Program studi berhasil dihapus."\r\n}', 10, NOW(), NOW());

-- Dosen
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Dosen', 'Mengambil daftar semua dosen', 'GET', '/api/staff/master-data/dosen', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data dosen berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 11, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Dosen', 'Mengambil detail satu dosen', 'GET', '/api/staff/master-data/dosen-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data dosen berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 12, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Dosen', 'Membuat dosen baru', 'POST', '/api/staff/master-data/dosen', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Dosen berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 13, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Dosen', 'Mengubah data dosen', 'PUT', '/api/staff/master-data/dosen', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Dosen berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 14, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Dosen', 'Menghapus dosen', 'DELETE', '/api/staff/master-data/dosen/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Dosen berhasil dihapus."\r\n}', 15, NOW(), NOW());

-- Nama Kurikulum
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Nama Kurikulum', 'Mengambil daftar semua nama kurikulum', 'GET', '/api/staff/master-data/nama-kurikulum', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data nama kurikulum berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 16, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Nama Kurikulum', 'Mengambil detail satu nama kurikulum', 'GET', '/api/staff/master-data/nama-kurikulum-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data nama kurikulum berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 17, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Nama Kurikulum', 'Membuat nama kurikulum baru', 'POST', '/api/staff/master-data/nama-kurikulum', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Nama kurikulum berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 18, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Nama Kurikulum', 'Mengubah data nama kurikulum', 'PUT', '/api/staff/master-data/nama-kurikulum', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Nama kurikulum berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 19, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Nama Kurikulum', 'Menghapus nama kurikulum', 'DELETE', '/api/staff/master-data/nama-kurikulum/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Nama kurikulum berhasil dihapus."\r\n}', 20, NOW(), NOW());

-- Tahun Akademik
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Tahun Akademik', 'Mengambil daftar semua tahun akademik', 'GET', '/api/staff/master-data/tahun-akademik', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data tahun akademik berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 21, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Tahun Akademik', 'Mengambil detail satu tahun akademik', 'GET', '/api/staff/master-data/tahun-akademik-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data tahun akademik berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 22, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Tahun Akademik', 'Membuat tahun akademik baru', 'POST', '/api/staff/master-data/tahun-akademik', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Tahun akademik berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 23, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Tahun Akademik', 'Mengubah data tahun akademik', 'PUT', '/api/staff/master-data/tahun-akademik', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Tahun akademik berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 24, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Tahun Akademik', 'Menghapus tahun akademik', 'DELETE', '/api/staff/master-data/tahun-akademik/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Tahun akademik berhasil dihapus."\r\n}', 25, NOW(), NOW());

-- Mahasiswa
INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get All Mahasiswa', 'Mengambil daftar semua mahasiswa', 'GET', '/api/staff/master-data/mahasiswa', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Data mahasiswa berhasil diambil.",\r\n  "data": [\r\n    {\r\n      "code": "...",\r\n      "nama": "..."\r\n    }\r\n  ]\r\n}', 26, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Get One Mahasiswa', 'Mengambil detail satu mahasiswa', 'GET', '/api/staff/master-data/mahasiswa-show', NULL, 'code (query parameter)', '{\r\n  "status": true,\r\n  "message": "Data mahasiswa berhasil diambil.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 27, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Create Mahasiswa', 'Membuat mahasiswa baru', 'POST', '/api/staff/master-data/mahasiswa', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Mahasiswa berhasil dibuat.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 28, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Update Mahasiswa', 'Mengubah data mahasiswa', 'PUT', '/api/staff/master-data/mahasiswa', NULL, '{\r\n  "code": "...",\r\n  "nama": "..."\r\n}', '{\r\n  "status": true,\r\n  "message": "Mahasiswa berhasil diubah.",\r\n  "data": {\r\n    "code": "...",\r\n    "nama": "..."\r\n  }\r\n}', 29, NOW(), NOW());

INSERT INTO `api_endpoints` (`api_section_id`, `title`, `description`, `method`, `url`, `headers`, `body`, `response_example`, `sort_order`, `created_at`, `updated_at`) VALUES
(3, 'Delete Mahasiswa', 'Menghapus mahasiswa', 'DELETE', '/api/staff/master-data/mahasiswa/{code}', NULL, NULL, '{\r\n  "status": true,\r\n  "message": "Mahasiswa berhasil dihapus."\r\n}', 30, NOW(), NOW());
```

## Catatan

- **PENTING**: Jalankan terlebih dahulu SQL `INSERT INTO api_sections` di awal kode (baris pertama di SQL block)
- Setelah itu barulah jalankan semua SQL `INSERT INTO api_endpoints`
- **api_section_id** = 3 (sudah sesuai dengan section_id untuk Master Data)
- Copy-paste keseluruhan SQL query dari atas (mulai dari api_sections), pastikan urutan eksekusi benar
- Jika error foreign key, pastikan api_sections sudah berhasil diinsert terlebih dahulu
