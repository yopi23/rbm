@extends('whatsapp.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm border-0 rounded-lg">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    <i class="fab fa-whatsapp text-success me-2 fs-4"></i>
                    <h3 class="m-0 fw-bold">{{ $device->name }}</h3>
                </div>
                <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Perangkat
                </a>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first() }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Perangkat</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-muted fw-semibold" style="width: 140px;">Session ID:</th>
                                        <td>{{ $device->session_id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">API Key:</th>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light"
                                                    value="{{ $device->api_key }}" readonly>
                                                <button class="btn btn-primary copy-btn"
                                                    data-clipboard-text="{{ $device->api_key }}">
                                                    <i class="fas fa-copy me-1"></i> Salin
                                                </button>
                                            </div>
                                            <small class="text-muted mt-1 d-block">Gunakan API key ini untuk mengirim pesan
                                                dari perangkat</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Status:</th>
                                        <td>
                                            <span id="device-status-badge"
                                                class="badge {{ isset($deviceStatus) && $deviceStatus['session']['status'] === 'READY' ? 'bg-success' : 'bg-warning' }} px-3 py-2 rounded-pill">
                                                <i
                                                    class="fas {{ isset($deviceStatus) && $deviceStatus['session']['status'] === 'READY' ? 'fa-check-circle' : 'fa-clock' }} me-1"></i>
                                                {{ isset($deviceStatus) ? $deviceStatus['session']['status'] : $device->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Nomor Telepon:</th>
                                        <td>
                                            @if (isset($deviceStatus) && isset($deviceStatus['session']['phoneNumber']))
                                                <span class="d-flex align-items-center">
                                                    <i class="fas fa-phone-alt me-2 text-success"></i>
                                                    {{ $deviceStatus['session']['phoneNumber'] }}
                                                </span>
                                            @else
                                                <span class="text-muted"><i class="fas fa-unlink me-1"></i> Belum
                                                    terhubung</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Dibuat:</th>
                                        <td><i
                                                class="far fa-calendar-alt me-2"></i>{{ $device->created_at->format('d F Y H:i') }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-code me-2"></i>Contoh Penggunaan</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-tabs" id="codeExamples" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="php-tab" data-bs-toggle="tab"
                                            data-bs-target="#php" type="button" role="tab" aria-controls="php"
                                            aria-selected="true">PHP</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="js-tab" data-bs-toggle="tab" data-bs-target="#js"
                                            type="button" role="tab" aria-controls="js"
                                            aria-selected="false">JavaScript</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="codeExamplesContent">
                                    <div class="tab-pane fade show active p-3" id="php" role="tabpanel"
                                        aria-labelledby="php-tab">
                                        <pre class="bg-dark text-light p-3 rounded"><code>$client = new \GuzzleHttp\Client([
    'timeout' => 30, // Meningkatkan timeout untuk koneksi lemot
    'connect_timeout' => 10
]);

try {
    $response = $client->post('{{ url('api/send-message') }}', [
        'json' => [
            'api_key' => '{{ $device->api_key }}',
            'number' => '6281234567890', // Nomor telepon tujuan dengan kode negara
            'message' => 'Halo dari Laravel!'
        ]
    ]);

    $result = json_decode($response->getBody()->getContents(), true);
} catch (\Exception $e) {
    // Menangani kesalahan koneksi
    $errorMessage = $e->getMessage();
}</code></pre>
                                        <button class="btn btn-sm btn-outline-primary copy-code" data-code-target="php">
                                            <i class="fas fa-copy me-1"></i> Salin Kode
                                        </button>
                                    </div>
                                    <div class="tab-pane fade p-3" id="js" role="tabpanel" aria-labelledby="js-tab">
                                        <pre class="bg-dark text-light p-3 rounded"><code>fetch('{{ url('api/send-message') }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        api_key: '{{ $device->api_key }}',
        number: '6281234567890', // Nomor telepon tujuan dengan kode negara
        message: 'Halo dari JavaScript!'
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => {
    console.error('Error:', error);
    // Menangani kesalahan koneksi
});</code></pre>
                                        <button class="btn btn-sm btn-outline-primary copy-code" data-code-target="js">
                                            <i class="fas fa-copy me-1"></i> Salin Kode
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-wifi me-2"></i>Status Koneksi</h5>
                            </div>
                            <div class="card-body text-center p-4" id="connection-status-container">
                                @if (isset($deviceStatus) && isset($deviceStatus['session']) && $deviceStatus['session']['status'] === 'READY')
                                    <div class="alert alert-success border-0 shadow-sm py-4">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <h5 class="fw-bold">WhatsApp Terhubung!</h5>
                                        <p class="mb-0">WhatsApp Anda berhasil terhubung dan siap digunakan.</p>
                                    </div>
                                @elseif(isset($deviceStatus) &&
                                        isset($deviceStatus['session']) &&
                                        $deviceStatus['session']['status'] === 'NEED_SCAN' &&
                                        isset($deviceStatus['qrCodeImage']))
                                    <div class="alert alert-warning border-0 shadow-sm py-4">
                                        <h5 class="fw-bold"><i class="fas fa-qrcode me-2"></i>Scan Kode QR untuk Terhubung
                                        </h5>
                                        <p>Buka WhatsApp di ponsel Anda, ketuk Menu atau Pengaturan dan pilih WhatsApp
                                            Web/Desktop</p>
                                        <div class="qr-container my-3 position-relative">
                                            <img src="{{ route('whatsapp.devices.qrcode', $device->id) }}" alt="Kode QR"
                                                class="img-fluid border p-2 rounded" style="max-width: 250px;"
                                                onerror="handleQrError(this)">
                                            <div
                                                class="qr-loading-overlay position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none justify-content-center align-items-center">
                                                <div class="spinner-border text-success" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center gap-2 mt-2">
                                            <span class="text-muted small" id="qr-refresh-timer">Auto refresh dalam <span
                                                    id="qr-countdown">30</span> detik</span>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" id="refresh-qr"
                                                title="Refresh Kode QR">
                                                <i class="fas fa-sync-alt"></i> Refresh Sekarang
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-danger border-0 shadow-sm py-4" id="connection-error">
                                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i>
                                        <h5 class="fw-bold">Masalah Koneksi</h5>
                                        @if (isset($connectionError))
                                            <p>Server WhatsApp tidak dapat dijangkau. Kemungkinan server sedang down atau
                                                ada masalah jaringan.</p>
                                            <div class="bg-light p-2 rounded mb-3 text-start">
                                                <small class="text-danger">{{ $connectionError }}</small>
                                            </div>
                                        @else
                                            <p>Status saat ini:
                                                {{ isset($deviceStatus) && isset($deviceStatus['session']) ? $deviceStatus['session']['status'] : $device->status }}
                                            </p>
                                        @endif
                                        <button class="btn btn-primary mt-2" id="refresh-connection">
                                            <i class="fas fa-sync-alt me-1"></i> Refresh Koneksi
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Tindakan</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <button class="btn btn-primary" id="refresh-status">
                                        <i class="fas fa-sync-alt me-2"></i> Refresh Status
                                    </button>

                                    <div class="server-status-info alert alert-info border-0 mb-3">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-server mt-1 me-2"></i>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Status Server</h6>
                                                <p class="mb-0">
                                                    @if (isset($connectionError))
                                                        <span class="text-danger"><i class="fas fa-times-circle me-1"></i>
                                                            Tidak terhubung</span>
                                                    @else
                                                        <span class="text-success"><i
                                                                class="fas fa-check-circle me-1"></i> Terhubung</span>
                                                    @endif
                                                    <span class="ms-2 text-muted"
                                                        id="server-address">(103.196.154.19:3000)</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-danger" onclick="confirmDisconnect()">
                                        <i class="fas fa-unlink me-2"></i> Putuskan WhatsApp
                                    </button>
                                    <form id="delete-form"
                                        action="{{ route('whatsapp.devices.disconnect', $device->id) }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize clipboard.js
            const clipboard = new ClipboardJS('.copy-btn');
            clipboard.on('success', function(e) {
                showToast('API key berhasil disalin!');
            });

            // Copy code examples
            const codeClipboard = new ClipboardJS('.copy-code', {
                text: function(trigger) {
                    const target = trigger.getAttribute('data-code-target');
                    return document.querySelector(`#${target} pre code`).textContent;
                }
            });
            codeClipboard.on('success', function(e) {
                showToast('Contoh kode berhasil disalin!');
            });

            // Refresh status button
            document.getElementById('refresh-status')?.addEventListener('click', function() {
                const button = this;
                button.disabled = true;
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Menyegarkan...';

                // Set a timeout in case the page doesn't reload
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Refresh Status';
                }, 5000);

                window.location.reload();
            });

            // Refresh connection button
            document.getElementById('refresh-connection')?.addEventListener('click', function() {
                const button = this;
                button.disabled = true;
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Mencoba terhubung...';

                // Set a timeout in case the page doesn't reload
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Refresh Koneksi';
                }, 5000);

                window.location.reload();
            });

            // QR Code auto-refresh functionality
            const qrImg = document.querySelector('.qr-container img');
            const qrCountdownElement = document.getElementById('qr-countdown');
            const qrLoadingOverlay = document.querySelector('.qr-loading-overlay');

            if (qrImg && qrCountdownElement) {
                let countdownValue = 30;
                let countdownPaused = false;

                // Start countdown timer
                const countdownInterval = setInterval(() => {
                    if (!countdownPaused) {
                        countdownValue--;
                        qrCountdownElement.textContent = countdownValue;

                        if (countdownValue <= 0) {
                            refreshQrCode();
                            countdownValue = 30;
                        }
                    }
                }, 1000);

                // Manual refresh button
                document.getElementById('refresh-qr')?.addEventListener('click', function() {
                    refreshQrCode();
                    countdownValue = 30;
                    qrCountdownElement.textContent = countdownValue;
                });

                // Function to refresh QR code
                function refreshQrCode() {
                    if (qrLoadingOverlay) {
                        qrLoadingOverlay.classList.remove('d-none');
                        qrLoadingOverlay.classList.add('d-flex');
                    }

                    // Create a new image element
                    const newImg = new Image();
                    const timestamp = new Date().getTime();
                    newImg.src = '{{ route('whatsapp.devices.qrcode', $device->id) }}?' + timestamp;

                    newImg.onload = function() {
                        qrImg.src = newImg.src;
                        if (qrLoadingOverlay) {
                            qrLoadingOverlay.classList.add('d-none');
                            qrLoadingOverlay.classList.remove('d-flex');
                        }

                        // If QR loads successfully, check status after 2 seconds
                        setTimeout(function() {
                            checkPageNeedsRefresh();
                        }, 2000);
                    };

                    newImg.onerror = function() {
                        // Handle errors loading the QR code
                        if (qrLoadingOverlay) {
                            qrLoadingOverlay.classList.add('d-none');
                            qrLoadingOverlay.classList.remove('d-flex');
                        }

                        // Show error in QR container
                        qrImg.src =
                            "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23f8f9fa'/%3E%3Cpath d='M35,45 L65,45 L65,55 L35,55 Z' fill='%23dc3545'/%3E%3Ccircle cx='50' cy='50' r='30' stroke='%23dc3545' stroke-width='5' fill='none'/%3E%3C/svg%3E";

                        // Pause countdown to prevent continuous errors
                        countdownPaused = true;
                        document.getElementById('qr-refresh-timer').innerHTML =
                            '<span class="text-danger">Gagal memuat QR. <a href="#" id="retry-qr">Coba lagi</a></span>';

                        // Add retry handler
                        document.getElementById('retry-qr')?.addEventListener('click', function(e) {
                            e.preventDefault();
                            countdownPaused = false;
                            countdownValue = 30;
                            qrCountdownElement.textContent = countdownValue;
                            document.getElementById('qr-refresh-timer').innerHTML =
                                'Auto refresh dalam <span id="qr-countdown">30</span> detik';
                            refreshQrCode();
                        });
                    };
                }

                // Check if page needs refresh (device connected)
                function checkPageNeedsRefresh() {
                    // Use a simple AJAX request to check current page
                    // This is a lightweight alternative to fetching the full status
                    const xhr = new XMLHttpRequest();
                    xhr.open('HEAD', window.location.href, true);
                    xhr.timeout = 5000; // 5 second timeout

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                // Refresh every 5 seconds to check status
                                setTimeout(function() {
                                    window.location.reload();
                                }, 5000);
                            }
                        }
                    };

                    xhr.onerror = function() {
                        console.error('Error checking page refresh status');
                    };

                    xhr.send();
                }
            }
        });

        // Function to handle QR image load errors
        function handleQrError(img) {
            img.src =
                "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23f8f9fa'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='12' text-anchor='middle' fill='%23dc3545'%3EGagal memuat QR%3C/text%3E%3C/svg%3E";
            img.parentNode.classList.add('qr-error');

            const refreshTimer = document.getElementById('qr-refresh-timer');
            if (refreshTimer) {
                refreshTimer.innerHTML = '<span class="text-danger">Gagal memuat QR. Server mungkin sedang down.</span>';
            }
        }

        // Toast notification function
        function showToast(message) {
            Swal.fire({
                text: message,
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Confirmation dialog for disconnect
        function confirmDisconnect() {
            Swal.fire({
                title: 'Putuskan WhatsApp?',
                text: 'Apakah Anda yakin ingin memutuskan akun WhatsApp ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, putuskan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const deleteForm = document.getElementById('delete-form');

                    // Add a loading indicator to the button
                    const disconnectBtn = document.querySelector('button[onclick="confirmDisconnect()"]');
                    if (disconnectBtn) {
                        disconnectBtn.disabled = true;
                        disconnectBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memutuskan...';
                    }

                    deleteForm.submit();
                }
            });
        }
    </script>
@endpush
