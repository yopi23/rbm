@extends('whatsapp.layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>WhatsApp Integration</h3>
            </div>
            <div class="card-body">
                <p>Connect your WhatsApp account to enable messaging capabilities directly from our platform.</p>

                <div class="mb-4">
                    <h4>Features:</h4>
                    <ul>
                        <li>Send messages to your clients directly from this platform</li>
                        <li>Manage multiple WhatsApp accounts</li>
                        <li>Secure connection with end-to-end encryption</li>
                        <li>Simple setup with QR code scanning</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h4>Connected Devices</h4>

                    @if ($devices->isEmpty())
                        <div class="alert alert-info">
                            No WhatsApp devices connected yet. Add a new device below.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>API Key</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="devices-table-body">
                                    @foreach ($devices as $device)
                                        <tr>
                                            <td>{{ $device->name }}</td>
                                            <td>{{ $device->phone_number ?? 'Not connected' }}</td>
                                            <td>
                                                <span
                                                    class="badge {{ $device->status === 'READY' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $device->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" class="form-control form-control-sm"
                                                        value="{{ $device->api_key }}" readonly>
                                                    <button class="btn btn-sm btn-outline-secondary copy-btn"
                                                        data-clipboard-text="{{ $device->api_key }}">Copy</button>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary refresh-status"
                                                    data-session-id="{{ $device->session_id }}">
                                                    <i class="fas fa-sync-alt"></i> Refresh
                                                </button>
                                                <a href="{{ route('whatsapp.show', $device->id) }}"
                                                    class="btn btn-sm btn-primary">Details</a>
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="event.preventDefault(); if(confirm('Are you sure you want to disconnect this device?')) document.getElementById('delete-form-{{ $device->id }}').submit();">
                                                    Disconnect
                                                </button>
                                                <form id="delete-form-{{ $device->id }}"
                                                    action="{{ route('whatsapp.devices.disconnect', $device->id) }}"
                                                    method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button id="refresh-status" class="btn btn-outline-secondary mt-2">
                            <i class="fas fa-sync-alt"></i> Refresh Status
                        </button>
                    @endif
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>Add New Device</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('whatsapp.devices.create') }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="name">Device Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Enter a name for this device" required>
                                <small class="form-text text-muted">Give this device a name to help you identify it (e.g.
                                    Personal WhatsApp, Business Account, etc.)</small>
                            </div>
                            <button type="submit" class="btn btn-success">Add Device</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto refresh semua device status saat halaman dimuat
            refreshAllDevices();

            // Event listener untuk tombol refresh per device
            document.addEventListener('click', function(event) {
                if (event.target.closest('.refresh-status')) {
                    let button = event.target.closest('.refresh-status');
                    let deviceId = button.getAttribute('data-session-id');

                    if (!deviceId) {
                        alert("Device ID tidak ditemukan!");
                        return;
                    }

                    // Menampilkan indikator loading pada tombol
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                    button.disabled = true;

                    // Memanggil API untuk refresh status device
                    fetch(`/whatsapp/devices/${deviceId}/refresh-status`)
                    headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Update phone number
                            phoneCell.textContent = data.phone_number || 'Not connected';

                            // Update status badge
                            statusCell.textContent = data.status;

                            if (data.status === 'READY') {
                                statusCell.classList.remove('bg-warning');
                                statusCell.classList.add('bg-success');
                            } else {
                                statusCell.classList.remove('bg-success');
                                statusCell.classList.add('bg-warning');
                            }

                            // Untuk tombol refresh
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        })
                }
            });
        });

        // Function untuk refresh semua device sekaligus
        function refreshAllDevices() {
            const rows = document.querySelectorAll('#devices-table-body tr');

            // Jika tidak ada device, keluar dari function
            if (rows.length === 0) return;

            // Untuk setiap device, lakukan refresh status
            rows.forEach(row => {
                const deviceId = row.querySelector('.refresh-status').getAttribute('data-session-id');
                const statusCell = row.querySelector('td:nth-child(3) .badge');
                const phoneCell = row.querySelector('td:nth-child(2)');

                // Tambahkan indikator loading pada status
                statusCell.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';

                fetch(`/whatsapp/devices/${deviceId}/refresh-status`)
                headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Update phone number
                        phoneCell.textContent = data.phone_number || 'Not connected';

                        // Update status badge
                        statusCell.textContent = data.status;

                        if (data.status === 'READY') {
                            statusCell.classList.remove('bg-warning');
                            statusCell.classList.add('bg-success');
                        } else {
                            statusCell.classList.remove('bg-success');
                            statusCell.classList.add('bg-warning');
                        }

                        // Untuk tombol refresh
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    })
            });
        }
    </script>
@endpush
