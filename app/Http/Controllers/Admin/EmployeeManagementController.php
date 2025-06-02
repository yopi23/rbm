<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SalarySetting;
use App\Models\Violation;
use App\Models\EmployeeMonthlyReport;
use App\Models\OutsideOfficeLog;
use App\Models\WorkSchedule;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\QrCodeAttendance;
use App\Models\Sevices as modelServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class EmployeeManagementController extends Controller
{
    public function scanEmployeeQrCode(Request $request)
    {
            // Log request untuk debugging
    Log::info('QR Scan Request:', [
        'qr_data' => $request->qr_data,
        'admin_id' => auth()->id(),
        'timestamp' => now()
    ]);
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        // Validasi admin yang melakukan scan
        $adminId = auth()->id();
        $admin = User::find($adminId);
        $adminDetail = UserDetail::where('kode_user', $adminId)->first();

        if (!$adminDetail || !in_array($adminDetail->jabatan, [1,0])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang dapat melakukan scan'
            ], 403);
        }

        try {
            $qrData = json_decode($request->qr_data, true);

            if (!$qrData || !isset($qrData['user_id']) || $qrData['type'] !== 'employee_identification') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code tidak valid atau format salah'
                ], 400);
            }

            // Validasi tanggal QR (harus hari ini)
            $qrDate = $qrData['date'] ?? null;
            $today = Carbon::now()->format('Y-m-d');

            if ($qrDate !== $today) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code sudah expired. QR hanya berlaku untuk hari ini.'
                ], 400);
            }

            // Validasi freshness QR jika ada generated_at (max 10 menit untuk fleksibilitas)
            if (isset($qrData['generated_at'])) {
                $generatedAt = Carbon::parse($qrData['generated_at']);
                if ($generatedAt->diffInMinutes(Carbon::now()) > 10) {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR Code sudah kadaluarsa, minta karyawan generate ulang'
                    ], 400);
                }
            }

            $userId = $qrData['user_id'];
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan'
                ], 404);
            }

            // Cek apakah user adalah teknisi
            $userDetail = UserDetail::where('kode_user', $userId)->first();
            if (!$userDetail || $userDetail->jabatan != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya teknisi yang dapat menggunakan QR absensi'
                ], 403);
            }

            $todayDate = Carbon::today();

            // Check attendance record untuk hari ini
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $todayDate)
                ->first();

            // Tentukan aksi berdasarkan status attendance saat ini
            if (!$attendance || !$attendance->check_in) {
                // CASE: Check-in (belum ada attendance atau belum check-in)

                // Get work schedule untuk calculate late
                $schedule = WorkSchedule::where('user_id', $userId)
                    ->where('day_of_week', $todayDate->format('l'))
                    ->first();

                // Calculate late minutes
                $lateMinutes = 0;
                if ($schedule && $schedule->is_working_day) {
                    $checkInTime = Carbon::now();
                    $scheduleStartTime = Carbon::parse($schedule->start_time);

                    if ($checkInTime->gt($scheduleStartTime)) {
                        $lateMinutes = $checkInTime->diffInMinutes($scheduleStartTime);
                    }
                }

                // Create or update attendance record
                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'attendance_date' => $todayDate,
                    ],
                    [
                        'check_in' => Carbon::now(),
                        'status' => 'hadir',
                        'location' => 'Scanned by Admin: ' . $admin->name,
                        'late_minutes' => $lateMinutes,
                        'created_by' => $adminId,
                    ]
                );

                // Create violation jika terlambat lebih dari 30 menit
                if ($lateMinutes > 30) {
                    Violation::create([
                        'user_id' => $userId,
                        'violation_date' => $todayDate,
                        'type' => 'telat',
                        'description' => "Terlambat $lateMinutes menit",
                        'penalty_percentage' => 5,
                        'status' => 'pending',
                        'created_by' => $adminId,
                    ]);
                }

                $message = "Check-in berhasil untuk {$user->name}";
                if ($lateMinutes > 0) {
                    $message .= " (Terlambat $lateMinutes menit)";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'action' => 'check_in',
                    'time' => Carbon::now()->format('H:i'),
                    'late_minutes' => $lateMinutes,
                    'employee_name' => $user->name,
                    'attendance_data' => $attendance
                ]);

            } else if ($attendance->check_in && !$attendance->check_out) {
                // CASE: Check-out (sudah check-in tapi belum check-out)

                $attendance->update([
                    'check_out' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Check-out berhasil untuk {$user->name}",
                    'action' => 'check_out',
                    'time' => Carbon::now()->format('H:i'),
                    'employee_name' => $user->name,
                    'attendance_data' => $attendance
                ]);

            } else {
                // CASE: Sudah complete (check-in dan check-out)
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan sudah menyelesaikan absensi hari ini'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error scanning employee QR: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR Code untuk karyawan (API only) - BACKUP METHOD
     * Tetap ada sebagai fallback jika frontend generation gagal
     */
    public function generateEmployeeQrCode(Request $request)
    {
        // Validasi user dari request (untuk API)
        $userId = $request->user_id ?? auth()->id();
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Cek apakah user adalah teknisi
        $userDetail = UserDetail::where('kode_user', $userId)->first();
        if (!$userDetail || $userDetail->jabatan != 3) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya teknisi yang dapat menggunakan fitur ini'
            ], 403);
        }

        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        // Buat unique token untuk karyawan ini
        $token = hash('sha256', $today . $userId . 'employee_qr' . time());

        // QR Code untuk karyawan berisi info mereka (sama format dengan frontend)
        $qrData = [
            'user_id' => $userId,
            'name' => $user->name,
            'date' => $today,
            'token' => $token,
            'type' => 'employee_identification',
            'generated_at' => $now->toISOString()
        ];

        // Return JSON data saja (tidak perlu generate image di backend)
        return response()->json([
            'success' => true,
            'qr_data' => json_encode($qrData),
            'employee_data' => [
                'name' => $user->name,
                'id' => $userId,
                'date' => $today
            ],
            'message' => 'QR data berhasil dibuat'
        ]);
    }

    // ===================================================================
    // WEB ATTENDANCE FUNCTIONS - MANUAL ADMIN ONLY
    // ===================================================================

    /**
     * Attendance Management Index
     * Halaman utama untuk admin kelola attendance
     */
    public function attendanceIndex()
    {
        $page = "Absensi Karyawan";
        $today = Carbon::today();

        $attendances = Attendance::with(['user', 'user.userDetail'])
            ->whereDate('attendance_date', $today)
            ->where('user_id', '!=', auth()->id())
            ->get();

        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3]) // Kasir dan Teknisi
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        $schedules = WorkSchedule::where('day_of_week', $today->format('l'))->get();

        $content = view('admin.page.attendance', compact('page', 'attendances', 'employees', 'schedules'))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Manual Check-In oleh Admin (Web Only)
     */
    public function attendanceCheckIn(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'photo' => 'nullable|image|max:2048', // Photo optional untuk manual
            'location' => 'required|string',
        ]);

        $today = Carbon::today();
        $schedule = WorkSchedule::where('user_id', $request->user_id)
            ->where('day_of_week', $today->format('l'))
            ->first();

        if (!$schedule || !$schedule->is_working_day) {
            return redirect()->back()->with('error', 'Tidak ada jadwal kerja hari ini');
        }

        // Check if already checked in
        $existingAttendance = Attendance::where('user_id', $request->user_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            return redirect()->back()->with('error', 'Sudah melakukan check-in hari ini');
        }

        // Store photo if provided
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        // Calculate late minutes
        $checkInTime = Carbon::now();
        $scheduleStartTime = Carbon::parse( $schedule->start_time);
        $lateMinutes = 0;

        if ($checkInTime->gt($scheduleStartTime)) {
            $lateMinutes = $checkInTime->diffInMinutes($scheduleStartTime);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'attendance_date' => $today,
            ],
            [
                'check_in' => $checkInTime,
                'status' => 'hadir',
                'photo_in' => $photoPath,
                'location' => $request->location,
                'late_minutes' => $lateMinutes,
                'created_by' => auth()->id(),
            ]
        );

        // Create violation if late more than 30 minutes
        if ($lateMinutes > 30) {
            Violation::create([
                'user_id' => $request->user_id,
                'violation_date' => $today,
                'type' => 'telat',
                'description' => "Terlambat $lateMinutes menit (Manual entry)",
                'penalty_percentage' => 5,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Check-in berhasil');
    }

    /**
     * Manual Check-Out oleh Admin (Web Only)
     */
    public function attendanceCheckOut(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'photo' => 'nullable|image|max:2048',
            'location' => 'required|string',
        ]);

        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $request->user_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return redirect()->back()->with('error', 'Belum melakukan check-in');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'Sudah melakukan check-out');
        }

        // Store photo if provided
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance-photos', 'public');
        }

        $attendance->update([
            'check_out' => Carbon::now(),
            'photo_out' => $photoPath,
        ]);

        return redirect()->back()->with('success', 'Check-out berhasil');
    }

    /**
     * Request Leave (Web & API)
     */
    public function requestLeave(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:izin,sakit,cuti',
            'note' => 'required|string',
        ]);

        $date = Carbon::parse($request->date);

        // Create attendance with status leave
        Attendance::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'attendance_date' => $date,
            ],
            [
                'status' => $request->type,
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permintaan izin berhasil dibuat'
            ]);
        }

        return redirect()->back()->with('success', 'Permintaan izin berhasil dibuat');
    }

    // ===================================================================
    // SALARY SETTINGS MANAGEMENT
    // ===================================================================

    public function salarySettingsIndex()
    {
        $page = "Pengaturan Kompensasi";

        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        $salarySettings = SalarySetting::with('user')->get();

        $content = view('admin.page.salary-settings', compact('employees', 'salarySettings'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function salarySettingsStore(Request $request)
    {
        // Debug: Log semua data yang diterima
        \Log::info('Salary Settings Store - Request Data:', $request->all());

        // Validasi berdasarkan compensation_type
        $rules = [
            'user_id' => 'required|exists:users,id',
            'compensation_type' => 'required|in:fixed,percentage',
        ];

        // Tambahkan validasi kondisional berdasarkan tipe kompensasi
        if ($request->compensation_type === 'fixed') {
            $rules['basic_salary'] = 'required|numeric|min:0';
            $rules['service_percentage'] = 'required|integer|min:0|max:100';
            $rules['target_bonus'] = 'required|numeric|min:0';
            $rules['monthly_target'] = 'required|integer|min:0';
        } elseif ($request->compensation_type === 'percentage') {
            $rules['percentage_value'] = 'required|numeric|min:0|max:100';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $data = [
                'user_id' => $request->user_id,
                'compensation_type' => $request->compensation_type,
                'created_by' => auth()->id(),
                'updated_at' => now(),
            ];

            if ($request->compensation_type == 'fixed') {
                $data['basic_salary'] = (float) $request->basic_salary;
                $data['service_percentage'] = (int) $request->service_percentage;
                $data['target_bonus'] = (float) $request->target_bonus;
                $data['monthly_target'] = (int) $request->monthly_target;
                $data['percentage_value'] = 0;
            } else {
                // Untuk tipe percentage, set field fixed salary ke 0
                $data['percentage_value'] = (float) $request->percentage_value;
                $data['basic_salary'] = 0;
                $data['service_percentage'] = 0;
                $data['target_bonus'] = 0;
                $data['monthly_target'] = 0;
            }

            // Debug: Log data yang akan disimpan
            \Log::info('Salary Settings Store - Data to be saved:', $data);

            $salarySetting = SalarySetting::updateOrCreate(
                ['user_id' => $request->user_id],
                $data
            );

            // Debug: Log hasil penyimpanan
            \Log::info('Salary Settings Store - Saved Record:', $salarySetting->toArray());

            DB::commit();

            // Return JSON response untuk AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengaturan kompensasi berhasil disimpan',
                    'data' => $salarySetting
                ]);
            }

            return redirect()->back()->with('success', 'Pengaturan kompensasi berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();

            // Debug: Log error
            \Log::error('Salary Settings Store Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan pengaturan: '.$e->getMessage());
        }
    }

    // ===================================================================
    // VIOLATIONS MANAGEMENT
    // ===================================================================

    public function violationsIndex()
    {
        $page = "Pelanggaran";

        $violations = Violation::with(['user', 'createdBy'])
            ->orderBy('violation_date', 'desc')
            ->get();

        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        $content= view('admin.page.violations', compact('violations', 'employees'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function violationsStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'violation_date' => 'required|date',
            'type' => 'required|in:telat,alpha,kelalaian,komplain,lainnya',
            'description' => 'required|string',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        Violation::create([
            'user_id' => $request->user_id,
            'violation_date' => $request->violation_date,
            'type' => $request->type,
            'description' => $request->description,
            'penalty_amount' => $request->penalty_amount,
            'penalty_percentage' => $request->penalty_percentage,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Pelanggaran berhasil dicatat');
    }

    public function violationsUpdateStatus(Request $request)
    {
        $request->validate([
            'violation_id' => 'required|exists:violations,id',
            'status' => 'required|in:processed,forgiven',
        ]);

        $violation = Violation::findOrFail($request->violation_id);
        $violation->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    // ===================================================================
    // MONTHLY REPORT GENERATION
    // ===================================================================

    public function generateMonthlyReport($year, $month)
    {
        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        foreach ($employees as $employee) {
            $this->generateEmployeeMonthlyReport($employee->id_user, $year, $month);
        }

        return redirect()->back()->with('success', 'Laporan bulanan berhasil digenerate');
    }

    private function generateEmployeeMonthlyReport($userId, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $salarySetting = SalarySetting::where('user_id', $userId)->first();
        if (!$salarySetting) {
            \Log::warning("Tidak ada pengaturan kompensasi untuk user: $userId");
            return;
        }

        // Hitung hari kerja berdasarkan schedule yang aktual
        $workingDaysCount = WorkSchedule::where('user_id', $userId)
            ->where('is_working_day', true)
            ->count();

        // Hitung total hari kerja dalam bulan
        $daysInMonth = $endDate->day;
        $totalWorkingDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dayOfWeek = $currentDate->format('l');

            $hasSchedule = WorkSchedule::where('user_id', $userId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->exists();

            if ($hasSchedule) {
                $totalWorkingDays++;
            }
        }

        // Hitung kehadiran
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $totalPresentDays = $attendances->where('status', 'hadir')->count();
        $totalAbsentDays = $totalWorkingDays - $totalPresentDays;
        $totalLateMinutes = $attendances->sum('late_minutes');

        // Hitung service
        $services = modelServices::where('id_teknisi', $userId)
            ->where('status_services', 'Selesai')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->get();

        $totalServiceUnits = $services->count();
        $totalServiceAmount = $services->sum('total_biaya');
        $totalPartCost = $services->sum('harga_sp');

        // === HITUNG KOMPENSASI BERDASARKAN TIPE ===
        $totalCommission = 0;
        $totalBonus = 0;
        $basicSalary = 0;

        if ($salarySetting->compensation_type == 'fixed') {
            // Sistem gaji tetap
            $basicSalary = $salarySetting->basic_salary;

            // Potong gaji berdasarkan absent days
            $attendanceRate = $totalWorkingDays > 0 ? ($totalPresentDays / $totalWorkingDays) : 1;
            $basicSalary = $basicSalary * $attendanceRate;

            // Hitung komisi dari service
            $totalCommission = ($totalServiceAmount * $salarySetting->service_percentage) / 100;

            // Bonus jika mencapai target
            if ($salarySetting->monthly_target > 0 && $totalServiceUnits >= $salarySetting->monthly_target) {
                $totalBonus = $salarySetting->target_bonus;
            }
        } else {
            // Sistem persentase profit
            $profit = $totalServiceAmount - $totalPartCost;
            $totalCommission = ($profit * $salarySetting->percentage_value) / 100;

            // Kurangi komisi berdasarkan kehadiran untuk sistem persentase
            $attendanceRate = $totalWorkingDays > 0 ? ($totalPresentDays / $totalWorkingDays) : 1;
            $totalCommission = $totalCommission * $attendanceRate;
        }

        // === HITUNG PENALTIES DARI VIOLATIONS BIASA ===
        $violations = Violation::where('user_id', $userId)
            ->where('status', 'processed')
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->get();

        $totalPenalties = $violations->sum('penalty_amount');

        // Tambahkan penalty dari persentase violations biasa
        foreach ($violations as $violation) {
            if ($violation->penalty_percentage) {
                $penaltyAmount = ($totalCommission * $violation->penalty_percentage) / 100;
                $totalPenalties += $penaltyAmount;
            }
        }

        // === HITUNG PELANGGARAN IZIN KELUAR KANTOR ===
        $outsideOfficeViolations = OutsideOfficeLog::where('user_id', $userId)
            ->where('status', 'violated')
            ->whereYear('log_date', $year)
            ->whereMonth('log_date', $month)
            ->get();

        $outsideOfficePenalties = 0;
        $outsideOfficeSummary = [];

        foreach ($outsideOfficeViolations as $violation) {
            if ($violation->late_return_minutes > 15) {
                // Penalty berdasarkan keterlambatan
                $penaltyPercentage = $this->calculateOutsideOfficePenalty($violation->late_return_minutes);
                $penaltyAmount = ($totalCommission * $penaltyPercentage) / 100;
                $outsideOfficePenalties += $penaltyAmount;

                // Simpan detail untuk summary
                $outsideOfficeSummary[] = [
                    'date' => $violation->log_date->format('d/m/Y'),
                    'late_minutes' => $violation->late_return_minutes,
                    'penalty_percentage' => $penaltyPercentage,
                    'penalty_amount' => $penaltyAmount,
                    'reason' => $violation->reason
                ];
            }
        }

        // Tambahkan ke total penalties
        $totalPenalties += $outsideOfficePenalties;

        // === HITUNG GAJI FINAL ===
        $finalSalary = $basicSalary + $totalCommission + $totalBonus - $totalPenalties;

        // === SIMPAN LAPORAN ===
        EmployeeMonthlyReport::updateOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'compensation_type' => $salarySetting->compensation_type,
                'basic_salary' => $basicSalary,
                'total_service_units' => $totalServiceUnits,
                'total_service_amount' => $totalServiceAmount,
                'total_part_cost' => $totalPartCost,
                'total_commission' => $totalCommission,
                'total_bonus' => $totalBonus,
                'total_penalties' => $totalPenalties,
                'final_salary' => $finalSalary,
                'total_working_days' => $totalWorkingDays,
                'total_present_days' => $totalPresentDays,
                'total_absent_days' => $totalAbsentDays,
                'total_late_minutes' => $totalLateMinutes,
                'outside_office_violations' => $outsideOfficeViolations->count(),
                'outside_office_penalties' => $outsideOfficePenalties,
                'outside_office_summary' => json_encode($outsideOfficeSummary),
                'status' => 'draft',
                'processed_by' => auth()->id(),
                'percentage_used' => $salarySetting->compensation_type == 'percentage'
                                    ? $salarySetting->percentage_value
                                    : $salarySetting->service_percentage,
            ]
        );
    }

    /**
     * Helper method untuk calculate outside office penalty percentage
     */
    private function calculateOutsideOfficePenalty(int $lateMinutes): int
    {
        // Penalty bertingkat berdasarkan keterlambatan kembali
        if ($lateMinutes <= 30) return 2;      // 2% untuk 15-30 menit
        if ($lateMinutes <= 60) return 5;      // 5% untuk 30-60 menit
        if ($lateMinutes <= 120) return 10;    // 10% untuk 1-2 jam
        return 15;                             // 15% untuk lebih dari 2 jam
    }

    // ===================================================================
    // OUTSIDE OFFICE MANAGEMENT
    // ===================================================================

    public function setOutsideOffice(Request $request)
{
    // Log request awal
    Log::info('=== SET OUTSIDE OFFICE REQUEST START ===', [
        'request_data' => $request->all(),
        'user_agent' => $request->userAgent(),
        'ip_address' => $request->ip(),
        'auth_user_id' => auth()->id(),
        'timestamp' => now()
    ]);

    try {
        // Validasi request
        Log::info('Starting validation for outside office request');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration_hours' => 'nullable|integer|min:1|max:8', // Maksimal 8 jam
        ]);

        Log::info('Validation passed for outside office request');

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed for outside office request', [
            'errors' => $e->errors(),
            'request_data' => $request->all()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        }

        return redirect()->back()->withErrors($e->errors())->withInput();
    }

    try {
        Log::info('Starting database transaction for outside office');
        DB::beginTransaction();

        // Parse tanggal dan waktu
        Log::info('Parsing start and end time', [
            'start_time_raw' => $request->start_time,
            'end_time_raw' => $request->end_time
        ]);

        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        $logDate = $startTime->toDateString();

        Log::info('Parsed times successfully', [
            'start_time_parsed' => $startTime->toDateTimeString(),
            'end_time_parsed' => $endTime->toDateTimeString(),
            'log_date' => $logDate,
            'duration_hours' => $endTime->diffInHours($startTime)
        ]);

        // Validasi durasi maksimal (8 jam)
        $durationHours = $endTime->diffInHours($startTime);
        if ($durationHours > 8) {
            Log::warning('Duration exceeds maximum limit', [
                'duration_hours' => $durationHours,
                'max_allowed' => 8
            ]);

            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Durasi izin keluar maksimal 8 jam'
                ], 400);
            }

            return redirect()->back()->with('error', 'Durasi izin keluar maksimal 8 jam');
        }

        // Cek apakah sudah ada izin aktif hari ini
        Log::info('Checking for existing active outside office log', [
            'user_id' => $request->user_id,
            'log_date' => $logDate
        ]);

        $existingLog = OutsideOfficeLog::where('user_id', $request->user_id)
            ->whereDate('log_date', $logDate)
            ->where('status', 'active')
            ->first();

        if ($existingLog) {
            Log::warning('User already has active outside office log today', [
                'user_id' => $request->user_id,
                'existing_log_id' => $existingLog->id,
                'existing_log_data' => $existingLog->toArray()
            ]);

            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan sudah memiliki izin keluar aktif hari ini'
                ], 400);
            }

            return redirect()->back()->with('error', 'Karyawan sudah memiliki izin keluar aktif hari ini');
        }

        Log::info('No existing active log found, proceeding to create new log');

        // Buat log izin keluar
        $logData = [
            'user_id' => $request->user_id,
            'log_date' => $logDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => $request->reason,
            'status' => 'active',
            'approval_status' => 'approved', // Auto approve untuk admin
            'approved_by' => auth()->id(),
            'created_by' => auth()->id()
        ];

        Log::info('Creating outside office log with data', [
            'log_data' => $logData
        ]);

        $outsideLog = OutsideOfficeLog::create($logData);

        Log::info('Outside office log created successfully', [
            'log_id' => $outsideLog->id,
            'created_log_data' => $outsideLog->toArray()
        ]);

        // Update status di user_details untuk tracking real-time
        Log::info('Updating user details for real-time tracking', [
            'user_id' => $request->user_id
        ]);

        $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

        if (!$userDetail) {
            Log::warning('User detail not found', [
                'user_id' => $request->user_id
            ]);
        } else {
            Log::info('Found user detail, updating status', [
                'user_detail_id' => $userDetail->id,
                'current_status' => [
                    'is_outside_office' => $userDetail->is_outside_office,
                    'outside_note' => $userDetail->outside_note,
                    'current_outside_log_id' => $userDetail->current_outside_log_id ?? null
                ]
            ]);

            $userDetail->update([
                'is_outside_office' => true,
                'outside_note' => $request->reason,
                'current_outside_log_id' => $outsideLog->id
            ]);

            Log::info('User detail updated successfully', [
                'updated_status' => [
                    'is_outside_office' => true,
                    'outside_note' => $request->reason,
                    'current_outside_log_id' => $outsideLog->id
                ]
            ]);
        }

        Log::info('Committing database transaction');
        DB::commit();

        Log::info('=== SET OUTSIDE OFFICE SUCCESS ===', [
            'log_id' => $outsideLog->id,
            'user_id' => $request->user_id,
            'duration_hours' => $durationHours
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Izin keluar berhasil dicatat',
                'data' => $outsideLog
            ]);
        }

        return redirect()->back()->with('success', 'Izin keluar berhasil dicatat');

    } catch (\Exception $e) {
        Log::error('=== SET OUTSIDE OFFICE ERROR ===', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'auth_user_id' => auth()->id()
        ]);

        DB::rollBack();
        Log::info('Database transaction rolled back due to error');

        $errorMessage = 'Gagal mencatat izin keluar: ' . $e->getMessage();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }

        return redirect()->back()->with('error', $errorMessage);
    }
}

