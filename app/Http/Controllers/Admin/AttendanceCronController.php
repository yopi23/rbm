<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\SalarySetting;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AttendanceCronController extends Controller
{
    /**
     * Auto check absent employees - Run at 9:00 AM
     * URL: /admin/cron/check-absent?token=YOUR_SECRET_TOKEN
     */
    public function checkAbsent(Request $request)
    {
        // Security check
        if (!$this->validateCronToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $today = Carbon::today();
            $currentTime = Carbon::now();

            Log::info('=== AUTO ABSENT CHECK STARTED ===', [
                'date' => $today->toDateString(),
                'time' => $currentTime->toTimeString()
            ]);

            DB::beginTransaction();

            // Get all active employees (kasir dan teknisi)
            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->whereIn('user_details.jabatan', [2, 3]) // 2=Kasir, 3=Teknisi
                ->where('users.deleted_at', null)
                ->get(['users.*', 'user_details.*', 'users.id as id_user']);

            $processedCount = 0;
            $alphaCount = 0;
            $violationCount = 0;

            foreach ($employees as $employee) {
                $processedCount++;

                // Check if employee has working schedule today
                $schedule = WorkSchedule::where('user_id', $employee->id_user)
                    ->where('day_of_week', $today->format('l'))
                    ->where('is_working_day', true)
                    ->first();

                if (!$schedule) {
                    continue; // Skip if no working schedule
                }

                // Check if attendance record exists
                $attendance = Attendance::where('user_id', $employee->id_user)
                    ->whereDate('attendance_date', $today)
                    ->first();

                // If no attendance record OR no check_in, mark as alpha
                if (!$attendance || !$attendance->check_in) {

                    // Create or update attendance as alpha
                    $attendance = Attendance::updateOrCreate(
                        [
                            'user_id' => $employee->id_user,
                            'attendance_date' => $today,
                        ],
                        [
                            'status' => 'alpha',
                            'note' => 'Auto marked as alpha by system at ' . $currentTime->format('H:i'),
                            'created_by' => null,
                            'updated_at' => $currentTime
                        ]
                    );

                    $alphaCount++;

                    // Create violation for alpha
                    $violation = $this->createAlphaViolation($employee, $today);
                    if ($violation) {
                        $violationCount++;
                    }

                    Log::info('Employee marked as alpha', [
                        'user_id' => $employee->id_user,
                        'user_name' => $employee->name,
                        'attendance_id' => $attendance->id,
                        'violation_id' => $violation->id ?? null
                    ]);
                }
            }

            DB::commit();

            $result = [
                'success' => true,
                'message' => 'Auto absent check completed',
                'data' => [
                    'processed_count' => $processedCount,
                    'alpha_count' => $alphaCount,
                    'violation_count' => $violationCount,
                    'date' => $today->toDateString(),
                    'time' => $currentTime->toTimeString()
                ]
            ];

            Log::info('=== AUTO ABSENT CHECK COMPLETED ===', $result['data']);

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Auto absent check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Auto absent check failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate cron security token
     */
    private function validateCronToken($request)
    {
        $providedToken = $request->query('token') ?? $request->header('X-Cron-Token');
        $validToken = env('CRON_SECRET_TOKEN', 'default-cron-token-12345');

        // Log attempt for security monitoring
        Log::info('Cron access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'token_provided' => !empty($providedToken),
            'valid_access' => $providedToken === $validToken
        ]);

        return $providedToken === $validToken;
    }

    /**
     * Auto checkout employees - Run at 5:00 PM
     * URL: /admin/cron/auto-checkout?token=YOUR_SECRET_TOKEN
     */
    public function autoCheckout(Request $request)
    {
        // Security check
        if (!$this->validateCronToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $today = Carbon::today();
            $currentTime = Carbon::now();

            Log::info('=== AUTO CHECKOUT STARTED ===', [
                'date' => $today->toDateString(),
                'time' => $currentTime->toTimeString()
            ]);

            DB::beginTransaction();

            // Get attendances that have check_in but no check_out for today
            $attendances = Attendance::with(['user'])
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->where('status', 'hadir')
                ->get();

            $checkedOutCount = 0;

            foreach ($attendances as $attendance) {
                // Update attendance with checkout time
                $attendance->update([
                    'check_out' => $currentTime,
                    'note' => ($attendance->note ? $attendance->note . ' | ' : '') . 'Auto checkout by system at ' . $currentTime->format('H:i'),
                    'updated_at' => $currentTime
                ]);

                $checkedOutCount++;

                Log::info('Employee auto checked out', [
                    'user_id' => $attendance->user_id,
                    'user_name' => $attendance->user->name,
                    'check_in' => $attendance->check_in,
                    'check_out' => $currentTime->toTimeString(),
                    'attendance_id' => $attendance->id
                ]);
            }

            DB::commit();

            $result = [
                'success' => true,
                'message' => 'Auto checkout completed',
                'data' => [
                    'checked_out_count' => $checkedOutCount,
                    'date' => $today->toDateString(),
                    'time' => $currentTime->toTimeString()
                ]
            ];

            Log::info('=== AUTO CHECKOUT COMPLETED ===', $result['data']);

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Auto checkout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Auto checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create violation for alpha (absent) employee
     */
    private function createAlphaViolation($employee, $date)
    {
        try {
            // Get salary setting to determine compensation type
            $salarySetting = SalarySetting::where('user_id', $employee->id_user)->first();

            if (!$salarySetting) {
                Log::warning('No salary setting for alpha violation', [
                    'user_id' => $employee->id_user,
                    'user_name' => $employee->name
                ]);
                return null;
            }

            $penaltyAmount = 0;
            $penaltyPercentage = 0;
            $description = 'Alpha (tidak hadir tanpa keterangan)';

            if ($salarySetting->compensation_type === 'fixed') {
                // For fixed salary: calculate daily salary
                $monthlySalary = $salarySetting->basic_salary;
                $workingDaysInMonth = $this->calculateWorkingDaysInMonth($employee->id_user, $date->year, $date->month);

                if ($workingDaysInMonth > 0) {
                    $dailySalary = $monthlySalary / $workingDaysInMonth;
                } else {
                    // Fallback: assume 22 working days per month
                    $dailySalary = $monthlySalary / 22;
                    $workingDaysInMonth = 22;
                }

                $penaltyAmount = $dailySalary;
                $description .= " - Potongan gaji harian: Rp " . number_format($dailySalary, 0, ',', '.');
            } else {
                // For percentage salary: 5% penalty
                $penaltyPercentage = 5;
                $description .= " - Penalty 5% dari komisi";
            }

            // Create violation
            $violation = Violation::create([
                'user_id' => $employee->id_user,
                'violation_date' => $date,
                'type' => 'alpha',
                'description' => $description . ' (Auto generated)',
                'penalty_amount' => $penaltyAmount,
                'penalty_percentage' => $penaltyPercentage,
                'status' => 'pending',
                'created_by' => null, // System generated
                'metadata' => json_encode([
                    'auto_generated' => true,
                    'compensation_type' => $salarySetting->compensation_type,
                    'daily_salary_calculated' => $penaltyAmount,
                    'working_days_in_month' => $workingDaysInMonth,
                    'generated_at' => Carbon::now()->toISOString(),
                    'reason' => 'alpha_attendance'
                ])
            ]);

            return $violation;

        } catch (\Exception $e) {
            Log::error('Error creating alpha violation', [
                'user_id' => $employee->id_user,
                'user_name' => $employee->name,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Calculate working days in a month for an employee
     */
    private function calculateWorkingDaysInMonth($userId, $year, $month)
    {
        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $workingDays = 0;
            $daysInMonth = $endDate->day;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($year, $month, $day);
                $dayOfWeek = $currentDate->format('l');

                $hasWorkingSchedule = WorkSchedule::where('user_id', $userId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_working_day', true)
                    ->exists();

                if ($hasWorkingSchedule) {
                    $workingDays++;
                }
            }

            return $workingDays > 0 ? $workingDays : 22; // Default to 22 if no schedule found

        } catch (\Exception $e) {
            Log::error('Error calculating working days', [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage()
            ]);

            return 22; // Default fallback
        }
    }

    /**
     * Status check - untuk monitoring
     * URL: /admin/cron/status?token=YOUR_SECRET_TOKEN
     */
    public function status(Request $request)
    {
        // Security check
        if (!$this->validateCronToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $today = Carbon::today();
            $currentTime = Carbon::now();

            // Today's statistics
            $todayAttendances = Attendance::whereDate('attendance_date', $today)->count();
            $todayPresent = Attendance::whereDate('attendance_date', $today)
                ->where('status', 'hadir')->count();
            $todayAlpha = Attendance::whereDate('attendance_date', $today)
                ->where('status', 'alpha')->count();

            // Employees still checked in
            $stillCheckedIn = Attendance::whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->with('user')
                ->count();

            // Recent violations
            $recentViolations = Violation::where('created_at', '>=', Carbon::now()->subDays(7))->count();

            $data = [
                'success' => true,
                'current_time' => $currentTime->toDateTimeString(),
                'timezone' => config('app.timezone'),
                'today_stats' => [
                    'total_attendances' => $todayAttendances,
                    'present_count' => $todayPresent,
                    'alpha_count' => $todayAlpha,
                    'still_checked_in' => $stillCheckedIn
                ],
                'recent_violations' => $recentViolations,
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_time' => date('Y-m-d H:i:s')
                ]
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
