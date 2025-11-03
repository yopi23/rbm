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
    // Lanjutan dari kode AttendanceController.php sebelumnya
    // public function scanFace(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|string',
    //         'photo' => 'required|string',
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'type' => 'required|in:check_in,check_out',
    //         'live_embedding' => 'required|json', // Real embedding dari Flutter
    //     ]);

    //     $userId = auth()->id();
    //     $today = Carbon::today();
    //     $isCheckIn = $request->input('type') === 'check_in';

    //     // Get user data
    //     $user = $this->getThisUser();

    //     // 1. CEK ENROLLMENT WAJAH
    //     if (empty($user->face_embedding)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Wajah belum terdaftar. Silakan lakukan pendaftaran wajah terlebih dahulu.'
    //         ], 403);
    //     }

    //     // 2. CEK LOKASI
    //     $ownerDetailId = $user->id_upline ?? $user->id;
    //     $owner = DB::table('user_details')
    //         ->where('id', $ownerDetailId)
    //         ->first(['default_lat', 'default_lon', 'allowed_radius_m']);

    //     if (!$owner || !$owner->default_lat || !$owner->default_lon) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Pengaturan lokasi kantor belum ditentukan oleh Admin/Owner.'
    //         ], 400);
    //     }

    //     $officeLatitude = $owner->default_lat;
    //     $officeLongitude = $owner->default_lon;
    //     $allowedRadiusMeters = $owner->allowed_radius_m ?? 50;

    //     $distance = $this->calculateDistance(
    //         $request->latitude,
    //         $request->longitude,
    //         $officeLatitude,
    //         $officeLongitude
    //     );

    //     if ($distance > $allowedRadiusMeters) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Lokasi absensi terlalu jauh. Harus di dalam radius {$allowedRadiusMeters} meter dari kantor. Jarak Anda: " . round($distance, 2) . " meter."
    //         ], 400);
    //     }

    //     // 3. VERIFIKASI WAJAH (REAL)
    //     $liveEmbedding = json_decode($request->live_embedding, true);
    //     $storedEmbedding = json_decode($user->face_embedding, true);

    //     if (empty($storedEmbedding) || empty($liveEmbedding)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Data embedding wajah tidak valid.'
    //         ], 400);
    //     }

    //     // Hitung Cosine Similarity
    //     $similarity = $this->calculateCosineSimilarity($liveEmbedding, $storedEmbedding);

    //     // Threshold: 0.7 untuk MobileFaceNet (lebih rendah dari FaceNet512)
    //     // Sesuaikan berdasarkan testing Anda
    //     $requiredThreshold = 0.70;

    //     if ($similarity < $requiredThreshold) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Verifikasi wajah gagal. Wajah tidak cocok dengan data yang terdaftar. (Similarity: ' . round($similarity, 3) . ')'
    //         ], 400);
    //     }

    //     // 4. LOGIKA ABSENSI
    //     $attendance = Attendance::where('user_id', $userId)
    //         ->whereDate('attendance_date', $today)
    //         ->first();

    //     if ($isCheckIn) {
    //         // Check-in logic
    //         if ($attendance && $attendance->check_in) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Anda sudah check-in hari ini.'
    //             ], 400);
    //         }

    //         if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'cuti'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Gagal check-in. Status Anda hari ini adalah ' . $attendance->status . '.'
    //             ], 400);
    //         }

    //         // Calculate late minutes
    //         $schedule = WorkSchedule::where('user_id', $userId)
    //             ->where('day_of_week', $today->format('l'))
    //             ->first();

    //         $lateMinutes = 0;
    //         if ($schedule && $schedule->is_working_day) {
    //             $checkInTime = Carbon::now();
    //             $scheduleStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . $schedule->start_time);
    //             if ($checkInTime->gt($scheduleStartTime)) {
    //                 $lateMinutes = $checkInTime->diffInMinutes($scheduleStartTime);
    //             }
    //         }

    //         // Save check-in photo (optional)
    //         $photoPath = null;
    //         if ($request->photo !== 'face_recognition') {
    //             $photoPath = $this->saveBase64Image($request->photo, 'attendance/checkin');
    //         }

    //         $attendance = Attendance::updateOrCreate(
    //             ['user_id' => $userId, 'attendance_date' => $today],
    //             [
    //                 'check_in' => Carbon::now(),
    //                 'status' => 'hadir',
    //                 'location' => "Face Scan | Lat:{$request->latitude}, Lon:{$request->longitude}",
    //                 'late_minutes' => $lateMinutes,
    //                 'check_in_photo' => $photoPath,
    //                 'created_by' => $userId,
    //             ]
    //         );

    //         // Create violation if late > 30 minutes
    //         if ($lateMinutes > 30) {
    //             Violation::create([
    //                 'user_id' => $userId,
    //                 'violation_date' => $today,
    //                 'type' => 'telat',
    //                 'description' => "Terlambat $lateMinutes menit (Absensi Wajah)",
    //                 'penalty_percentage' => 5,
    //                 'status' => 'pending',
    //                 'created_by' => $userId,
    //             ]);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Check-in wajah berhasil! Verifikasi selesai. (Similarity: ' . round($similarity, 3) . ')',
    //             'data' => [
    //                 'attendance' => $attendance,
    //                 'is_late' => $lateMinutes > 0,
    //                 'late_minutes' => $lateMinutes,
    //                 'face_similarity' => round($similarity, 3)
    //             ]
    //         ]);

    //     } else {
    //         // Check-out logic
    //         if (!$attendance || !$attendance->check_in) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Anda belum check-in hari ini.'
    //             ], 400);
    //         }

    //         if ($attendance->check_out) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Anda sudah check-out hari ini.'
    //             ], 400);
    //         }

    //         // Save check-out photo (optional)
    //         $photoPath = null;
    //         if ($request->photo !== 'face_recognition') {
    //             $photoPath = $this->saveBase64Image($request->photo, 'attendance/checkout');
    //         }

    //         $attendance->update([
    //             'check_out' => Carbon::now(),
    //             'location' => "Face Scan | Lat:{$request->latitude}, Lon:{$request->longitude}",
    //             'check_out_photo' => $photoPath,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Check-out wajah berhasil! (Similarity: ' . round($similarity, 3) . ')',
    //             'data' => [
    //                 'attendance' => $attendance,
    //                 'face_similarity' => round($similarity, 3)
    //             ]
    //         ]);
    //     }
    // }

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

    // REGISTER FACE - REAL IMPLEMENTATION
    public function registerFace(Request $request)
{
    try {
        $request->validate([
            'user_id' => 'required',
            'face_embedding' => 'required|string',
        ]);

        // Cari atau buat record di user_details
        $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        // Simpan face embedding
        $userDetail->face_embedding = $request->face_embedding;
        $userDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Wajah berhasil didaftarkan! Sekarang Anda dapat menggunakan fitur absensi wajah.',
            'data' => [
                'user_name' => $userDetail->fullname,
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mendaftarkan wajah: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Scan face untuk absensi (check-in/check-out)
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

        // Ambil data user dari user_details
        $userDetail = UserDetail::where('kode_user', $userId)->first();

        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        // Cek apakah user sudah mendaftar wajah
        if (empty($userDetail->face_embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah terlebih dahulu.'
            ], 403);
        }

        // Decode embeddings
        $storedEmbedding = json_decode($userDetail->face_embedding, true);
        $liveEmbedding = json_decode($request->live_embedding, true);

        // Validasi format embedding
        if (!is_array($storedEmbedding) || !is_array($liveEmbedding)) {
            \Log::error('Invalid embedding format', [
                'stored_is_array' => is_array($storedEmbedding),
                'live_is_array' => is_array($liveEmbedding)
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Format data wajah tidak valid'
            ], 400);
        }

        // Hitung similarity (cosine similarity)
        $similarity = $this->calculateCosineSimilarity($storedEmbedding, $liveEmbedding);

        \Log::info('Face similarity calculated', [
            'user_id' => $userId,
            'similarity' => $similarity
        ]);

        // Threshold untuk verifikasi (0.5 = 50% similarity)
        $threshold = 0.90;

        if ($similarity < $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi wajah gagal. Wajah tidak cocok dengan data terdaftar. Silakan coba lagi atau hubungi admin.',
                'data' => [
                    'similarity' => round($similarity * 100, 2) . '%',
                    'threshold' => round($threshold * 100, 2) . '%'
                ]
            ], 401);
        }

        // Cek lokasi (jika is_outside_office = 0)
        if ($userDetail->is_outside_office == 0) {
            $validLocation = $this->checkLocation(
                $request->latitude,
                $request->longitude,
                $userDetail->default_lat,
                $userDetail->default_lon,
                $userDetail->allowed_radius_m ?? 100
            );

            if (!$validLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi Anda di luar jangkauan kantor. Jarak maksimal: ' .
                                ($userDetail->allowed_radius_m ?? 100) . ' meter'
                ], 400);
            }
        }

        // Cek jadwal kerja hari ini
        $today = Carbon::today();
        $dayName = $today->format('l'); // Nama hari (Monday, Tuesday, etc)

        $schedule = WorkSchedule::where('user_id', $userId)
            ->where('day_of_week', $dayName)
            ->first();

        if (!$schedule || !$schedule->is_working_day) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki jadwal kerja hari ini'
            ], 400);
        }

        // Cek keberadaan attendance hari ini
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

        $isLate = false;
        $lateMinutes = 0;

        if ($request->type === 'check_in') {
            // Logic Check-In
            if ($attendance && $attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in hari ini pada ' .
                                Carbon::parse($attendance->check_in)->format('H:i')
                ], 400);
            }

            $checkInTime = Carbon::now();

            // Parse waktu jadwal dengan benar
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

            if ($attendance) {
                $attendance->check_in = $checkInTime;
                $attendance->status = 'hadir';
                $attendance->is_late = $isLate;
                $attendance->late_minutes = $lateMinutes;
                $attendance->location = "Face Recognition | Lat:{$request->latitude}, Lon:{$request->longitude}";
                $attendance->save();
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
            }

            $message = $isLate
                ? "Check-in berhasil! Anda terlambat $lateMinutes menit"
                : 'Check-in berhasil! Selamat bekerja';

        } else {
            // Logic Check-Out
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

            $attendance->check_out = Carbon::now();
            $attendance->save();

            $message = 'Check-out berhasil! Terima kasih atas kerja keras Anda hari ini';
        }

        \Log::info('Face attendance successful', [
            'user_id' => $userId,
            'type' => $request->type,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'similarity' => $similarity
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
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat memproses absensi: ' . $e->getMessage()
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
        $dotProduct += $embedding1[$i] * $embedding2[$i];
        $magnitude1 += $embedding1[$i] * $embedding1[$i];
        $magnitude2 += $embedding2[$i] * $embedding2[$i];
    }

    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);

    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0;
    }

    return $dotProduct / ($magnitude1 * $magnitude2);
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