/**
 * Mark Return from Outside Office
 */
public function markReturnFromOutside(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'actual_return_time' => 'nullable|date',
        'return_note' => 'nullable|string'
    ]);

    try {
        DB::beginTransaction();

        $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

        if (!$userDetail || !$userDetail->is_outside_office) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak sedang dalam status keluar kantor'
            ], 400);
        }

        // Cari log aktif
        $activeLog = OutsideOfficeLog::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$activeLog) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan log izin keluar yang aktif'
            ], 404);
        }

        // Mark as returned
        $activeLog->markAsReturned();

        // Update user_details
        $userDetail->update([
            'is_outside_office' => false,
            'outside_note' => null,
            'current_outside_log_id' => null
        ]);

        DB::commit();

        $message = 'Kembali dari izin keluar berhasil dicatat';
        if ($activeLog->late_return_minutes > 0) {
            $message .= " (Terlambat {$activeLog->late_return_minutes} menit)";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $activeLog->fresh()
            ]);
        }

        return redirect()->back()->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error marking return from outside: ' . $e->getMessage());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat kembali: ' . $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Gagal mencatat kembali: ' . $e->getMessage());
    }
}

/**
 * Reset Outside Office Status (Emergency)
 */
public function resetOutsideOffice($userId)
{
    try {
        DB::beginTransaction();

        $userDetail = UserDetail::where('kode_user', $userId)->first();

        if (!$userDetail) {
            return redirect()->back()->with('error', 'User detail tidak ditemukan');
        }

        // Reset semua log aktif
        OutsideOfficeLog::where('user_id', $userId)
            ->where('status', 'active')
            ->update([
                'status' => 'violated',
                'violation_note' => 'Reset oleh admin: ' . auth()->user()->name,
                'actual_return_time' => Carbon::now()
            ]);

        // Reset user detail
        $userDetail->update([
            'is_outside_office' => false,
            'outside_note' => null,
            'current_outside_log_id' => null
        ]);

        DB::commit();

        return redirect()->back()->with('success', 'Status keluar kantor berhasil direset');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error resetting outside office: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Gagal mereset status: ' . $e->getMessage());
    }
}

