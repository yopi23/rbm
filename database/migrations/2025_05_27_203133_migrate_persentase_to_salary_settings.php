<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PresentaseUser;
use App\Models\SalarySetting;

return new class extends Migration
{
    public function up()
    {
        // Migrate data dari PresentaseUser ke SalarySetting
        $presentaseUsers = PresentaseUser::all();

        foreach ($presentaseUsers as $presentase) {
            SalarySetting::updateOrCreate(
                ['user_id' => $presentase->kode_user],
                [
                    'compensation_type' => 'percentage',
                    'basic_salary' => 0,
                    'service_percentage' => 0,
                    'percentage_value' => $presentase->presentase,
                    'target_bonus' => 0,
                    'monthly_target' => 0,
                    'created_by' => 1, // Assume admin
                ]
            );
        }
    }

    public function down()
    {
        // Rollback if needed
        SalarySetting::where('compensation_type', 'percentage')->delete();
    }
};
