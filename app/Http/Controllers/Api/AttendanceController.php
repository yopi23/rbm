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
use Illuminate\Support\Facades\Log;
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
            'success' => 'sukses',
        ]);
    }


    public function registerFace(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required',
                'face_embeddings' => 'required|array|min:3|max:10', // Min 3, max 10 foto
            ]);

            $embeddingsArray = [];
            $totalDimension = null;

            // Decode dan validasi setiap embedding
            foreach ($request->face_embeddings as $embeddingJson) {
                $embedding = json_decode($embeddingJson, true);

                if (!is_array($embedding)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format data wajah tidak valid'
                    ], 400);
                }

                // Validasi dimensi konsisten
                if ($totalDimension === null) {
                    $totalDimension = count($embedding);

                    // Support MobileFaceNet (192) atau FaceNet (512)
                    if (!in_array($totalDimension, [192, 512])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Dimensi data wajah tidak sesuai. Expected: 192 atau 512, Got: ' . $totalDimension
                        ], 400);
                    }
                } else if (count($embedding) !== $totalDimension) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dimensi embedding tidak konsisten'
                    ], 400);
                }

                // Validasi nilai embedding
                foreach ($embedding as $value) {
                    if (!is_numeric($value) || is_nan($value) || is_infinite($value)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Data wajah mengandung nilai tidak valid'
                        ], 400);
                    }
                }

                $embeddingsArray[] = $embedding;
            }

            // Hitung average embedding
            $averageEmbedding = $this->calculateAverageEmbedding($embeddingsArray);

            // Normalisasi L2
            $normalizedEmbedding = $this->normalizeEmbedding($averageEmbedding);

            // Cari user
            $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

            if (!$userDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan'
                ], 404);
            }

            // Cek validasi tambahan: apakah embedding terlalu mirip satu sama lain?
            $diversityScore = $this->calculateEmbeddingDiversity($embeddingsArray);

            if ($diversityScore < 0.02) {
                Log::warning('Low diversity in face embeddings', [
                    'user_id' => $request->user_id,
                    'diversity_score' => $diversityScore
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Foto terlalu mirip satu sama lain. Pastikan Anda menggerakkan kepala di setiap foto untuk variasi yang lebih baik.'
                ], 400);
            }

            // Simpan data
            $userDetail->face_embedding = json_encode($normalizedEmbedding);
            $userDetail->face_embedding_count = count($embeddingsArray); // Track jumlah foto
            $userDetail->face_registered_at = now();
            $userDetail->face_last_updated_at = now();
            $userDetail->save();

            Log::info('Face registration successful', [
                'user_id' => $request->user_id,
                'embeddings_count' => count($embeddingsArray),
                'dimension' => count($normalizedEmbedding),
                'diversity_score' => $diversityScore
            ]);

            return response()->json([
                'success' => true,
                'message' => "Wajah berhasil didaftarkan dari " . count($embeddingsArray) . " foto! 🎉",
                'data' => [
                    'user_name' => $userDetail->fullname,
                    'registered_at' => $userDetail->face_registered_at,
                    'photos_used' => count($embeddingsArray),
                    'diversity_score' => round($diversityScore, 4),
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Face registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan wajah: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan face untuk absensi dengan AUTO-UPDATE EMBEDDING
     * Setiap kali berhasil verifikasi, update embedding dengan weighted average
     */
    public function scanFaceAttendance(Request $request)
    {
        try {
            Log::info('Face attendance request received', [
                'user_id' => $request->user_id,
                'type' => $request->type,
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
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah terlebih dahulu.'
                ], 403);
            }

            // Decode embeddings
            $storedEmbedding = json_decode($userDetail->face_embedding, true);
            $liveEmbedding = json_decode($request->live_embedding, true);

            // Validasi format
            if (!is_array($storedEmbedding) || !is_array($liveEmbedding)) {
                Log::error('Invalid embedding format');
                return response()->json([
                    'success' => false,
                    'message' => 'Format data wajah tidak valid'
                ], 400);
            }

            // Validasi dimensi
            if (count($storedEmbedding) !== count($liveEmbedding)) {
                Log::error('Embedding dimension mismatch');
                return response()->json([
                    'success' => false,
                    'message' => 'Dimensi data wajah tidak cocok. Silakan daftarkan wajah ulang.'
                ], 400);
            }

            // Normalisasi live embedding
            $normalizedLiveEmbedding = $this->normalizeEmbedding($liveEmbedding);

            // Hitung similarity
            $similarity = $this->calculateCosineSimilarity($storedEmbedding, $normalizedLiveEmbedding);

            Log::info('Face similarity calculated', [
                'user_id' => $userId,
                'similarity' => $similarity,
                'similarity_percentage' => round($similarity * 100, 2)
            ]);

            // Threshold tetap 0.80 karena kita gunakan multiple embeddings + auto update
            $threshold = 0.80;

            if ($similarity < $threshold) {
                Log::warning('Face verification failed', [
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
                            'Jika terus gagal, coba daftar ulang wajah'
                        ]
                    ]
                ], 401);
            }

            // ✅ VERIFIKASI BERHASIL - UPDATE EMBEDDING dengan weighted average
            $this->updateFaceEmbeddingAfterSuccess(
                $userDetail,
                $storedEmbedding,
                $normalizedLiveEmbedding,
                $similarity
            );

            // Validasi lokasi
            if ($userDetail->is_outside_office == 0) {
                $officeLat = $userDetail->default_lat;
                $officeLon = $userDetail->default_lon;
                $isUplineLocation = false;
                $idUpline = $userDetail->id_upline ?? null;

                if (empty($officeLat) || empty($officeLon)) {
                    if ($idUpline) {
                        $uplineDetail = UserDetail::where('kode_user', $idUpline)->first();
                        if ($uplineDetail && !empty($uplineDetail->default_lat) && !empty($uplineDetail->default_lon)) {
                            $officeLat = $uplineDetail->default_lat;
                            $officeLon = $uplineDetail->default_lon;
                            $isUplineLocation = true;
                        }
                    }

                    if (empty($officeLat) || empty($officeLon)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Lokasi kantor belum diatur.'
                        ], 400);
                    }
                }

                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $officeLat,
                    $officeLon
                );

                $maxDistance = $userDetail->allowed_radius_m ?? 100;

                if ($distance > $maxDistance) {
                    return response()->json([
                        'success' => false,
                        'message' => "Lokasi terlalu jauh.\nJarak: " . round($distance, 2) . "m\nMaksimal: {$maxDistance}m"
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
                    'message' => 'Anda tidak memiliki jadwal kerja hari ini'
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
                if ($attendance && $attendance->check_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah check-in hari ini'
                    ], 400);
                }

                if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'cuti'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status hari ini: ' . $attendance->status
                    ], 400);
                }

                $checkInTime = Carbon::now();
                $scheduleTime = Carbon::parse($schedule->start_time);
                $scheduledTime = Carbon::create(
                    $today->year, $today->month, $today->day,
                    $scheduleTime->hour, $scheduleTime->minute, 0
                );

                if ($checkInTime->gt($scheduledTime)) {
                    $isLate = true;
                    $lateMinutes = $checkInTime->diffInMinutes($scheduledTime);
                }

                if ($attendance) {
                    $attendance->update([
                        'check_in' => $checkInTime,
                        'status' => 'hadir',
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'location' => "Face Recognition",
                    ]);
                } else {
                    $attendance = Attendance::create([
                        'user_id' => $userId,
                        'attendance_date' => $today,
                        'check_in' => $checkInTime,
                        'status' => 'hadir',
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'location' => "Face Recognition",
                        'created_by' => $userId,
                    ]);
                }

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

                $message = $isLate
                    ? "Check-in berhasil! ⚠️ Terlambat $lateMinutes menit"
                    : 'Check-in berhasil! ✓ Selamat bekerja';
            } else {
                if (!$attendance || !$attendance->check_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda harus check-in terlebih dahulu'
                    ], 400);
                }

                if ($attendance->check_out) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah check-out hari ini'
                    ], 400);
                }

                $attendance->update(['check_out' => Carbon::now()]);
                $message = 'Check-out berhasil! ✓ Terima kasih';
            }

            Log::info('Face attendance successful with auto-update', [
                'user_id' => $userId,
                'type' => $request->type,
                'similarity' => round($similarity * 100, 2),
                'embedding_updated' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => $message . "\n\n🔄 Data wajah diperbarui otomatis",
                'data' => [
                    'is_late' => $isLate,
                    'late_minutes' => $lateMinutes,
                    'similarity' => round($similarity * 100, 2) . '%',
                    'embedding_updated' => true,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in face attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.'
            ], 500);
        }
    }

    /**
     * AUTO-UPDATE: Update embedding dengan weighted average
     *
     * Formula: new_embedding = (old * 0.7) + (current * 0.3)
     * Ini membuat sistem "belajar" dari setiap verifikasi sukses
     */
    private function updateFaceEmbeddingAfterSuccess(
        $userDetail,
        $storedEmbedding,
        $currentEmbedding,
        $similarity
    ) {
        // Weight: 70% old, 30% new
        // Semakin tinggi similarity, semakin kecil update (lebih konservatif)
        $oldWeight = 0.7 + ($similarity - 0.8) * 0.5; // 0.7-0.8 range
        $newWeight = 1 - $oldWeight;

        $updatedEmbedding = [];

        for ($i = 0; $i < count($storedEmbedding); $i++) {
            $updatedEmbedding[$i] =
                ($storedEmbedding[$i] * $oldWeight) +
                ($currentEmbedding[$i] * $newWeight);
        }

        // Normalisasi kembali
        $normalizedUpdated = $this->normalizeEmbedding($updatedEmbedding);

        // Simpan
        $userDetail->face_embedding = json_encode($normalizedUpdated);
        $userDetail->face_last_updated_at = now();
        $userDetail->save();

        Log::info('Face embedding auto-updated', [
            'user_id' => $userDetail->kode_user,
            'old_weight' => $oldWeight,
            'new_weight' => $newWeight,
            'similarity' => $similarity
        ]);
    }

    /**
     * Helper: Hitung rata-rata dari multiple embeddings
     */
    private function calculateAverageEmbedding(array $embeddings)
    {
        $dimension = count($embeddings[0]);
        $avgEmbedding = array_fill(0, $dimension, 0.0);

        foreach ($embeddings as $embedding) {
            for ($i = 0; $i < $dimension; $i++) {
                $avgEmbedding[$i] += floatval($embedding[$i]);
            }
        }

        $count = count($embeddings);
        for ($i = 0; $i < $dimension; $i++) {
            $avgEmbedding[$i] /= $count;
        }

        return $avgEmbedding;
    }

    /**
     * Helper: Normalisasi L2
     */
    private function normalizeEmbedding(array $embedding)
    {
        $magnitude = 0.0;

        foreach ($embedding as $value) {
            $magnitude += $value * $value;
        }

        $magnitude = sqrt($magnitude);

        if ($magnitude == 0) {
            return $embedding;
        }

        $normalized = [];
        foreach ($embedding as $value) {
            $normalized[] = $value / $magnitude;
        }

        return $normalized;
    }

    /**
     * Helper: Hitung diversity score untuk validasi quality
     * Semakin tinggi = semakin bervariasi (bagus)
     */
    private function calculateEmbeddingDiversity(array $embeddings)
    {
        if (count($embeddings) < 2) {
            return 1.0;
        }

        $totalDistance = 0.0;
        $comparisons = 0;

        for ($i = 0; $i < count($embeddings); $i++) {
            for ($j = $i + 1; $j < count($embeddings); $j++) {
                $similarity = $this->calculateCosineSimilarity(
                    $embeddings[$i],
                    $embeddings[$j]
                );

                // Distance = 1 - similarity
                $totalDistance += (1 - $similarity);
                $comparisons++;
            }
        }

        return $comparisons > 0 ? $totalDistance / $comparisons : 0.0;
    }

    /**
     * Helper: Cosine Similarity
     */
    private function calculateCosineSimilarity($embedding1, $embedding2)
    {
        if (count($embedding1) !== count($embedding2)) {
            throw new \Exception('Dimensi embedding tidak cocok');
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
            return 0;
        }

        $similarity = $dotProduct / ($magnitude1 * $magnitude2);
        return max(0, min(1, $similarity));
    }

    /**
     * Helper: Calculate distance
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

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

    /**
     * Hapus data wajah user (reset ke awal)
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

            // Backup data lama untuk log (opsional)
            Log::info('Face data deletion requested', [
                'user_id' => $user_id,
                'registered_at' => $userDetail->face_registered_at,
                'last_updated_at' => $userDetail->face_last_updated_at,
                'verification_count' => $userDetail->face_verification_count ?? 0,
                'photos_used' => $userDetail->face_embedding_count ?? 0,
            ]);

            // Hapus semua data face
            $userDetail->face_embedding = null;
            $userDetail->face_embedding_count = null;
            $userDetail->face_registered_at = null;
            $userDetail->face_last_updated_at = null;
            $userDetail->face_verification_count = 0;
            $userDetail->save();

            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil dihapus. Anda perlu mendaftar ulang untuk menggunakan fitur absensi wajah.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting face data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data wajah: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check status enrollment wajah
     */
    public function checkFaceEnrollmentStatus($user_id)
    {
        try {
            $userDetail = UserDetail::where('kode_user', $user_id)->first();

            if (!$userDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan'
                ], 404);
            }

            $isEnrolled = !empty($userDetail->face_embedding);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_enrolled' => $isEnrolled,
                    'user_name' => $userDetail->fullname ?? 'Unknown',
                    'registered_at' => $userDetail->face_registered_at,
                    'last_updated_at' => $userDetail->face_last_updated_at,
                    'photos_used' => $userDetail->face_embedding_count ?? 0,
                    'total_verifications' => $userDetail->face_verification_count ?? 0,
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
     * Get attendance status (method yang sudah ada, ditambahkan info face)
     */
    public function getStatus(Request $request)
    {
        $userId = auth()->id();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

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

        // Tambahkan info face enrollment
        $userDetail = UserDetail::where('kode_user', $userId)->first();
        $faceInfo = null;

        if ($userDetail) {
            $faceInfo = [
                'is_enrolled' => !empty($userDetail->face_embedding),
                'last_updated' => $userDetail->face_last_updated_at,
                'photos_used' => $userDetail->face_embedding_count ?? 0,
            ];
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
                'status' => $attendance ? $attendance->status : null,
                'face_info' => $faceInfo, // Info tambahan
            ]
        ]);
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

        Log::info("Distance check: {$distance}m (max: {$maxDistance}m)");

        return $distance <= $maxDistance;
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


}
