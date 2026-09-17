<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AnnouncementSeeder::class,
            EventSeeder::class,
            ScheduleSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
