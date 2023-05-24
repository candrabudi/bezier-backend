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
        Schema::create('plan_posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('member_user_id');
            $table->bigInteger('plan_library_id');
            $table->string('post');
            $table->string('caption');
            $table->string('hastag');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_posts');
    }
};
