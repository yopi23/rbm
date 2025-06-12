<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenaltyRule;
use App\Traits\HasOwnerScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PenaltyRulesController extends Controller
{

    /**
     * Display penalty rules management page
     */
    public function index()
    {
        $page = "Pengaturan Aturan Penalty";
        $content = view('admin.page.penalty-rules')->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Get all penalty rules for current owner (API)
     */
    public function list()
    {
        try {
            $ownerCode = $this->getThisUser()->id_upline;
            if (!$ownerCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner tidak ditemukan'
                ], 403);
            }

            $rules = PenaltyRule::with(['createdBy', 'updatedBy'])
                ->forOwner($ownerCode)
                ->orderBy('rule_type')
                ->orderBy('compensation_type')
                ->orderBy('priority')
                ->orderBy('min_minutes')
                ->get();

            return response()->json([
                'success' => true,
                'rules' => $rules,
                'owner_code' => $ownerCode
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching penalty rules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat aturan penalty'
            ], 500);
        }
    }

    /**
     * Store new penalty rule
     */
    public function store(Request $request)
    {
        $ownerCode = $this->getThisUser()->id_upline;
        // if (!$ownerCode) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Owner tidak ditemukan'
        //     ], 403);
        // }

        $validator = Validator::make($request->all(), [
            'rule_type' => 'required|in:attendance_late,outside_office_late,absence,other',
            'compensation_type' => 'required|in:fixed,percentage,both',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:1|gte:min_minutes',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|integer|min:0|max:100',
            'description' => 'required|string|max:255',
            'priority' => 'required|integer|min:1',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validation: At least one penalty type must be specified
            if (!$request->penalty_amount && !$request->penalty_percentage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harus mengisi minimal satu jenis penalty (nominal atau persentase)'
                ], 422);
            }

            // Check for overlapping rules within owner scope
            $overlap = $this->checkRuleOverlap($request, null);
            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rentang waktu bertabrakan dengan aturan yang sudah ada'
                ], 422);
            }

            $rule = PenaltyRule::create([
                'kode_owner' => $ownerCode,
                'rule_type' => $request->rule_type,
                'compensation_type' => $request->compensation_type,
                'min_minutes' => $request->min_minutes,
                'max_minutes' => $request->max_minutes,
                'penalty_amount' => $request->penalty_amount ?? 0,
                'penalty_percentage' => $request->penalty_percentage ?? 0,
                'description' => $request->description,
                'priority' => $request->priority,
                'is_active' => $request->is_active,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            Log::info('Penalty rule created', [
                'rule_id' => $rule->id,
                'owner_code' => $ownerCode,
                'created_by' => auth()->id(),
                'rule_data' => $rule->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aturan penalty berhasil dibuat',
                'rule' => $rule
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating penalty rule: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat aturan penalty: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific penalty rule
     */
    public function show($id)
    {
        try {
           $ownerCode=$this->getThisUser()->id_upline;
            if (!$ownerCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner tidak ditemukan'
                ], 403);
            }

            $rule = PenaltyRule::forOwner($ownerCode)->findOrFail($id);

            return response()->json([
                'success' => true,
                'rule' => $rule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aturan tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update penalty rule
     */
    public function update(Request $request, $id)
    {
        $ownerCode=$this->getThisUser()->id_upline;
        if (!$ownerCode) {
            return response()->json([
                'success' => false,
                'message' => 'Owner tidak ditemukan'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rule_type' => 'required|in:attendance_late,outside_office_late,absence,other',
            'compensation_type' => 'required|in:fixed,percentage,both',
            'min_minutes' => 'required|integer|min:0',
            'max_minutes' => 'nullable|integer|min:1|gte:min_minutes',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|integer|min:0|max:100',
            'description' => 'required|string|max:255',
            'priority' => 'required|integer|min:1',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $rule = PenaltyRule::forOwner($ownerCode)->findOrFail($id);

            // Validation: At least one penalty type must be specified
            if (!$request->penalty_amount && !$request->penalty_percentage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harus mengisi minimal satu jenis penalty (nominal atau persentase)'
                ], 422);
            }

            // Check for overlapping rules (exclude current rule) within owner scope
            $overlap = $this->checkRuleOverlap($request, $id, $ownerCode);
            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rentang waktu bertabrakan dengan aturan yang sudah ada'
                ], 422);
            }

            $originalData = $rule->toArray();

            $rule->update([
                'rule_type' => $request->rule_type,
                'compensation_type' => $request->compensation_type,
                'min_minutes' => $request->min_minutes,
                'max_minutes' => $request->max_minutes,
                'penalty_amount' => $request->penalty_amount ?? 0,
                'penalty_percentage' => $request->penalty_percentage ?? 0,
                'description' => $request->description,
                'priority' => $request->priority,
                'is_active' => $request->is_active,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            Log::info('Penalty rule updated', [
                'rule_id' => $rule->id,
                'owner_code' => $ownerCode,
                'updated_by' => auth()->id(),
                'original_data' => $originalData,
                'new_data' => $rule->fresh()->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aturan penalty berhasil diupdate',
                'rule' => $rule->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating penalty rule: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate aturan penalty: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete penalty rule
     */
    public function destroy($id)
    {
        try {
            $ownerCode=$this->getThisUser()->id_upline;
            if (!$ownerCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner tidak ditemukan'
                ], 403);
            }

            DB::beginTransaction();

            $rule = PenaltyRule::forOwner($ownerCode)->findOrFail($id);
            $ruleData = $rule->toArray();

            $rule->delete();

            DB::commit();

            Log::info('Penalty rule deleted', [
                'rule_id' => $id,
                'owner_code' => $ownerCode,
                'deleted_by' => auth()->id(),
                'deleted_data' => $ruleData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aturan penalty berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting penalty rule: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus aturan penalty: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Seed default penalty rules for current owner
     */
    public function seed()
    {
        try {
            $ownerCode=$this->getThisUser()->id_upline;
            if (!$ownerCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner tidak ditemukan'
                ], 403);
            }

            DB::beginTransaction();

            $defaultRules = PenaltyRule::getDefaultRules($ownerCode);
            $createdCount = 0;

            foreach ($defaultRules as $ruleData) {
                // Check if similar rule already exists for this owner
                $exists = PenaltyRule::forOwner($ownerCode)
                    ->where('rule_type', $ruleData['rule_type'])
                    ->where('compensation_type', $ruleData['compensation_type'])
                    ->where('min_minutes', $ruleData['min_minutes'])
                    ->where('max_minutes', $ruleData['max_minutes'])
                    ->exists();

                if (!$exists) {
                    PenaltyRule::create(array_merge($ruleData, [
                        'is_active' => true,
                        'metadata' => null,
                        'created_by' => auth()->id()
                    ]));
                    $createdCount++;
                }
            }

            DB::commit();

            Log::info('Default penalty rules seeded', [
                'owner_code' => $ownerCode,
                'created_count' => $createdCount,
                'seeded_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menambahkan {$createdCount} aturan default",
                'created_count' => $createdCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error seeding penalty rules: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat aturan default: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for overlapping rules within owner scope
     */
    private function checkRuleOverlap(Request $request, $excludeId = null, $ownerCode= null)
    {
        $query = PenaltyRule::where('rule_type', $request->rule_type)
            ->where('compensation_type', $request->compensation_type)
            ->where('is_active', true);

        // Apply owner scope
        // $ownerCode=$this->getThisUser()->id_upline;
        if ($ownerCode) {
            $query->forOwner($ownerCode);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingRules = $query->get();

        foreach ($existingRules as $rule) {
            $newMin = $request->min_minutes;
            $newMax = $request->max_minutes;
            $existingMin = $rule->min_minutes;
            $existingMax = $rule->max_minutes;

            // Check for overlap
            if ($this->rangesOverlap($newMin, $newMax, $existingMin, $existingMax)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two ranges overlap
     */
    private function rangesOverlap($min1, $max1, $min2, $max2)
    {
        // Handle null max values (unlimited)
        if ($max1 === null) $max1 = PHP_INT_MAX;
        if ($max2 === null) $max2 = PHP_INT_MAX;

        // Check overlap: (start1 <= end2) && (start2 <= end1)
        return ($min1 <= $max2) && ($min2 <= $max1);
    }

    /**
     * Get applicable penalty rule for given parameters with owner scope
     */
    public static function getApplicablePenalty($ruleType, $compensationType, $minutes, $ownerCode)
    {
        try {
            // $ownerCode=$this->getThisUser()->id_upline;
            // If no owner code provided, try to get from current context
            if (!$ownerCode) {
                $controller = new static();
                $ownerCode = $controller->getCurrentOwnerCode();
            }

            if (!$ownerCode) {
                Log::warning('No owner code available for penalty calculation');
                return [
                    'success' => false,
                    'penalty_amount' => 0,
                    'penalty_percentage' => 0,
                    'should_create_violation' => false,
                    'penalty_description' => 'Owner tidak ditemukan',
                    'rule_id' => null
                ];
            }

            $rule = PenaltyRule::findApplicableRule($ruleType, $compensationType, $minutes, $ownerCode);

            if ($rule) {
                return [
                    'success' => true,
                    'penalty_amount' => $rule->penalty_amount,
                    'penalty_percentage' => $rule->penalty_percentage,
                    'should_create_violation' => true,
                    'penalty_description' => $rule->description,
                    'rule_id' => $rule->id,
                    'owner_code' => $ownerCode
                ];
            } else {
                return [
                    'success' => true,
                    'penalty_amount' => 0,
                    'penalty_percentage' => 0,
                    'should_create_violation' => false,
                    'penalty_description' => 'Dalam batas toleransi',
                    'rule_id' => null,
                    'owner_code' => $ownerCode
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error getting applicable penalty: ' . $e->getMessage(), [
                'rule_type' => $ruleType,
                'compensation_type' => $compensationType,
                'minutes' => $minutes,
                'owner_code' => $ownerCode
            ]);

            return [
                'success' => false,
                'penalty_amount' => 0,
                'penalty_percentage' => 0,
                'should_create_violation' => false,
                'penalty_description' => 'Error dalam menentukan penalty',
                'rule_id' => null
            ];
        }
    }

    /**
     * Export penalty rules to CSV for current owner
     */
    public function export()
    {
        try {
            $ownerCode=$this->getThisUser()->id_upline;
            if (!$ownerCode) {
                return redirect()->back()->with('error', 'Owner tidak ditemukan');
            }

            $rules = PenaltyRule::with(['createdBy'])
                ->forOwner($ownerCode)
                ->orderBy('rule_type')
                ->orderBy('compensation_type')
                ->orderBy('priority')
                ->get();

            $filename = 'penalty_rules_owner_' . $ownerCode . '_' . date('Y_m_d_H_i_s') . '.csv';

            $csvContent = "Jenis Aturan,Tipe Kompensasi,Min Menit,Max Menit,Penalty Nominal,Penalty Persentase,Deskripsi,Status,Prioritas,Dibuat Oleh,Tanggal Dibuat\n";

            foreach ($rules as $rule) {
                $ruleTypeLabel = $rule->rule_type === 'attendance_late' ? 'Keterlambatan Absensi' : 'Izin Keluar Terlambat';
                $compensationTypeLabel = [
                    'fixed' => 'Gaji Tetap',
                    'percentage' => 'Sistem Persentase',
                    'both' => 'Keduanya'
                ][$rule->compensation_type];

                $csvContent .= '"' . $ruleTypeLabel . '",';
                $csvContent .= '"' . $compensationTypeLabel . '",';
                $csvContent .= '"' . $rule->min_minutes . '",';
                $csvContent .= '"' . ($rule->max_minutes ?? 'Unlimited') . '",';
                $csvContent .= '"' . number_format($rule->penalty_amount, 0, ',', '.') . '",';
                $csvContent .= '"' . $rule->penalty_percentage . '%",';
                $csvContent .= '"' . str_replace('"', '""', $rule->description) . '",';
                $csvContent .= '"' . ($rule->is_active ? 'Aktif' : 'Tidak Aktif') . '",';
                $csvContent .= '"' . $rule->priority . '",';
                $csvContent .= '"' . ($rule->createdBy->name ?? '-') . '",';
                $csvContent .= '"' . $rule->created_at->format('d/m/Y H:i') . '"';
                $csvContent .= "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Error exporting penalty rules: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }
}
