<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NigerianHolidaysSeeder extends Seeder
{
    /**
     * Seed Nigerian public holidays for 2026
     */
    public function run(): void
    {
        $holidays = [
            // 2026 Nigerian Public Holidays
            ['holiday_date' => '2026-01-01', 'holiday_name' => 'New Year\'s Day', 'description' => 'New Year celebration'],
            ['holiday_date' => '2026-04-03', 'holiday_name' => 'Good Friday', 'description' => 'Christian holiday'],
            ['holiday_date' => '2026-04-06', 'holiday_name' => 'Easter Monday', 'description' => 'Christian holiday'],
            ['holiday_date' => '2026-05-01', 'holiday_name' => 'Workers\' Day', 'description' => 'International Workers\' Day'],
            ['holiday_date' => '2026-05-27', 'holiday_name' => 'Democracy Day', 'description' => 'Celebration of Nigeria\'s democracy'],
            ['holiday_date' => '2026-06-12', 'holiday_name' => 'Democracy Day (Observed)', 'description' => 'June 12 Democracy Day'],
            ['holiday_date' => '2026-10-01', 'holiday_name' => 'Independence Day', 'description' => 'Nigeria\'s Independence'],
            ['holiday_date' => '2026-12-25', 'holiday_name' => 'Christmas Day', 'description' => 'Christian holiday'],
            ['holiday_date' => '2026-12-26', 'holiday_name' => 'Boxing Day', 'description' => 'Day after Christmas'],
            
            // Islamic holidays (approximate dates - these vary based on moon sighting)
            ['holiday_date' => '2026-03-21', 'holiday_name' => 'Eid al-Fitr (estimated)', 'description' => 'End of Ramadan'],
            ['holiday_date' => '2026-05-28', 'holiday_name' => 'Eid al-Adha (estimated)', 'description' => 'Festival of Sacrifice'],
            ['holiday_date' => '2026-06-17', 'holiday_name' => 'Maulud Nabiyy (estimated)', 'description' => 'Prophet Muhammad\'s Birthday'],
        ];

        foreach ($holidays as $holiday) {
            DB::table('holidays')->updateOrInsert(
                ['holiday_date' => $holiday['holiday_date']],
                [
                    'holiday_name' => $holiday['holiday_name'],
                    'description' => $holiday['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Nigerian holidays seeded successfully!');
    }
}
