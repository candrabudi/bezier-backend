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
        Schema::create('design_libraries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_design_id');
            $table->bigInteger('client_user_id');
            $table->bigInteger('member_user_id');
            $table->bigInteger('category_id');
            $table->string('design_title');
            $table->string('design_path', 191);
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
        Schema::dropIfExists('design_libraries');
    }
};
