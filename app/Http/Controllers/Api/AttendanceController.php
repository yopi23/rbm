<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\QrCodeAttendance;
use App\Models\WorkSchedule;
use App\Models\Violation;
use Illuminate\Http\Request;
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
            // Check if already checked in today
            $existingAttendance = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $today)
                ->first();

            if ($existingAttendance && $existingAttendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in hari ini'
                ], 400);
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
