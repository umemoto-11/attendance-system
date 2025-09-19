<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (User::count() === 0) {
            $this->call(UserSeeder::class);
        }

        $users = User::where('role', '!=', 'admin')->get();

        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::create(2023, 6, 1)->addDays($i);

                if ($date->isSunday()) {
                    continue;
                }

                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                ]);
            }
        }
    }
}
