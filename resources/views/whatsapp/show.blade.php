@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>{{ $device->name }}</h3>
                <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">Back to Devices</a>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <h4>Device Information</h4>
                        <table class="table">
                            <tr>
                                <th>Session ID:</th>
                                <td>{{ $device->session_id }}</td>
                            </tr>
                            <tr>
                                <th>API Key:</th>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $device->api_key }}" readonly>
                                        <button class="btn btn-outline-secondary copy-btn"
                                            data-clipboard-text="{{ $device->api_key }}">Copy</button>
                                    </div>
                                    <small class="text-muted">Use this API key in your requests to send messages from this
                                        device</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span
                                        class="badge {{ isset($deviceStatus) && $deviceStatus['session']['status'] === 'READY' ? 'bg-success' : 'bg-warning' }}">
                                        {{ isset($deviceStatus) ? $deviceStatus['session']['status'] : $device->status }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Phone Number:</th>
                                <td>{{ isset($deviceStatus) && $deviceStatus['session']['phoneNumber'] ? $deviceStatus['session']['phoneNumber'] : $device->phone_number ?? 'Not connected' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>{{ $device->created_at->format('F d, Y H:i') }}</td>
                            </tr>
                        </table>

                        <div class="mt-4">
                            <h4>Usage Examples</h4>
                            <div class="card">
                                <div class="card-header">
                                    PHP Example
                                </div>
                                <div class="card-body">
                                    <pre><code>$client = new \GuzzleHttp\Client();
$response = $client->post('{{ url('api/send-message') }}', [
    'json' => [
        'api_key' => '{{ $device->api_key }}',
        'number' => '6281234567890', // Target phone number with country code
        'message' => 'Hello from Laravel!'
    ]
]);

$result = json_decode($response->getBody()->getContents(), true);</code></pre>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    JavaScript Example
                                </div>
                                <div class="card-body">
                                    <pre><code>fetch('{{ url('api/send-message') }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        api_key: '{{ $device->api_key }}',
        number: '6281234567890', // Target phone number with country code
        message: 'Hello from JavaScript!'
    })
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4>Connection Status</h4>
                            </div>
                            <div class="card-body text-center">
                                @if (isset($deviceStatus) && $deviceStatus['session']['status'] === 'READY')
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h5>WhatsApp Connected!</h5>
                                        <p>Your WhatsApp is successfully connected and ready to use.</p>
                                    </div>
                                @elseif(isset($deviceStatus) && ($deviceStatus['session']['status'] === 'NEED_SCAN' || $deviceStatus['qrCodeImage']))
                                    <div class="alert alert-warning">
                                        <h5>Scan QR Code to Connect</h5>
                                        <p>Open WhatsApp on your phone, tap Menu or Settings and select WhatsApp Web/Desktop
                                        </p>
                                        <div class="qr-container">
                                            <img src="{{ route('whatsapp.devices.qrcode', $device->id) }}" alt="QR Code"
                                                class="img-fluid" style="max-width: 250px;">
                                        </div>
                                        <button class="btn btn-primary mt-3" id="refresh-qr">Refresh QR Code</button>
                                    </div>
                                @else
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                        <h5>Connection Issue</h5>
                                        <p>Current status:
                                            {{ isset($deviceStatus) ? $deviceStatus['session']['status'] : $device->status }}
                                        </p>
                                        <button class="btn btn-primary mt-2" id="refresh-connection">Refresh
                                            Connection</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Actions</h4>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-info mb-2 w-100" id="refresh-status">Refresh Status</button>

                                <button class="btn btn-danger w-100"
                                    onclick="event.preventDefault(); if(confirm('Are you sure you want to disconnect this WhatsApp account?')) document.getElementById('delete-form').submit();">
                                    Disconnect WhatsApp
                                </button>
                                <form id="delete-form" action="{{ route('whatsapp.devices.disconnect', $device->id) }}"
                                    method="POST" style="display: none;">
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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize clipboard.js
            new ClipboardJS('.copy-btn');

            // Refresh connection status
            document.getElementById('refresh-status')?.addEventListener('click', function() {
                window.location.reload();
            });

            // Refresh QR Code
            document.getElementById('refresh-qr')?.addEventListener('click', function() {
                const qrImg = document.querySelector('.qr-container img');
                if (qrImg) {
                    qrImg.src = '{{ route('whatsapp.devices.qrcode', $device->id) }}?' + new Date()
                        .getTime();
                }
            });

            // Refresh connection
            document.getElementById('refresh-connection')?.addEventListener('click', function() {
                window.location.reload();
            });
        });
    </script>
@endpush
