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
        Schema::create('task_clients', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_user_id');
            $table->dateTime('received_at');
            $table->dateTime('submit_at')->nullable();
            $table->enum('status', ['new', 'approved', 'pending', 'need_edit'])->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_members');
    }
};