/**
 * Outside Office History Index
 */
public function outsideOfficeHistoryIndex(Request $request)
{
    $page = "Riwayat Izin Keluar Kantor";

    $selectedEmployee = $request->employee_id;
    $selectedMonth = $request->month ?? date('m');
    $selectedYear = $request->year ?? date('Y');
    $status = $request->status; // active, completed, violated

    // Get employees
    $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
        ->where('user_details.id_upline', $this->getThisUser()->id_upline)
        ->whereIn('user_details.jabatan', [2, 3])
        ->get(['users.*', 'user_details.*', 'users.id as id_user']);

    // Build query
    $logsQuery = OutsideOfficeLog::with(['user', 'approvedBy', 'createdBy']);

    if ($selectedEmployee) {
        $logsQuery->where('user_id', $selectedEmployee);
    } else {
        $employeeIds = $employees->pluck('id_user')->toArray();
        $logsQuery->whereIn('user_id', $employeeIds);
    }

    $logsQuery->whereYear('log_date', $selectedYear)
             ->whereMonth('log_date', $selectedMonth);

    if ($status) {
        $logsQuery->where('status', $status);
    }

    $outsideLogs = $logsQuery->orderBy('log_date', 'desc')
                            ->orderBy('start_time', 'desc')
                            ->paginate(50);

    // Get statistics
    $stats = $this->getOutsideOfficeStats($selectedEmployee, $selectedYear, $selectedMonth);

    $content = view('admin.page.outside-office-history', compact(
        'page', 'outsideLogs', 'employees', 'selectedEmployee',
        'selectedMonth', 'selectedYear', 'status', 'stats'
    ))->render();

    return view('admin.layout.blank_page', compact('page', 'content'));
}

