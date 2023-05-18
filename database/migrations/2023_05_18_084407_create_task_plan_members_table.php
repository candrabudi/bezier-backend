<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_plan_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_client_user_id');
            $table->bigInteger('client_user_id');
            $table->dateTime('received_at');
            $table->dateTime('submit_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_plan_members');
    }
};
