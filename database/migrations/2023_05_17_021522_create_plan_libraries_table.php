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
        Schema::create('plan_libraries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_plan_id');
            $table->bigInteger('client_user_id');
            $table->bigInteger('member_user_id');
            $table->string('plan_title', 191);
            $table->text('plan_description');
            $table->string('plan_prompt', 191);
            $table->string('source_file', 191)->nullable();
            $table->enum('status', ['approved', 'pending', 'not_active', 'need_edit'])->default('pending');
            $table->bigInteger('approved_by');
            $table->dateTime('approved_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_libraries');
    }
};