private function getOutsideOfficeStats($employeeId, $year, $month)
{
    $query = OutsideOfficeLog::query();

    if ($employeeId) {
        $query->where('user_id', $employeeId);
    } else {
        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.id as id_user']);
        $employeeIds = $employees->pluck('id_user')->toArray();
        $query->whereIn('user_id', $employeeIds);
    }

    $query->whereYear('log_date', $year)
          ->whereMonth('log_date', $month);

    return [
        'total_requests' => $query->count(),
        'active_requests' => $query->where('status', 'active')->count(),
        'completed_requests' => $query->where('status', 'completed')->count(),
        'violated_requests' => $query->where('status', 'violated')->count(),
        'avg_duration_hours' => round($query->whereNotNull('end_time')->avg(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60')), 2),
        'total_late_minutes' => $query->sum('late_return_minutes')
    ];
}
/**
 * Outside Office Detail
 */
public function outsideOfficeDetail($id)
{
    try {
        $log = OutsideOfficeLog::with(['user', 'user.userDetail', 'approvedBy', 'createdBy'])->findOrFail($id);

        $html = view('admin.partials.outside-office-detail', compact('log'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching outside office detail: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal memuat detail: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Mark Return by Log ID
 */
public function markReturnByLog(Request $request)
{
    $request->validate([
        'log_id' => 'required|exists:outside_office_logs,id'
    ]);

    try {
        DB::beginTransaction();

        $log = OutsideOfficeLog::with('user')->findOrFail($request->log_id);

        if ($log->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Log ini sudah tidak aktif'
            ], 400);
        }

        // Calculate late return minutes
        $now = Carbon::now();
        $expectedReturnTime = Carbon::parse($log->end_time);
        $lateMinutes = 0;

        if ($now->gt($expectedReturnTime)) {
            $lateMinutes = $now->diffInMinutes($expectedReturnTime);
        }

        // Determine status based on late minutes
        $status = 'completed';
        if ($lateMinutes > 15) {
            $status = 'violated';
        }

        // Update log
        $log->update([
            'actual_return_time' => $now,
            'late_return_minutes' => $lateMinutes,
            'status' => $status,
            'updated_at' => $now
        ]);

        // Update user detail
        $userDetail = UserDetail::where('kode_user', $log->user_id)->first();
        if ($userDetail) {
            $userDetail->update([
                'is_outside_office' => false,
                'outside_note' => null,
                'current_outside_log_id' => null
            ]);
        }

        DB::commit();

        $message = "Kembali dari izin keluar berhasil dicatat untuk {$log->user->name}";
        if ($lateMinutes > 0) {
            $message .= " (Terlambat {$lateMinutes} menit)";
            if ($status === 'violated') {
                $message .= " - Ditandai sebagai pelanggaran";
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'log_id' => $log->id,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'actual_return_time' => $now->format('Y-m-d H:i:s')
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error marking return by log: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal mencatat kembali: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Violate Log (Reset as violation)
 */
public function violateLog(Request $request)
{
    $request->validate([
        'log_id' => 'required|exists:outside_office_logs,id',
        'reason' => 'required|string|min:5'
    ]);

    try {
        DB::beginTransaction();

        $log = OutsideOfficeLog::with('user')->findOrFail($request->log_id);

        if ($log->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Log ini sudah tidak aktif'
            ], 400);
        }

        // Calculate late minutes from expected return time
        $now = Carbon::now();
        $expectedReturnTime = Carbon::parse($log->end_time);
        $lateMinutes = $now->gt($expectedReturnTime) ? $now->diffInMinutes($expectedReturnTime) : 0;

        $log->update([
            'status' => 'violated',
            'violation_note' => "Reset oleh admin: {$request->reason}",
            'actual_return_time' => $now,
            'late_return_minutes' => $lateMinutes,
            'updated_at' => $now
        ]);

        // Update user detail
        $userDetail = UserDetail::where('kode_user', $log->user_id)->first();
        if ($userDetail) {
            $userDetail->update([
                'is_outside_office' => false,
                'outside_note' => null,
                'current_outside_log_id' => null
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Log {$log->user->name} berhasil direset sebagai pelanggaran",
            'data' => [
                'log_id' => $log->id,
                'status' => 'violated',
                'violation_note' => $log->violation_note
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error violating log: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal mereset log: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Export Outside Office History
 */
public function exportOutsideOfficeHistory(Request $request)
{
    $selectedEmployee = $request->employee_id;
    $selectedMonth = $request->month ?? date('m');
    $selectedYear = $request->year ?? date('Y');
    $status = $request->status;

    // Get employees for filter
    $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
        ->where('user_details.id_upline', $this->getThisUser()->id_upline)
        ->whereIn('user_details.jabatan', [2, 3])
        ->get(['users.*', 'user_details.*', 'users.id as id_user']);

    // Build query
    $logsQuery = OutsideOfficeLog::with(['user', 'user.userDetail']);

    if ($selectedEmployee) {
        $logsQuery->where('user_id', $selectedEmployee);
    } else {
        $employeeIds = $employees->pluck('id_user')->toArray();
        $logsQuery->whereIn('user_id', $employeeIds);
    }

    $logsQuery->whereYear('log_date', $selectedYear)
             ->whereMonth('log_date', $selectedMonth);

    if ($status) {
        $logsQuery->where('status', $status);
    }

    $outsideLogs = $logsQuery->orderBy('log_date', 'desc')
                            ->orderBy('start_time', 'desc')
                            ->get();

    // Generate filename
    $filename = 'riwayat_izin_keluar_' . $selectedYear . '_' . $selectedMonth;
    if ($selectedEmployee) {
        $employeeName = $employees->where('id_user', $selectedEmployee)->first()->name ?? 'karyawan';
        $filename .= '_' . str_replace(' ', '_', strtolower($employeeName));
    }
    $filename .= '.csv';

    // Create CSV content
    $csvContent = "Nama,Jabatan,Tanggal,Waktu Keluar,Waktu Kembali (Rencana),Waktu Kembali (Aktual),Durasi,Status,Keterlambatan (menit),Alasan,Catatan Pelanggaran\n";

    foreach ($outsideLogs as $log) {
        $jabatan = '';
        if ($log->user->userDetail) {
            $jabatan = $log->user->userDetail->jabatan == 2 ? 'Kasir' : 'Teknisi';
        }

        $duration = '';
        if ($log->actual_return_time) {
            $start = Carbon::parse($log->start_time);
            $end = Carbon::parse($log->actual_return_time);
            $duration = $start->diff($end)->format('%H:%I');
        } elseif ($log->end_time) {
            $start = Carbon::parse($log->start_time);
            $end = Carbon::parse($log->end_time);
            $duration = $start->diff($end)->format('%H:%I') . ' (rencana)';
        }

        $csvContent .= '"' . $log->user->name . '",';
        $csvContent .= '"' . $jabatan . '",';
        $csvContent .= '"' . Carbon::parse($log->log_date)->format('d/m/Y') . '",';
        $csvContent .= '"' . Carbon::parse($log->start_time)->format('H:i') . '",';
        $csvContent .= '"' . ($log->end_time ? Carbon::parse($log->end_time)->format('H:i') : '-') . '",';
        $csvContent .= '"' . ($log->actual_return_time ? Carbon::parse($log->actual_return_time)->format('H:i') : '-') . '",';
        $csvContent .= '"' . $duration . '",';
        $csvContent .= '"' . ucfirst($log->status) . '",';
        $csvContent .= '"' . ($log->late_return_minutes ?? 0) . '",';
        $csvContent .= '"' . str_replace('"', '""', $log->reason) . '",';
        $csvContent .= '"' . str_replace('"', '""', $log->violation_note ?? '') . '"';
        $csvContent .= "\n";
    }

    return response($csvContent)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
}

/**
 * Get This User Helper
 */
public function getThisUser()
{
    return UserDetail::where('kode_user', auth()->id())->first();
}
    // ===================================================================
    // MONTHLY REPORT VIEWS AND ACTIONS
    // ===================================================================

    public function monthlyReportIndex(Request $request)
    {
        $page = "Laporan Bulanan Karyawan";
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');

        $reports = EmployeeMonthlyReport::with(['user', 'processedBy'])
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $content = view('admin.page.monthly-report', compact('reports', 'year', 'month'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function finalizeReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:employee_monthly_reports,id',
        ]);

        $report = EmployeeMonthlyReport::findOrFail($request->report_id);
        $report->update(['status' => 'finalized']);

        return response()->json(['success' => true]);
    }

    public function markAsPaid(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:employee_monthly_reports,id',
        ]);

        $report = EmployeeMonthlyReport::findOrFail($request->report_id);
        $report->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        // Update saldo karyawan saat laporan di-mark as paid
        $userDetail = UserDetail::where('kode_user', $report->user_id)->first();
        if ($userDetail) {
            $userDetail->saldo += $report->final_salary;
            $userDetail->save();
        }

        return response()->json(['success' => true]);
    }

    public function reportDetail($id)
    {
        $page = "Detail Laporan Bulanan";
        $report = EmployeeMonthlyReport::with(['user', 'processedBy'])->findOrFail($id);

        // Get attendance data
        $attendances = Attendance::where('user_id', $report->user_id)
            ->whereYear('attendance_date', $report->year)
            ->whereMonth('attendance_date', $report->month)
            ->orderBy('attendance_date')
            ->get();

        // Get service data
        $services = modelServices::where('id_teknisi', $report->user_id)
            ->where('status_services', 'Selesai')
            ->whereYear('updated_at', $report->year)
            ->whereMonth('updated_at', $report->month)
            ->orderBy('updated_at')
            ->get();

        // Get violations
        $violations = Violation::where('user_id', $report->user_id)
            ->whereYear('violation_date', $report->year)
            ->whereMonth('violation_date', $report->month)
            ->orderBy('violation_date')
            ->get();

        $content = view('admin.page.report-detail', compact('report', 'attendances', 'services', 'violations'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function reportPrint($id)
    {
        $report = EmployeeMonthlyReport::with(['user', 'processedBy'])->findOrFail($id);

        // Get attendance data
        $attendances = Attendance::where('user_id', $report->user_id)
            ->whereYear('attendance_date', $report->year)
            ->whereMonth('attendance_date', $report->month)
            ->orderBy('attendance_date')
            ->get();

        // Get service data
        $services = modelServices::where('id_teknisi', $report->user_id)
            ->where('status_services', 'Selesai')
            ->whereYear('updated_at', $report->year)
            ->whereMonth('updated_at', $report->month)
            ->orderBy('updated_at')
            ->get();

        // Get violations
        $violations = Violation::where('user_id', $report->user_id)
            ->whereYear('violation_date', $report->year)
            ->whereMonth('violation_date', $report->month)
            ->orderBy('violation_date')
            ->get();

        return view('admin.page.report-print', compact('report', 'attendances', 'services', 'violations'));
    }

    // ===================================================================
    // WORK SCHEDULE MANAGEMENT
    // ===================================================================

    public function scheduleIndex()
    {
        $page = "Jadwal Kerja Karyawan";

        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        $schedules = WorkSchedule::with('user')->get();

        $content = view('admin.page.work-schedule', compact('employees', 'schedules'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function scheduleStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_working_day' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Delete existing schedules
            WorkSchedule::where('user_id', $request->user_id)->delete();

            // Create new schedules
            foreach ($request->schedules as $schedule) {
                WorkSchedule::create([
                    'user_id' => $request->user_id,
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'is_working_day' => $schedule['is_working_day'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Jadwal kerja berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }

    public function getUserSchedule($userId)
    {
        $schedules = WorkSchedule::where('user_id', $userId)->get();
        return response()->json(['schedules' => $schedules]);
    }

    // ===================================================================
    // API HELPER FUNCTIONS
    // ===================================================================

    /**
     * Get attendance history for mobile app
     */
    public function getAttendanceHistory(Request $request, $userId)
    {
        $limit = $request->limit ?? 30;

        $attendances = Attendance::where('user_id', $userId)
            ->orderBy('attendance_date', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Get user schedule for mobile app
     */
    public function getUserScheduleAPI($userId)
    {
        $schedules = WorkSchedule::where('user_id', $userId)->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get salary info for mobile app
     */
    public function getSalaryInfo($userId)
    {
        $setting = SalarySetting::where('user_id', $userId)->first();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $report = EmployeeMonthlyReport::where('user_id', $userId)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->first();

        return response()->json([
            'success' => true,
            'salary_setting' => $setting,
            'current_report' => $report
        ]);
    }

    /**
     * Get current attendance status for mobile app
     */
    public function getCurrentAttendanceStatus($userId)
    {
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $today)
            ->first();

        $schedule = WorkSchedule::where('user_id', $userId)
            ->where('day_of_week', $today->format('l'))
            ->first();

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
            'schedule' => $schedule,
            'can_check_in' => !$attendance || !$attendance->check_in,
            'can_check_out' => $attendance && $attendance->check_in && !$attendance->check_out
        ]);
    }

    /**
     * Attendance History Index - Riwayat Absensi Karyawan
     */
    public function attendanceHistoryIndex(Request $request)
    {
        $page = "Riwayat Absensi Karyawan";

        // Get filter parameters
        $selectedEmployee = $request->employee_id;
        $selectedMonth = $request->month ?? date('m');
        $selectedYear = $request->year ?? date('Y');
        $viewType = $request->view_type ?? 'monthly'; // daily atau monthly
        $selectedDate = $request->date ?? date('Y-m-d');

        // Get employees for filter
        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3]) // Kasir dan Teknisi
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        // Base query untuk attendance
        $attendanceQuery = Attendance::with(['user', 'user.userDetail']);

        // Apply filters
        if ($selectedEmployee) {
            $attendanceQuery->where('user_id', $selectedEmployee);
        } else {
            // Hanya tampilkan karyawan dari upline yang sama
            $employeeIds = $employees->pluck('id_user')->toArray();
            $attendanceQuery->whereIn('user_id', $employeeIds);
        }

        if ($viewType == 'daily') {
            // Filter untuk view harian
            $attendanceQuery->whereDate('attendance_date', $selectedDate);
        } else {
            // Filter untuk view bulanan
            $attendanceQuery->whereYear('attendance_date', $selectedYear)
                            ->whereMonth('attendance_date', $selectedMonth);
        }

        // Get attendances
        $attendances = $attendanceQuery->orderBy('attendance_date', 'desc')
                                    ->orderBy('user_id')
                                    ->paginate(50);

        // Get statistics untuk summary
        $stats = $this->getAttendanceStats($selectedEmployee, $selectedYear, $selectedMonth, $viewType, $selectedDate);

        $content = view('admin.page.attendance-history', compact(
            'page', 'attendances', 'employees', 'selectedEmployee',
            'selectedMonth', 'selectedYear', 'viewType', 'selectedDate', 'stats'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Get Attendance Statistics
     */
    private function getAttendanceStats($employeeId, $year, $month, $viewType, $date)
    {
        $query = Attendance::query();

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        } else {
            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->where('user_details.id_upline', $this->getThisUser()->id_upline)
                ->whereIn('user_details.jabatan', [2, 3])
                ->get(['users.id as id_user']);
            $employeeIds = $employees->pluck('id_user')->toArray();
            $query->whereIn('user_id', $employeeIds);
        }

        if ($viewType == 'daily') {
            $query->whereDate('attendance_date', $date);
        } else {
            $query->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month);
        }

        $totalAttendance = $query->count();
        $presentCount = $query->where('status', 'hadir')->count();
        $absentCount = $query->whereIn('status', ['alpha', 'izin', 'sakit', 'cuti'])->count();
        $lateCount = $query->where('late_minutes', '>', 0)->count();
        $avgLateMinutes = $query->where('late_minutes', '>', 0)->avg('late_minutes') ?? 0;

        return [
            'total_attendance' => $totalAttendance,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'avg_late_minutes' => round($avgLateMinutes, 2),
            'attendance_rate' => $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 2) : 0
        ];
    }

    /**
     * Export Attendance History to Excel
     */
    public function exportAttendanceHistory(Request $request)
    {
        $selectedEmployee = $request->employee_id;
        $selectedMonth = $request->month ?? date('m');
        $selectedYear = $request->year ?? date('Y');
        $viewType = $request->view_type ?? 'monthly';
        $selectedDate = $request->date ?? date('Y-m-d');

        // Get employees for filter
        $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $this->getThisUser()->id_upline)
            ->whereIn('user_details.jabatan', [2, 3])
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);

        // Base query untuk attendance
        $attendanceQuery = Attendance::with(['user', 'user.userDetail']);

        // Apply filters
        if ($selectedEmployee) {
            $attendanceQuery->where('user_id', $selectedEmployee);
        } else {
            $employeeIds = $employees->pluck('id_user')->toArray();
            $attendanceQuery->whereIn('user_id', $employeeIds);
        }

        if ($viewType == 'daily') {
            $attendanceQuery->whereDate('attendance_date', $selectedDate);
        } else {
            $attendanceQuery->whereYear('attendance_date', $selectedYear)
                            ->whereMonth('attendance_date', $selectedMonth);
        }

        $attendances = $attendanceQuery->orderBy('attendance_date', 'desc')
                                    ->orderBy('user_id')
                                    ->get();

        // Generate filename
        $filename = 'riwayat_absensi_' . ($viewType == 'daily' ? $selectedDate : $selectedYear . '_' . $selectedMonth) . '.csv';

        // Create CSV content
        $csvContent = "Nama,Tanggal,Check In,Check Out,Status,Keterlambatan (menit),Lokasi,Keterangan\n";

        foreach ($attendances as $attendance) {
            $csvContent .= '"' . $attendance->user->name . '",';
            $csvContent .= '"' . Carbon::parse($attendance->attendance_date)->format('d/m/Y') . '",';
            $csvContent .= '"' . ($attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '-') . '",';
            $csvContent .= '"' . ($attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '-') . '",';
            $csvContent .= '"' . ucfirst($attendance->status) . '",';
            $csvContent .= '"' . ($attendance->late_minutes ?? 0) . '",';
            $csvContent .= '"' . ($attendance->location ?? '-') . '",';
            $csvContent .= '"' . ($attendance->note ?? '-') . '"';
            $csvContent .= "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Attendance Detail - Detail record absensi tertentu
     */
    public function attendanceDetail($id)
    {
        $page = "Detail Absensi";

        $attendance = Attendance::with(['user', 'user.userDetail'])
            ->findOrFail($id);

        // Get work schedule for that day
        $schedule = WorkSchedule::where('user_id', $attendance->user_id)
            ->where('day_of_week', Carbon::parse($attendance->attendance_date)->format('l'))
            ->first();

        // Get violations for that day
        $violations = Violation::where('user_id', $attendance->user_id)
            ->whereDate('violation_date', $attendance->attendance_date)
            ->get();

        // Calculate work duration if both check in and check out exist
        $workDuration = null;
        if ($attendance->check_in && $attendance->check_out) {
            $checkIn = Carbon::parse($attendance->check_in);
            $checkOut = Carbon::parse($attendance->check_out);
            $workDuration = $checkIn->diff($checkOut);
        }

        // Get previous and next attendance for navigation
        $previousAttendance = Attendance::where('user_id', $attendance->user_id)
            ->where('attendance_date', '<', $attendance->attendance_date)
            ->orderBy('attendance_date', 'desc')
            ->first();

        $nextAttendance = Attendance::where('user_id', $attendance->user_id)
            ->where('attendance_date', '>', $attendance->attendance_date)
            ->orderBy('attendance_date', 'asc')
            ->first();

        $content = view('admin.page.attendance-detail', compact(
            'page', 'attendance', 'schedule', 'violations', 'workDuration',
            'previousAttendance', 'nextAttendance'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Delete Attendance Record
     */
    public function deleteAttendance(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'reason' => 'required|string|min:10'
        ]);

        try {
            DB::beginTransaction();

            $attendance = Attendance::findOrFail($request->attendance_id);

            // Log the deletion
            Log::info('Attendance deleted', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'user_name' => $attendance->user->name,
                'attendance_date' => $attendance->attendance_date,
                'deleted_by' => auth()->id(),
                'reason' => $request->reason
            ]);

            // Delete photos if exist
            if ($attendance->photo_in && Storage::disk('public')->exists($attendance->photo_in)) {
                Storage::disk('public')->delete($attendance->photo_in);
            }
            if ($attendance->photo_out && Storage::disk('public')->exists($attendance->photo_out)) {
                Storage::disk('public')->delete($attendance->photo_out);
            }

            $attendance->delete();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Record absensi berhasil dihapus'
                ]);
            }

            return redirect()->route('admin.attendance.history')
                ->with('success', 'Record absensi berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting attendance: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus record: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Gagal menghapus record: ' . $e->getMessage());
        }
    }

    /**
     * Edit/Update Attendance Record
     */
    public function updateAttendance(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,izin,sakit,alpha,cuti',
            'note' => 'nullable|string',
            'reason' => 'required|string|min:10'
        ]);

        try {
            DB::beginTransaction();

            $attendance = Attendance::findOrFail($request->attendance_id);
            $oldData = $attendance->toArray();

            // Calculate new late minutes if check_in is changed
            $lateMinutes = 0;
            if ($request->check_in && $request->status == 'hadir') {
                $schedule = WorkSchedule::where('user_id', $attendance->user_id)
                    ->where('day_of_week', Carbon::parse($attendance->attendance_date)->format('l'))
                    ->first();

                if ($schedule) {
                    $checkInTime = Carbon::parse($attendance->attendance_date . ' ' . $request->check_in);
                    $scheduleStartTime = Carbon::parse($attendance->attendance_date . ' ' . $schedule->start_time);

                    if ($checkInTime->gt($scheduleStartTime)) {
                        $lateMinutes = $checkInTime->diffInMinutes($scheduleStartTime);
                    }
                }
            }

            // Update attendance
            $attendance->update([
                'check_in' => $request->check_in ? Carbon::parse($attendance->attendance_date . ' ' . $request->check_in) : null,
                'check_out' => $request->check_out ? Carbon::parse($attendance->attendance_date . ' ' . $request->check_out) : null,
                'status' => $request->status,
                'note' => $request->note,
                'late_minutes' => $lateMinutes,
                'updated_at' => now()
            ]);

            // Log the update
            Log::info('Attendance updated', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'user_name' => $attendance->user->name,
                'old_data' => $oldData,
                'new_data' => $attendance->fresh()->toArray(),
                'updated_by' => auth()->id(),
                'reason' => $request->reason
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Record absensi berhasil diupdate',
                    'data' => $attendance->fresh()
                ]);
            }

            return redirect()->back()
                ->with('success', 'Record absensi berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating attendance: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupdate record: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Gagal mengupdate record: ' . $e->getMessage());
        }
    }
}
