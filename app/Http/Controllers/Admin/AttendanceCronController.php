<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Models\SalarySetting;
use App\Models\Violation;
use App\Http\Controllers\Admin\PenaltyRulesController; // Pastikan ini diimpor
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AttendanceCronController extends Controller
{
    /**
     * Auto check absent employees - Run at 9:00 AM
     */
    public function checkAbsent(Request $request)
    {
        if (!$this->validateCronToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        try {
            $today = Carbon::today();
            $currentTime = Carbon::now();

            Log::info('=== AUTO ABSENT CHECK STARTED ===', ['date' => $today->toDateString(), 'time' => $currentTime->toTimeString()]);

            DB::beginTransaction();

            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->whereIn('user_details.jabatan', [2, 3])
                ->get(['users.*', 'user_details.*', 'users.id as id_user']);

            $alphaCount = 0;
            $violationCount = 0;

            foreach ($employees as $employee) {
                $schedule = WorkSchedule::where('user_id', $employee->id_user)
                    ->where('day_of_week', $today->format('l'))
                    ->where('is_working_day', true)
                    ->first();

                if (!$schedule) {
                    continue;
                }

                $attendance = Attendance::where('user_id', $employee->id_user)
                    ->whereDate('attendance_date', $today)
                    ->first();

                if (!$attendance || !$attendance->check_in) {
                    Attendance::updateOrCreate(
                        ['user_id' => $employee->id_user, 'attendance_date' => $today],
                        ['status' => 'alpha', 'note' => 'Auto marked as alpha by system at ' . $currentTime->format('H:i')]
                    );
                    $alphaCount++;

                    $violation = $this->createAlphaViolation($employee, $today);
                    if ($violation) {
                        $violationCount++;
                    }
                }
            }

            DB::commit();

            $result = [
                'success' => true,
                'message' => 'Auto absent check completed',
                'data' => [
                    'processed_count' => $employees->count(),
                    'alpha_count' => $alphaCount,
                    'violation_count' => $violationCount,
                ]
            ];
            Log::info('=== AUTO ABSENT CHECK COMPLETED ===', $result['data']);
            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto absent check failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Auto absent check failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create violation for alpha (absent) employee
     * VERSI PERBAIKAN: Meniru alur dari EmployeeManagementController@attendanceCheckIn
     */
    private function createAlphaViolation($employee, $date)
    {
        try {
            Log::info('=== [REVISED] STARTING ALPHA VIOLATION CREATION ===', [
                'user_id' => $employee->id_user,
                'user_name' => $employee->name
            ]);

            // 1. Dapatkan tipe kompensasi, sama seperti di attendanceCheckIn
            $salarySetting = SalarySetting::where('user_id', $employee->id_user)->first();
            $compensationType = $salarySetting ? $salarySetting->compensation_type : 'fixed'; // Default 'fixed'

            // 2. Panggil helper untuk mendapatkan info penalti dari database
            $penaltyInfo = $this->calculateAlphaPenalty($compensationType, $employee->id_user);

            // 3. Cek apakah harus membuat pelanggaran, sama persis seperti attendanceCheckIn
            if ($penaltyInfo['success'] && $penaltyInfo['should_create_violation']) {

                // 4. Buat record pelanggaran menggunakan data dari penaltyInfo
                $violation = Violation::create([
                    'user_id' => $employee->id_user,
                    'violation_date' => $date,
                    'type' => 'alpha',
                    'description' => $penaltyInfo['penalty_description'] . ' (Auto Generated)',
                    'penalty_amount' => $penaltyInfo['penalty_amount'],
                    'penalty_percentage' => $penaltyInfo['penalty_percentage'],
                    'status' => 'pending',
                    'created_by' => $this->getCurrentOwnerCode($employee->id_user),
                    'metadata' => json_encode([
                        'rule_id' => $penaltyInfo['rule_id'] ?? null,
                        'compensation_type' => $compensationType,
                        'owner_code' => $this->getCurrentOwnerCode($employee->id_user),
                        'auto_generated' => true,
                        'calculated_at' => now()->toISOString()
                    ])
                ]);

                Log::info('[REVISED] Alpha violation created successfully using database rules', [
                    'violation_id' => $violation->id,
                    'user_id' => $employee->id_user,
                    'rule_id' => $penaltyInfo['rule_id'],
                ]);

                return $violation;

            } else {
                Log::warning('[REVISED] Alpha violation not created because penalty rules determined it was not necessary.', [
                    'user_id' => $employee->id_user,
                    'penalty_info' => $penaltyInfo
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('=== [REVISED] ERROR CREATING ALPHA VIOLATION ===', [
                'user_id' => $employee->id_user,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Helper method untuk menghitung penalti alpha.
     */
    private function calculateAlphaPenalty(string $compensationType, $userId): array
    {
        $ownerCode = $this->getCurrentOwnerCode($userId);

        return PenaltyRulesController::getApplicablePenalty(
            'absence',
            $compensationType,
            0,
            $ownerCode
        );
    }

    /**
     * Auto checkout employees - Run at 5:00 PM
     */
    public function autoCheckout(Request $request)
    {
        if (!$this->validateCronToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        try {
            $today = Carbon::today();
            $currentTime = Carbon::now();
            Log::info('=== AUTO CHECKOUT STARTED ===', ['date' => $today->toDateString(), 'time' => $currentTime->toTimeString()]);
            DB::beginTransaction();

            $attendances = Attendance::with(['user'])
                ->whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->where('status', 'hadir')
                ->get();

            foreach ($attendances as $attendance) {
                $attendance->update([
                    'check_out' => $currentTime,
                    'note' => ($attendance->note ? $attendance->note . ' | ' : '') . 'Auto checkout by system at ' . $currentTime->format('H:i'),
                ]);
            }

            DB::commit();
            $result = [
                'success' => true,
                'message' => 'Auto checkout completed',
                'data' => ['checked_out_count' => $attendances->count()]
            ];
            Log::info('=== AUTO CHECKOUT COMPLETED ===', $result['data']);
            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto checkout failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Auto checkout failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper untuk mendapatkan kode owner (Upline ID).
     */
    private function getCurrentOwnerCode($userId = null)
    {
        if ($userId) {
            return UserDetail::where('kode_user', $userId)->value('id_upline');
        }

        // Fallback jika tidak ada user ID, misal dari user yang sedang login (jika cron dijalankan via web)
        if(auth()->check()){
            return UserDetail::where('kode_user', auth()->id())->value('id_upline');
        }

        return null;
    }

    /**
     * Validate cron security token
     */
    private function validateCronToken($request)
    {
        $providedToken = $request->query('token') ?? $request->header('X-Cron-Token');
        $validToken = env('CRON_SECRET_TOKEN');

        if (empty($validToken)) {
            Log::error('CRON_SECRET_TOKEN is not set in .env file.');
            return false;
        }

        return !empty($providedToken) && $providedToken === $validToken;
    }

    /**
     * Status check - untuk monitoring
     */
    public function status(Request $request)
    {
        if (!$this->validateCronToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        return response()->json(['success' => true, 'message' => 'Cron service is running.']);
    }
}
