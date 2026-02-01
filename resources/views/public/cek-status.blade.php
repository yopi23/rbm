<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cek Status - {{ $toko->nama_toko ?? 'Service Center' }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '{{ $toko->primary_color ?? "#10B981" }}',
                        secondary: '{{ $toko->secondary_color ?? "#059669" }}',
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 1000px;
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .animate-slideDown {
            animation: slideDown 0.4s ease-out forwards;
        }

        /* Tab styles */
        .tab-active {
            color: {{ $toko->primary_color ?? '#10B981' }};
            border-bottom: 3px solid {{ $toko->primary_color ?? '#10B981' }};
            font-weight: 600;
        }

        /* Custom focus styles */
        input:focus {
            outline: none;
            border-color: {{ $toko->primary_color ?? '#10B981' }};
            box-shadow: 0 0 0 3px {{ $toko->primary_color ?? '#10B981' }}20;
        }

        /* Button hover */
        .btn-primary:hover {
            background-color: {{ $toko->secondary_color ?? '#059669' }};
        }

        /* Status badge styles */
        .status-antrian { background-color: #FEF3C7; color: #92400E; }
        .status-proses { background-color: #DBEAFE; color: #1E40AF; }
        .status-selesai { background-color: #D1FAE5; color: #065F46; }
        .status-batal { background-color: #FEE2E2; color: #991B1B; }
        .status-diambil { background-color: #F3F4F6; color: #374151; }
        .status-pending { background-color: #FEF3C7; color: #92400E; }

        /* Warranty status */
        .warranty-active { background-color: #D1FAE5; color: #065F46; }
        .warranty-expired { background-color: #FEE2E2; color: #991B1B; }

        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid {{ $toko->primary_color ?? '#10B981' }};
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Barcode container */
        .barcode-container {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-2xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    @if($toko->logo_url)
                        <img src="{{ Storage::url($toko->logo_url) }}"
                             alt="{{ $toko->nama_toko }}"
                             class="h-10 w-10 object-contain rounded-xl shadow-sm">
                    @else
                        <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">{{ $toko->nama_toko ?? 'Service Center' }}</h1>
                        @if($toko->alamat_toko)
                            <p class="text-xs text-gray-500 line-clamp-1">{{ $toko->alamat_toko }}</p>
                        @endif
                    </div>
                </div>
                @if($toko->nomor_cs)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $toko->nomor_cs) }}"
                       target="_blank"
                       class="flex items-center space-x-1 text-sm text-green-600 hover:text-green-700 transition-colors bg-green-50 px-3 py-2 rounded-lg">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span class="font-medium">Hubungi CS</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 py-6">
        <!-- Card Container -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fadeInUp">
            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-100">
                <button onclick="switchTab('service')"
                        id="tab-service"
                        class="flex-1 py-4 px-6 text-center font-medium transition-all duration-200 tab-active">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Cek Service</span>
                    </div>
                </button>
                <button onclick="switchTab('garansi')"
                        id="tab-garansi"
                        class="flex-1 py-4 px-6 text-center font-medium transition-all duration-200 text-gray-500 hover:text-gray-700">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Cek Garansi</span>
                    </div>
                </button>
            </div>

            <!-- Tab Content: Service -->
            <div id="content-service" class="p-6">
                <form id="form-service" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Masukkan Kode Service
                        </label>
                        <div class="relative">
                            <input type="text"
                                   name="kode"
                                   id="kode-service"
                                   placeholder="Contoh: SRV-2026-001"
                                   autocomplete="off"
                                   class="w-full px-4 py-3.5 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-200">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Kode service dapat dilihat pada nota service Anda</p>
                    </div>
                    <button type="submit"
                            id="btn-service"
                            class="btn-primary w-full bg-primary text-white py-3.5 px-6 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg shadow-primary/25">
                        <span id="btn-text-service">Cek Status</span>
                        <div id="btn-loading-service" class="spinner hidden"></div>
                    </button>
                </form>

                <!-- Result Container Service -->
                <div id="result-service" class="mt-6 hidden"></div>
            </div>

            <!-- Tab Content: Garansi -->
            <div id="content-garansi" class="p-6 hidden">
                <form id="form-garansi" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Masukkan Kode Garansi
                        </label>
                        <div class="relative">
                            <input type="text"
                                   name="kode"
                                   id="kode-garansi"
                                   placeholder="Contoh: SRV-2026-001"
                                   autocomplete="off"
                                   class="w-full px-4 py-3.5 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-200">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Kode garansi sama dengan kode service pada nota</p>
                    </div>
                    <button type="submit"
                            id="btn-garansi"
                            class="btn-primary w-full bg-primary text-white py-3.5 px-6 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg shadow-primary/25">
                        <span id="btn-text-garansi">Cek Garansi</span>
                        <div id="btn-loading-garansi" class="spinner hidden"></div>
                    </button>
                </form>

                <!-- Result Container Garansi -->
                <div id="result-garansi" class="mt-6 hidden"></div>
            </div>
        </div>

        <!-- Help Text -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                Butuh bantuan?
                @if($toko->nomor_cs)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $toko->nomor_cs) }}"
                       class="text-primary font-medium hover:underline">
                        Hubungi kami
                    </a>
                @endif
            </p>
        </div>

        <!-- Powered By -->
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-400">Powered by RBM Service Management</p>
        </div>
    </main>

    <script>
        // Tab switching
        function switchTab(tab) {
            const contentService = document.getElementById('content-service');
            const contentGaransi = document.getElementById('content-garansi');
            const tabService = document.getElementById('tab-service');
            const tabGaransi = document.getElementById('tab-garansi');

            if (tab === 'service') {
                contentService.classList.remove('hidden');
                contentGaransi.classList.add('hidden');
                tabService.classList.add('tab-active');
                tabService.classList.remove('text-gray-500');
                tabGaransi.classList.remove('tab-active');
                tabGaransi.classList.add('text-gray-500');
            } else {
                contentGaransi.classList.remove('hidden');
                contentService.classList.add('hidden');
                tabGaransi.classList.add('tab-active');
                tabGaransi.classList.remove('text-gray-500');
                tabService.classList.remove('tab-active');
                tabService.classList.add('text-gray-500');
            }
        }

        // Loading toggle
        function toggleLoading(type, show) {
            const btnText = document.getElementById(`btn-text-${type}`);
            const btnLoading = document.getElementById(`btn-loading-${type}`);
            const btn = document.getElementById(`btn-${type}`);

            if (show) {
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            } else {
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        // Format currency
        function formatRupiah(number) {
            if (!number) return 'Rp 0';
            return 'Rp ' + parseInt(number).toLocaleString('id-ID');
        }

        // Get status class
        function getStatusClass(status) {
            const statusLower = (status || '').toLowerCase();
            const statusMap = {
                'antrian': 'status-antrian',
                'proses': 'status-proses',
                'selesai': 'status-selesai',
                'batal': 'status-batal',
                'diambil': 'status-diambil',
                'pending': 'status-pending'
            };
            return statusMap[statusLower] || 'status-pending';
        }

        // Render service result
        function renderServiceResult(data) {
            let partsHtml = '';
            if (data.parts && data.parts.length > 0) {
                partsHtml = `
                    <div class="border-t border-gray-100 pt-4 mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Sparepart Digunakan</h4>
                        <div class="space-y-2">
                            ${data.parts.map(p => `
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">${p.nama}</span>
                                    <span class="text-gray-500">x${p.qty}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            let garansiHtml = '';
            if (data.garansi && data.garansi.length > 0) {
                garansiHtml = `
                    <div class="border-t border-gray-100 pt-4 mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Garansi</h4>
                        <div class="space-y-2">
                            ${data.garansi.map(g => `
                                <div class="flex items-center justify-between p-3 rounded-lg ${g.status === 'active' ? 'bg-green-50' : 'bg-red-50'}">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">${g.nama}</p>
                                        <p class="text-xs text-gray-500">s/d ${g.tgl_exp}</p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${g.status === 'active' ? 'warranty-active' : 'warranty-expired'}">
                                        ${g.status === 'active' ? g.days_remaining + ' hari' : 'Expired'}
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            return `
                <div class="animate-slideDown">
                    <div class="barcode-container rounded-xl p-4 mb-4">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-center">${data.barcode}</div>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold ${getStatusClass(data.status)}">
                                ${data.status || 'Unknown'}
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Kode Service</p>
                                <p class="font-semibold text-gray-900">${data.kode_service}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Pelanggan</p>
                                <p class="font-semibold text-gray-900">${data.nama_pelanggan}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Unit</p>
                                <p class="font-semibold text-gray-900">${data.type_unit || '-'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Teknisi</p>
                                <p class="font-semibold text-gray-900">${data.teknisi}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Tanggal</p>
                                <p class="font-semibold text-gray-900">${data.created_at}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Biaya</p>
                                <p class="font-semibold text-gray-900">${formatRupiah(data.total_biaya)}</p>
                            </div>
                        </div>

                        ${data.keterangan ? `
                            <div class="border-t border-gray-200 pt-3 mt-3">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Keterangan</p>
                                <p class="text-sm text-gray-700 mt-1">${data.keterangan}</p>
                            </div>
                        ` : ''}

                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-500">DP: ${formatRupiah(data.dp)}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Sisa Pembayaran</p>
                                    <p class="text-lg font-bold text-primary">${formatRupiah(data.sisa)}</p>
                                </div>
                            </div>
                        </div>

                        ${partsHtml}
                        ${garansiHtml}
                    </div>
                </div>
            `;
        }

        // Render garansi result
        function renderGaransiResult(data) {
            return `
                <div class="animate-slideDown">
                    <div class="barcode-container rounded-xl p-4 mb-4">
                        <div class="text-center">${data.barcode}</div>
                    </div>

                    <div class="space-y-3">
                        ${data.items.map(item => `
                            <div class="bg-gray-50 rounded-xl p-4 ${item.status === 'active' ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-semibold text-gray-900">${item.nama}</h4>
                                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-600">${item.type}</span>
                                        </div>
                                        ${item.catatan ? `<p class="text-sm text-gray-600 mt-1">${item.catatan}</p>` : ''}
                                        <div class="mt-2 text-xs text-gray-500">
                                            <span>Mulai: ${item.tgl_mulai}</span>
                                            <span class="mx-2">|</span>
                                            <span>Exp: ${item.tgl_exp}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-3 py-1.5 rounded-full text-sm font-semibold ${item.status === 'active' ? 'warranty-active' : 'warranty-expired'}">
                                            ${item.status === 'active' ? 'Aktif' : 'Expired'}
                                        </span>
                                        ${item.status === 'active' ? `
                                            <p class="mt-2 text-sm font-medium text-green-600">${item.days_remaining} hari lagi</p>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Render error
        function renderError(message) {
            return `
                <div class="animate-slideDown">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-red-800">Data Tidak Ditemukan</h4>
                                <p class="text-sm text-red-600">${message}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Service form submit
        document.getElementById('form-service').addEventListener('submit', async function(e) {
            e.preventDefault();
            const kode = document.getElementById('kode-service').value.trim();
            const resultDiv = document.getElementById('result-service');

            if (!kode) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = renderError('Silakan masukkan kode service');
                return;
            }

            toggleLoading('service', true);

            try {
                const response = await fetch('{{ route("cek.service", $slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kode })
                });

                const data = await response.json();
                resultDiv.classList.remove('hidden');

                if (data.success) {
                    resultDiv.innerHTML = renderServiceResult(data.data);
                } else {
                    resultDiv.innerHTML = renderError(data.message);
                }
            } catch (error) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = renderError('Terjadi kesalahan. Silakan coba lagi.');
            }

            toggleLoading('service', false);
        });

        // Garansi form submit
        document.getElementById('form-garansi').addEventListener('submit', async function(e) {
            e.preventDefault();
            const kode = document.getElementById('kode-garansi').value.trim();
            const resultDiv = document.getElementById('result-garansi');

            if (!kode) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = renderError('Silakan masukkan kode garansi');
                return;
            }

            toggleLoading('garansi', true);

            try {
                const response = await fetch('{{ route("cek.garansi", $slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kode })
                });

                const data = await response.json();
                resultDiv.classList.remove('hidden');

                if (data.success) {
                    resultDiv.innerHTML = renderGaransiResult(data.data);
                } else {
                    resultDiv.innerHTML = renderError(data.message);
                }
            } catch (error) {
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = renderError('Terjadi kesalahan. Silakan coba lagi.');
            }

            toggleLoading('garansi', false);
        });

        // Auto-check from URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const kode = urlParams.get('kode');
            const tab = urlParams.get('tab'); // 'service' or 'garansi'

            if (tab && (tab === 'service' || tab === 'garansi')) {
                switchTab(tab);
            }

            if (kode) {
                const targetTab = (tab === 'garansi') ? 'garansi' : 'service';
                const inputId = `kode-${targetTab}`;
                const btnId = `btn-${targetTab}`;
                
                const inputElement = document.getElementById(inputId);
                if (inputElement) {
                    inputElement.value = kode;
                    // Trigger submit
                    document.getElementById(btnId).click();
                }
            }
        });
    </script>
</body>
</html>
