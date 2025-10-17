<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Attendance::count() === 0) {
            $this->call(AttendanceSeeder::class);
        }

        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            if (!$attendance->date ||Carbon::parse($attendance->date)->isSunday()) {
                continue;
            }

            if ($attendance->breakTimes()->exists()) {
                continue;
            }

            $attendance->breakTimes()->create([
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }
    }
}

