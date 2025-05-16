<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Tabel Data HP</h4>
    </div>
    <div class="card-body">
        <a href="{{ route('admin.tg.create') }}" class="btn btn-primary mb-3">Tambah Data HP</a>
        <a href="{{ route('admin.tg.index') }}" class="btn btn-secondary mb-3 ml-2">Lihat Data Berdasarkan Posisi
            Kamera</a>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Posisi Kamera</th>
                        <th>Ukuran layar</th>
                        @foreach ($brands as $brand)
                            <th>{{ $brand->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rowNum = 0;
                        $currentCameraGroup = null;
                        $currentCameraPosition = null;
                    @endphp

                    @foreach ($matrix as $row)
                        @php
                            $rowNum++;

                            // Check if we're starting a new camera position within the group
$newCameraPosition = $currentCameraPosition !== $row['camera_position'];
if ($newCameraPosition) {
    $currentCameraPosition = $row['camera_position'];
    $cameraPositionCount = count(
        array_filter($matrix, function ($r) use ($currentCameraPosition) {
            return $r['camera_position'] === $currentCameraPosition;
                                    }),
                                );
                            }
                        @endphp

                        <tr>
                            <td>{{ $rowNum }}</td>

                            @if ($newCameraPosition)
                                <td rowspan="{{ $cameraPositionCount }}">{{ $row['camera_position'] }}</td>
                            @endif

                            <td>{{ $row['screen_size'] }}</td>

                            @foreach ($brands as $brand)
                                <td>{{ $row[$brand->name] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
