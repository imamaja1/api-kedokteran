@extends('admin.layout')
@section('title', 'Konfigurasi Feeder')
@section('page-title', 'Konfigurasi Feeder')

@section('content')
<div class="container-fluid px-0">

    {{-- Breadcrumb & Info Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Konfigurasi Feeder</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold"><i class="bi bi-hdd-network-fill text-primary me-2"></i>Konfigurasi Feeder PDDikti</h4>
        </div>
        <div>
            <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-shield-lock-fill text-success me-1"></i> Terenkripsi AES-256-CBC
            </span>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
        <div class="flex-grow-1">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
        <div class="flex-grow-1">{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row g-4">
        {{-- Kolom Kiri: Form Konfigurasi --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-sliders me-2"></i>Parameter Kredensial Web Service</span>
                    <small class="text-white-50">Tabel: <code>feeder_credentials</code></small>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.feeder-config.update') }}" method="POST" id="feederConfigForm">
                        @csrf

                        <div class="row g-3">
                            {{-- Feeder URL --}}
                            <div class="col-md-8">
                                <label for="feeder_url" class="form-label fw-semibold">
                                    Feeder Host / Base URL <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-globe2"></i></span>
                                    <input type="text"
                                        class="form-control @error('feeder_url') is-invalid @enderror"
                                        id="feeder_url"
                                        name="feeder_url"
                                        value="{{ old('feeder_url', $credentials['feeder_url'] ?? '') }}"
                                        placeholder="http://localhost atau https://feeder.kampus.ac.id"
                                        required>
                                    @error('feeder_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    Alamat IP atau domain server yang menjalankan aplikasi Neo Feeder PDDikti.
                                </div>
                            </div>

                            {{-- Feeder Port --}}
                            <div class="col-md-4">
                                <label for="feeder_port" class="form-label fw-semibold">Port Layanan</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-ethernet"></i></span>
                                    <input type="text"
                                        class="form-control @error('feeder_port') is-invalid @enderror"
                                        id="feeder_port"
                                        name="feeder_port"
                                        value="{{ old('feeder_port', $credentials['feeder_port'] ?? '') }}"
                                        placeholder="8082">
                                    @error('feeder_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    Port default Neo Feeder biasanya <code>8082</code> atau <code>8100</code>.
                                </div>
                            </div>

                            {{-- Web Service Endpoint --}}
                            <div class="col-md-12">
                                <label for="feeder_endpoint" class="form-label fw-semibold">Endpoint Web Service</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-diagram-2"></i></span>
                                    <input type="text"
                                        class="form-control @error('feeder_endpoint') is-invalid @enderror"
                                        id="feeder_endpoint"
                                        name="feeder_endpoint"
                                        value="{{ old('feeder_endpoint', $credentials['feeder_endpoint'] ?? 'ws/live2.php') }}"
                                        placeholder="ws/live2.php">
                                    @error('feeder_endpoint')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    Path endpoint Web Service Feeder (e.g. <code>ws/live2.php</code> untuk Live, atau <code>ws/sandbox2.php</code> untuk Sandbox).
                                </div>
                            </div>

                            <hr class="my-3 text-muted">

                            {{-- Username Feeder --}}
                            <div class="col-md-6">
                                <label for="feeder_username" class="form-label fw-semibold">Username / Kode PT</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text"
                                        class="form-control @error('feeder_username') is-invalid @enderror"
                                        id="feeder_username"
                                        name="feeder_username"
                                        value="{{ old('feeder_username', $credentials['feeder_username'] ?? '') }}"
                                        placeholder="Username Feeder">
                                    @error('feeder_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    Username akun administrator atau akun web service Feeder PDDikti.
                                </div>
                            </div>

                            {{-- Password Feeder --}}
                            <div class="col-md-6">
                                <label for="feeder_password" class="form-label fw-semibold">Password Feeder</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password"
                                        class="form-control @error('feeder_password') is-invalid @enderror"
                                        id="feeder_password"
                                        name="feeder_password"
                                        autocomplete="new-password"
                                        placeholder="{{ !empty($credentials['feeder_password']) ? '•••••••• (Tersimpan)' : 'Masukkan password' }}">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" title="Tampilkan / Sembunyikan">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                    @error('feeder_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text small">
                                    @if(!empty($credentials['feeder_password']))
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>Password telah tersimpan.</span> Biarkan kosong jika tidak ingin mengubahnya.
                                    @else
                                    Password disimpan terenkripsi menggunakan AES-256-CBC.
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-floppy-fill me-1"></i> Simpan Konfigurasi
                            </button>

                            <button type="button" class="btn btn-outline-success" id="btnTestConnection">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="testSpinner" role="status" aria-hidden="true"></span>
                                <i class="bi bi-lightning-charge-fill me-1" id="testIcon"></i> Uji Koneksi Feeder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Status & Informasi Endpoint --}}
        <div class="col-lg-4">
            {{-- Status Koneksi & Token Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cpu me-2 text-primary"></i>Status Feeder Token</span>
                    @if(!empty($credentials['feeder_token']))
                    <span class="badge bg-success"><i class="bi bi-check2 me-1"></i>Terhubung</span>
                    @else
                    <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Belum Ada Token</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Token diperoleh secara otomatis ketika melakukan autentikasi (<code>GetToken</code>) ke layanan Neo Feeder PDDikti.
                    </p>

                    @if(!empty($credentials['feeder_token']))
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Token Aktif Saat Ini:</label>
                        <div class="p-2 bg-light rounded border text-break font-monospace small">
                            {{ Str::limit($credentials['feeder_token'], 42, '...') }}
                        </div>
                        @if(isset($records['feeder_token']))
                        <div class="text-muted mt-1" style="font-size: .75rem;">
                            <i class="bi bi-clock-history me-1"></i> Diperbarui: {{ $records['feeder_token']->updated_at?->diffForHumans() ?? '—' }}
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-key-fill fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        <span class="small">Belum ada token tersimpan. Klik tombol <strong>Uji Koneksi Feeder</strong> untuk menguji dan mengambil token baru.</span>
                    </div>
                    @endif

                    {{-- Form terpisah untuk pengujian langsung dari tombol di sini --}}
                    <form action="{{ route('admin.feeder-config.test') }}" method="POST" id="directTestForm" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>

            {{-- Full WS URL Preview Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-semibold">
                    <i class="bi bi-link-45deg me-2 text-info"></i>Target Web Service URL
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">URL lengkap yang akan dituju untuk setiap transaksi data:</p>
                    <div class="p-2 bg-light rounded border text-break font-monospace small mb-2 text-primary">
                        {{ $wsUrl ?? 'Belum terkonfigurasi' }}
                    </div>
                    <div class="small text-muted">
                        Format payload JSON-RPC untuk Neo Feeder:
                        <pre class="bg-dark text-white p-2 rounded mt-1 mb-0" style="font-size: .75rem;">{
  "act": "GetToken",
  "username": "...",
  "password": "..."
}</pre>
                    </div>
                </div>
            </div>

            {{-- Info Keamanan --}}
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-2"></i>Keamanan Kredensial</h6>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><i class="bi bi-check2 text-primary me-1"></i> Password dan token disimpan dengan enkripsi simetris <strong>AES-256-CBC</strong>.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-primary me-1"></i> Kunci enkripsi menggunakan <code>APP_KEY</code> aplikasi secara otomatis.</li>
                        <li><i class="bi bi-check2 text-primary me-1"></i> Hanya user dengan hak akses <strong>Administrator</strong> yang dapat mengakses menu ini.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Hasil Pengujian Koneksi --}}
<div class="modal fade" id="testResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="testModalHeader">
                <h5 class="modal-title" id="testModalTitle">Hasil Uji Koneksi Feeder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testModalBody">
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-primary me-3" role="status"></div>
                    <div>Menghubungi server Feeder...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle Password Visibility
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('feeder_password');
    const pwdIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwdInput.getAttribute('type') === 'password';
            pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
            pwdIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    // Ajax Test Connection
    const btnTest = document.getElementById('btnTestConnection');
    const testSpinner = document.getElementById('testSpinner');
    const testIcon = document.getElementById('testIcon');
    const testModalEl = document.getElementById('testResultModal');
    const testModal = new bootstrap.Modal(testModalEl);
    const modalHeader = document.getElementById('testModalHeader');
    const modalTitle = document.getElementById('testModalTitle');
    const modalBody = document.getElementById('testModalBody');

    if (btnTest) {
        btnTest.addEventListener('click', function () {
            // Ambil data dari form saat ini
            const url = document.getElementById('feeder_url').value;
            const port = document.getElementById('feeder_port').value;
            const endpoint = document.getElementById('feeder_endpoint').value;
            const username = document.getElementById('feeder_username').value;
            const password = document.getElementById('feeder_password').value;

            if (!url) {
                alert('Silakan isi Feeder Host / Base URL terlebih dahulu.');
                return;
            }

            // Tampilkan modal loading
            modalHeader.className = 'modal-header bg-light';
            modalTitle.innerText = 'Menguji Koneksi Feeder...';
            modalBody.innerHTML = `
                <div class="d-flex align-items-center py-3">
                    <div class="spinner-border text-primary me-3" role="status"></div>
                    <div>
                        <div class="fw-semibold">Sedang menghubungi Web Service Feeder...</div>
                        <div class="text-muted small">Mengirim permintaan <code>GetToken</code> ke server Feeder.</div>
                    </div>
                </div>
            `;
            testModal.show();

            testSpinner.classList.remove('d-none');
            testIcon.classList.add('d-none');
            btnTest.disabled = true;

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '{{ csrf_token() }}';

            fetch('{{ route("admin.feeder-config.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    feeder_url: url,
                    feeder_port: port,
                    feeder_endpoint: endpoint,
                    feeder_username: username,
                    feeder_password: password,
                }),
            })
            .then(async (response) => {
                const data = await response.json();
                testSpinner.classList.add('d-none');
                testIcon.classList.remove('d-none');
                btnTest.disabled = false;

                if (response.ok && data.status) {
                    modalHeader.className = 'modal-header bg-success text-white';
                    modalTitle.innerText = 'Koneksi Berhasil!';
                    modalBody.innerHTML = `
                        <div class="text-center py-2">
                            <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1">${data.message}</h6>
                            <p class="text-muted small mb-3">Token berhasil diverifikasi dan disimpan ke database.</p>
                            <div class="p-2 bg-light border rounded font-monospace small text-break">
                                ${data.token}
                            </div>
                        </div>
                    `;
                    // Reload saat modal ditutup agar perubahan token terlihat
                    testModalEl.addEventListener('hidden.bs.modal', function () {
                        window.location.reload();
                    }, { once: true });
                } else {
                    modalHeader.className = 'modal-header bg-danger text-white';
                    modalTitle.innerText = 'Koneksi Gagal';
                    modalBody.innerHTML = `
                        <div class="text-center py-2">
                            <i class="bi bi-x-circle-fill text-danger fs-1 mb-2 d-block"></i>
                            <h6 class="fw-bold mb-2">Gagal Terhubung ke Feeder</h6>
                            <div class="alert alert-danger text-start small mb-0">
                                ${data.message || 'Terjadi kesalahan tidak dikenal saat menghubungi Feeder.'}
                            </div>
                        </div>
                    `;
                }
            })
            .catch((err) => {
                testSpinner.classList.add('d-none');
                testIcon.classList.remove('d-none');
                btnTest.disabled = false;

                modalHeader.className = 'modal-header bg-danger text-white';
                modalTitle.innerText = 'Kesalahan Jaringan';
                modalBody.innerHTML = `
                    <div class="text-center py-2">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-1 mb-2 d-block"></i>
                        <h6 class="fw-bold mb-2">Gagal Mengirim Permintaan</h6>
                        <p class="text-muted small mb-0">${err.message || 'Periksa koneksi internet atau server lokal Anda.'}</p>
                    </div>
                `;
            });
        });
    }
});
</script>
@endpush
