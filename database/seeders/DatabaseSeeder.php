<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $habits = [
            ['name' => '起床', 'category' => '作息', 'habit_type' => 'wake', 'icon' => 'wake', 'color' => '#ffb800', 'suggested_time' => '07:30:00'],
            ['name' => '上班', 'category' => '工作', 'habit_type' => 'commute', 'icon' => 'work', 'color' => '#1e9fff', 'suggested_time' => '09:00:00'],
            ['name' => '吃饭', 'category' => '饮食', 'habit_type' => 'normal', 'icon' => 'meal', 'color' => '#16baaa', 'suggested_time' => '12:00:00'],
            ['name' => '下班', 'category' => '工作', 'habit_type' => 'commute', 'icon' => 'home', 'color' => '#31bdec', 'suggested_time' => '18:00:00'],
            ['name' => '运动', 'category' => '健康', 'habit_type' => 'duration', 'icon' => 'sport', 'color' => '#ff5722', 'suggested_time' => '19:30:00'],
            ['name' => '学习', 'category' => '成长', 'habit_type' => 'duration', 'icon' => 'study', 'color' => '#5fb878', 'suggested_time' => '20:30:00'],
            ['name' => '玩游戏', 'category' => '娱乐', 'habit_type' => 'duration', 'icon' => 'game', 'color' => '#a233c6', 'suggested_time' => '21:30:00'],
            ['name' => '睡觉', 'category' => '作息', 'habit_type' => 'sleep', 'icon' => 'sleep', 'color' => '#2f4056', 'suggested_time' => '23:00:00'],
            ['name' => '体重', 'category' => '健康', 'habit_type' => 'weight', 'icon' => 'health', 'color' => '#009688', 'suggested_time' => '07:40:00'],
            ['name' => '个人状态', 'category' => '健康', 'habit_type' => 'mood', 'icon' => 'todo', 'color' => '#e91e63', 'suggested_time' => '21:00:00'],
        ];

        foreach ($habits as $index => $habit) {
            Habit::updateOrCreate(
                ['sort_order' => $index + 1],
                $habit + ['is_daily' => true, 'status' => true]
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('admin123456'),
            ]
        );

        AppSetting::updateOrCreate(['setting_key' => 'app_name'], ['setting_value' => '生活习惯打卡统计系统']);
        AppSetting::updateOrCreate(['setting_key' => 'api_base_url'], ['setting_value' => 'http://localhost:8000/api']);
    }
}
