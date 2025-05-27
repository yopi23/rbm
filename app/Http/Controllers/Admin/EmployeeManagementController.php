<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SalarySetting;
use App\Models\Violation;
use App\Models\EmployeeMonthlyReport;
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

class EmployeeManagementController extends Controller
{
    public function scanEmployeeQrCode(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        // Validasi admin yang melakukan scan
        $adminId = auth()->id();
        $admin = User::find($adminId);
        $adminDetail = UserDetail::where('kode_user', $adminId)->first();

        if (!$adminDetail || !in_array($adminDetail->jabatan, [1, 2])) {
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
                    $scheduleStartTime = Carbon::parse($todayDate->format('Y-m-d') . ' ' . $schedule->start_time);

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
        $scheduleStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . $schedule->start_time);
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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'compensation_type' => 'required|in:fixed,percentage',
            'basic_salary' => 'required_if:compensation_type,fixed|numeric|min:0',
            'service_percentage' => 'required_if:compensation_type,fixed|integer|min:0|max:100',
            'target_bonus' => 'required_if:compensation_type,fixed|numeric|min:0',
            'monthly_target' => 'required_if:compensation_type,fixed|integer|min:0',
            'percentage_value' => 'required_if:compensation_type,percentage|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'compensation_type' => $request->compensation_type,
                'created_by' => auth()->id(),
            ];

            if ($request->compensation_type == 'fixed') {
                $data['basic_salary'] = $request->basic_salary;
                $data['service_percentage'] = $request->service_percentage;
                $data['target_bonus'] = $request->target_bonus;
                $data['monthly_target'] = $request->monthly_target;
                $data['percentage_value'] = 0;
            } else {
                $data['percentage_value'] = $request->percentage_value;
                $data['basic_salary'] = 0;
                $data['service_percentage'] = 0;
                $data['target_bonus'] = 0;
                $data['monthly_target'] = 0;
            }

            SalarySetting::updateOrCreate(
                ['user_id' => $request->user_id],
                $data
            );

            DB::commit();
            return redirect()->back()->with('success', 'Pengaturan kompensasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
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

        // Hitung kompensasi berdasarkan tipe
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

        // Hitung penalties
        $violations = Violation::where('user_id', $userId)
            ->where('status', 'processed')
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->get();

        $totalPenalties = $violations->sum('penalty_amount');

        // Tambahkan penalty dari persentase
        foreach ($violations as $violation) {
            if ($violation->penalty_percentage) {
                $penaltyAmount = ($totalCommission * $violation->penalty_percentage) / 100;
                $totalPenalties += $penaltyAmount;
            }
        }

        // Hitung gaji final
        $finalSalary = $basicSalary + $totalCommission + $totalBonus - $totalPenalties;

        // Simpan laporan
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
                'status' => 'draft',
                'processed_by' => auth()->id(),
                'percentage_used' => $salarySetting->compensation_type == 'percentage'
                                    ? $salarySetting->percentage_value
                                    : $salarySetting->service_percentage,
            ]
        );
    }

    // ===================================================================
    // OUTSIDE OFFICE MANAGEMENT
    // ===================================================================

    public function setOutsideOffice(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'note' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
        ]);

        $userDetail = UserDetail::where('kode_user', $request->user_id)->first();

        if (!$userDetail) {
            return redirect()->back()->with('error', 'User detail tidak ditemukan');
        }

        $userDetail->update([
            'is_outside_office' => true,
            'outside_note' => $request->note,
            'outside_start_time' => $request->start_time,
            'outside_end_time' => $request->end_time,
        ]);

        return redirect()->back()->with('success', 'Status keluar kantor berhasil diupdate');
    }

    public function resetOutsideOffice($userId)
    {
        $userDetail = UserDetail::where('kode_user', $userId)->first();

        if (!$userDetail) {
            return redirect()->back()->with('error', 'User detail tidak ditemukan');
        }

        $userDetail->update([
            'is_outside_office' => false,
            'outside_note' => null,
            'outside_start_time' => null,
            'outside_end_time' => null,
        ]);

        return redirect()->back()->with('success', 'Status keluar kantor berhasil direset');
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
}
