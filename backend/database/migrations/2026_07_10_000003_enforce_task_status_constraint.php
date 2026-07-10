<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum(
                'status',
                array_map(static fn (TaskStatus $status): string => $status->value, TaskStatus::cases()),
            )->default(TaskStatus::Pending->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default(TaskStatus::Pending->value)->change();
        });
    }
};
