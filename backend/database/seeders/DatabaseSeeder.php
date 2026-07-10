<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Мария Орлова',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user = User::query()->updateOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Алексей Смирнов',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $admin->tasks()->delete();
        $user->tasks()->delete();

        $admin->tasks()->createMany([
            [
                'title' => 'Проверить API-контракт',
                'description' => 'Сверить эндпоинты, статусы ответов и формат ошибок.',
                'due_date' => now()->addDays(2)->toDateString(),
                'status' => TaskStatus::InProgress,
            ],
            [
                'title' => 'Опубликовать заметки к релизу',
                'description' => null,
                'due_date' => now()->addWeek()->toDateString(),
                'status' => TaskStatus::Pending,
            ],
            [
                'title' => 'Архивировать завершённый спринт',
                'description' => 'Перенести документы завершённого спринта в архив.',
                'due_date' => now()->subDay()->toDateString(),
                'status' => TaskStatus::Completed,
            ],
        ]);

        $user->tasks()->createMany([
            [
                'title' => 'Подготовить макеты главной',
                'description' => 'Проверить адаптивные состояния и передать финальные экраны.',
                'due_date' => now()->addDay()->toDateString(),
                'status' => TaskStatus::Pending,
            ],
            [
                'title' => 'Созвон с командой',
                'description' => 'Обсудить прогресс, риски и план до конца недели.',
                'due_date' => now()->addDays(3)->toDateString(),
                'status' => TaskStatus::InProgress,
            ],
            [
                'title' => 'Обновить README',
                'description' => null,
                'due_date' => null,
                'status' => TaskStatus::Pending,
            ],
            [
                'title' => 'Отправить итоги ретроспективы',
                'description' => 'Поделиться с командой согласованными шагами.',
                'due_date' => now()->subDays(2)->toDateString(),
                'status' => TaskStatus::Completed,
            ],
            [
                'title' => 'Разобрать входящие',
                'description' => 'Ответить на сообщения и сохранить полезные материалы.',
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => TaskStatus::Pending,
            ],
        ]);
    }
}
