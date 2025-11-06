<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\QrCodeAttendance;
use App\Models\WorkSchedule;
use App\Models\Violation;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Scan QR Code
    public function scanQrCode(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'location' => 'nullable|string',
        ]);

        // Find the QR code
        $qrCode = QrCodeAttendance::where('token', $request->token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$qrCode) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid atau sudah kadaluarsa'
            ], 400);
        }

        $userId = auth()->id();
        $today = Carbon::today();
        $type = $qrCode->type;

        // Untuk check-in
        if ($type == 'check_in') {
            // Cari apakah sudah ada record absensi untuk user ini di hari ini
            $existingAttendance = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $today)
                ->first();

            // Jika record sudah ada, lakukan pengecekan lebih lanjut
            if ($existingAttendance) {
                // 1. Cek jika karyawan sudah pernah check-in (kolom check_in tidak kosong)
                if ($existingAttendance->check_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah melakukan check-in hari ini'
                    ], 400);
                }

                // 2. Cek jika statusnya adalah izin, sakit, atau cuti. Jika ya, gagalkan check-in.
                if (in_array($existingAttendance->status, ['izin', 'sakit', 'cuti'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal check-in. Status Anda hari ini adalah ' . $existingAttendance->status . '.'
                    ], 400);
                }
            }

            // Get work schedule
            $schedule = WorkSchedule::where('user_id', $userId)
                ->where('day_of_week', $today->format('l'))
                ->first();

            // Calculate late minutes
            $lateMinutes = 0;
            if ($schedule && $schedule->is_working_day) {
                $checkInTime = Carbon::now();
                $scheduleStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . $schedule->start_time);

                if ($checkInTime->gt($scheduleStartTime)) {
                    $lateMinutes = $checkInTime->diffInMinutes($scheduleStartTime);
                }
            }

            // Create attendance record
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $userId,
                    'attendance_date' => $today,
                ],
                [
                    'check_in' => Carbon::now(),
                    'status' => 'hadir',
                    'location' => $request->location ?? 'Mobile App Scan',
                    'late_minutes' => $lateMinutes,
                    'created_by' => $userId,
                ]
            );

            // Create violation if late more than 30 minutes
            if ($lateMinutes > 30) {
                Violation::create([
                    'user_id' => $userId,
                    'violation_date' => $today,
                    'type' => 'telat',
                    'description' => "Terlambat $lateMinutes menit",
                    'penalty_percentage' => 5,
                    'status' => 'pending',
                    'created_by' => $userId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check in berhasil',
                'data' => [
                    'attendance' => $attendance,
                    'is_late' => $lateMinutes > 0,
                    'late_minutes' => $lateMinutes
                ]
            ]);
        }
        // Untuk check-out
        else if ($type == 'check_out') {
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $today)
                ->first();

            if (!$attendance || !$attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in hari ini'
                ], 400);
            }

            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-out hari ini'
                ], 400);
            }

            $attendance->update([
                'check_out' => Carbon::now(),
                'location' => $request->location ?? $attendance->location,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check out berhasil',
                'data' => $attendance
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tipe QR Code tidak valid'
        ], 400);
    }

    // Request leave
    public function requestLeave(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:izin,sakit,cuti',
            'note' => 'required|string',
        ]);

        $userId = auth()->id();
        $date = Carbon::parse($request->date);

        // Create attendance with status leave
        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'attendance_date' => $date,
            ],
            [
                'status' => $request->type,
                'note' => $request->note,
                'created_by' => $userId,
            ]
        );

        return response()->json([
            'success'=>'sukses',
        ]);
    }


    public function checkFaceEnrollmentStatus($user_id)
    {
        try {
            // Ambil data dari tabel user_details
            $userDetail = UserDetail::where('kode_user', $user_id)->first();

            if (!$userDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan'
                ], 404);
            }

            // Cek apakah user sudah punya face_embedding
            $isEnrolled = !empty($userDetail->face_embedding);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_enrolled' => $isEnrolled,
                    'user_name' => $userDetail->fullname ?? 'Unknown',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register wajah dengan validasi lebih ketat
     */
    public function registerFace(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required',
                'face_embedding' => 'required|string',
            ]);

            // Decode dan validasi embedding
            $embeddingArray = json_decode($request->face_embedding, true);

            if (!is_array($embeddingArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format data wajah tidak valid'
                ], 400);
            }

            // Validasi ukuran embedding (192 dimensi untuk MobileFaceNet, 512 untuk FaceNet)
            $expectedDimension = 192; // MobileFaceNet
            $actualDimension = count($embeddingArray);

            // Support both models
            if (!in_array($actualDimension, [192, 512])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dimensi data wajah tidak sesuai. Expected: 192 atau 512, Got: ' . $actualDimension
                ], 400);
            }

            // Validasi nilai embedding (tidak boleh NaN atau Infinity)
            foreach ($embeddingArray as $value) {
                if (!is_numeric($value) || is_nan($value) || is_infinite($value)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data wajah mengandung nilai tidak valid'
                    ], 400);
                }
            }

            // Cari user
            $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

            if (!$userDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan'
                ], 404);
            }

            // Cek apakah sudah pernah mendaftar
            if (!empty($userDetail->face_embedding)) {
                \Log::info('User mencoba registrasi ulang', ['user_id' => $request->user_id]);

                // Izinkan update jika user memang ingin update wajah
                // Tapi beri peringatan
            }

            // Simpan face embedding
            $userDetail->face_embedding = $request->face_embedding;
            $userDetail->face_registered_at = now(); // Tambahkan kolom ini di migration
            $userDetail->save();

            \Log::info('Face registration successful', [
                'user_id' => $request->user_id,
                'embedding_size' => count($embeddingArray)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil didaftarkan! Sekarang Anda dapat menggunakan fitur absensi wajah.',
                'data' => [
                    'user_name' => $userDetail->fullname,
                    'registered_at' => $userDetail->face_registered_at,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Face registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan wajah: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan face untuk absensi dengan validasi dan logging lebih baik
     */
    public function scanFaceAttendance(Request $request)
    {
        try {
            \Log::info('Face attendance request received', [
                'user_id' => $request->user_id,
                'type' => $request->type,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            $request->validate([
                'user_id' => 'required',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'type' => 'required|in:check_in,check_out',
                'live_embedding' => 'required|string',
            ]);

            $userId = $request->user_id;
            $userDetail = UserDetail::where('kode_user', $userId)->first();

            if (!$userDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan'
                ], 404);
            }

            // Cek enrollment
            if (empty($userDetail->face_embedding)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah terlebih dahulu di menu Pengaturan.'
                ], 403);
            }

            // Decode dan validasi embeddings
            $storedEmbedding = json_decode($userDetail->face_embedding, true);
            $liveEmbedding = json_decode($request->live_embedding, true);

            // Validasi format
            if (!is_array($storedEmbedding) || !is_array($liveEmbedding)) {
                \Log::error('Invalid embedding format', [
                    'user_id' => $userId,
                    'stored_is_array' => is_array($storedEmbedding),
                    'live_is_array' => is_array($liveEmbedding)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Format data wajah tidak valid'
                ], 400);
            }

            // Validasi dimensi
            if (count($storedEmbedding) !== count($liveEmbedding)) {
                \Log::error('Embedding dimension mismatch', [
                    'stored_size' => count($storedEmbedding),
                    'live_size' => count($liveEmbedding)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dimensi data wajah tidak cocok. Silakan daftarkan wajah ulang.'
                ], 400);
            }

            // Hitung similarity
            $similarity = $this->calculateCosineSimilarity($storedEmbedding, $liveEmbedding);

            \Log::info('Face similarity calculated', [
                'user_id' => $userId,
                'similarity' => $similarity,
                'similarity_percentage' => round($similarity * 100, 2)
            ]);

            // Threshold lebih fleksibel: 0.70 = 70% similarity
            // Untuk production, bisa diturunkan ke 0.65-0.70 tergantung testing
            $threshold = 0.80;

            if ($similarity < $threshold) {
                \Log::warning('Face verification failed', [
                    'user_id' => $userId,
                    'similarity' => $similarity,
                    'threshold' => $threshold
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal. Wajah tidak cocok dengan data terdaftar.',
                    'data' => [
                        'similarity' => round($similarity * 100, 2) . '%',
                        'threshold' => round($threshold * 100, 2) . '%',
                        'tips' => [
                            'Pastikan pencahayaan cukup',
                            'Hadapkan wajah langsung ke kamera',
                            'Lepas masker/kacamata',
                            'Jika masih gagal, coba daftar ulang wajah'
                        ]
                    ]
                ], 401);
            }

            // Validasi lokasi jika required
            if ($userDetail->is_outside_office == 0) {
                // Definisikan variabel untuk menyimpan lokasi kantor yang akan digunakan
                $officeLat = $userDetail->default_lat;
                $officeLon = $userDetail->default_lon;
                $isUplineLocation = false; // Flag untuk melacak apakah lokasi dari upline
                $idUpline = $userDetail->id_upline ?? null; // Asumsi kolom: id_upline di user_details

                // 1. Cek apakah lokasi di akun pengguna kosong
                if (empty($officeLat) || empty($officeLon)) {
                    // 2. Jika kosong, cek Upline
                    if ($idUpline) {
                        // 3. Ambil data UserDetail dari Upline
                        $uplineDetail = UserDetail::where('kode_user', $idUpline)->first();

                        if ($uplineDetail) {
                            // 4. Gunakan lokasi Upline jika tersedia
                            if (!empty($uplineDetail->default_lat) && !empty($uplineDetail->default_lon)) {
                                $officeLat = $uplineDetail->default_lat;
                                $officeLon = $uplineDetail->default_lon;
                                $isUplineLocation = true;
                                \Log::info('Using upline location for attendance', ['user_id' => $userId, 'upline_id' => $idUpline]);
                            }
                        }
                    }

                    // 5. Jika lokasi (milik user atau upline) masih kosong, keluarkan error
                    if (empty($officeLat) || empty($officeLon)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Lokasi kantor belum diatur. Hubungi admin untuk mengatur lokasi kantor Anda atau upline Anda.'
                        ], 400);
                    }
                }

                // Lanjutkan dengan perhitungan jarak menggunakan $officeLat dan $officeLon
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $officeLat,
                    $officeLon
                );

                $maxDistance = $userDetail->allowed_radius_m ?? 10;

                if ($distance > $maxDistance) {
                    \Log::warning('Location validation failed', [
                        'user_id' => $userId,
                        'distance' => $distance,
                        'max_distance' => $maxDistance,
                        'location_source' => $isUplineLocation ? 'Upline' : 'Self'
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => "Lokasi Anda terlalu jauh dari kantor " . ($isUplineLocation ? "(lokasi Upline)" : "") . ".\nJarak: " . round($distance, 2) . "m\nMaksimal: {$maxDistance}m",
                        'data' => [
                            'current_distance' => round($distance, 2),
                            'max_allowed' => $maxDistance,
                            'unit' => 'meters'
                        ]
                    ], 400);
                }
            }

            // Validasi jadwal kerja
            $today = Carbon::today();
            $dayName = $today->format('l');

            $schedule = WorkSchedule::where('user_id', $userId)
                ->where('day_of_week', $dayName)
                ->first();

            if (!$schedule || !$schedule->is_working_day) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki jadwal kerja hari ini (' . $dayName . ')'
                ], 400);
            }

            // Proses absensi
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $today)
                ->first();

            $isLate = false;
            $lateMinutes = 0;
            $message = '';

            if ($request->type === 'check_in') {
                // Validasi check-in
                if ($attendance && $attendance->check_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah check-in hari ini pada ' .
                                    Carbon::parse($attendance->check_in)->format('H:i')
                    ], 400);
                }

                if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'cuti'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status hari ini adalah ' . $attendance->status . '. Tidak dapat check-in.'
                    ], 400);
                }

                // Hitung keterlambatan
                $checkInTime = Carbon::now();
                $scheduleTime = Carbon::parse($schedule->start_time);
                $scheduledTime = Carbon::create(
                    $today->year,
                    $today->month,
                    $today->day,
                    $scheduleTime->hour,
                    $scheduleTime->minute,
                    0
                );

                if ($checkInTime->gt($scheduledTime)) {
                    $isLate = true;
                    $lateMinutes = $checkInTime->diffInMinutes($scheduledTime);
                }

                // Simpan attendance
                if ($attendance) {
                    $attendance->update([
                        'check_in' => $checkInTime,
                        'status' => 'hadir',
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'location' => "Face Recognition | Lat:{$request->latitude}, Lon:{$request->longitude}",
                    ]);
                } else {
                    $attendance = Attendance::create([
                        'user_id' => $userId,
                        'attendance_date' => $today,
                        'check_in' => $checkInTime,
                        'status' => 'hadir',
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'location' => "Face Recognition | Lat:{$request->latitude}, Lon:{$request->longitude}",
                        'created_by' => $userId,
                    ]);
                }

                // Buat violation jika terlambat > 30 menit
                if ($lateMinutes > 30) {
                    Violation::create([
                        'user_id' => $userId,
                        'violation_date' => $today,
                        'type' => 'telat',
                        'description' => "Terlambat $lateMinutes menit (Absensi Wajah)",
                        'penalty_percentage' => 5,
                        'status' => 'pending',
                        'created_by' => $userId,
                    ]);

                    \Log::info('Violation created for late attendance', [
                        'user_id' => $userId,
                        'late_minutes' => $lateMinutes
                    ]);
                }

                $message = $isLate
                    ? "Check-in berhasil! ⚠️ Anda terlambat $lateMinutes menit"
                    : 'Check-in berhasil! ✓ Selamat bekerja';

            } else {
                // Check-out
                if (!$attendance || !$attendance->check_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda harus check-in terlebih dahulu sebelum check-out'
                    ], 400);
                }

                if ($attendance->check_out) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah check-out hari ini pada ' .
                                    Carbon::parse($attendance->check_out)->format('H:i')
                    ], 400);
                }

                $attendance->update([
                    'check_out' => Carbon::now(),
                    'location' => $attendance->location . " | Checkout Face",
                ]);

                $message = 'Check-out berhasil! ✓ Terima kasih atas kerja keras Anda hari ini';
            }

            \Log::info('Face attendance successful', [
                'user_id' => $userId,
                'type' => $request->type,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'similarity' => round($similarity * 100, 2)
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_late' => $isLate,
                    'late_minutes' => $lateMinutes,
                    'similarity' => round($similarity * 100, 2) . '%',
                    'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                    'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                    'attendance_date' => $attendance->attendance_date,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in face attendance', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in face attendance: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user_id ?? 'unknown'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
/**
 * Hapus data wajah user
 */
public function deleteFaceData($user_id)
{
    try {
        $userDetail = UserDetail::where('kode_user', $user_id)->first();

        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        if (empty($userDetail->face_embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data wajah yang terdaftar'
            ], 400);
        }

        $userDetail->face_embedding = null;
        $userDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Data wajah berhasil dihapus. Anda perlu mendaftar ulang untuk menggunakan fitur absensi wajah.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus data wajah: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Helper: Hitung Cosine Similarity antara dua embedding
     */
    private function calculateCosineSimilarity($embedding1, $embedding2)
    {
        if (count($embedding1) !== count($embedding2)) {
            throw new \Exception('Dimensi embedding tidak cocok. Expected: ' . count($embedding1) . ', Got: ' . count($embedding2));
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $val1 = floatval($embedding1[$i]);
            $val2 = floatval($embedding2[$i]);

            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            \Log::warning('Zero magnitude detected in similarity calculation');
            return 0;
        }

        $similarity = $dotProduct / ($magnitude1 * $magnitude2);

        // Clamp nilai antara 0 dan 1
        return max(0, min(1, $similarity));
    }

/**
 * Helper: Cek lokasi menggunakan koordinat dari user_details
 */
private function checkLocation($userLat, $userLon, $officeLat, $officeLon, $maxDistance = 100)
{
    // Jika koordinat kantor tidak ada, skip validasi lokasi
    if (empty($officeLat) || empty($officeLon)) {
        return true;
    }

    $distance = $this->calculateDistance(
        $userLat,
        $userLon,
        $officeLat,
        $officeLon
    );

    \Log::info("Distance check: {$distance}m (max: {$maxDistance}m)");

    return $distance <= $maxDistance;
}

/**
 * Helper: Hitung jarak menggunakan Haversine formula
 */
private function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000; // meter

    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(
        pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
    ));

    return $angle * $earthRadius;
}

    // Helper: Get User
    public function getThisUser()
    {
        $userId = Auth::id();

        $userDetail = DB::table('user_details')
            ->where('kode_user', $userId)
            ->first();

        return $userDetail;
    }

    // Get attendance status
    public function getStatus(Request $request)
    {
        $userId = auth()->id();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

        // Get work schedule
        $schedule = WorkSchedule::where('user_id', $userId)
            ->where('day_of_week', $today->format('l'))
            ->first();

        $workHours = null;
        $isWorkDay = false;

        if ($schedule) {
            $isWorkDay = $schedule->is_working_day;
            if ($isWorkDay) {
                $workHours = [
                    'start' => $schedule->start_time,
                    'end' => $schedule->end_time
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $today->format('Y-m-d'),
                'is_work_day' => $isWorkDay,
                'work_hours' => $workHours,
                'attendance' => $attendance,
                'has_checked_in' => $attendance && $attendance->check_in ? true : false,
                'has_checked_out' => $attendance && $attendance->check_out ? true : false,
                'status' => $attendance ? $attendance->status : null
            ]
        ]);
    }

}
