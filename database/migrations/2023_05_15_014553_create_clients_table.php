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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('phone_number', 25);
            $table->string('whatsapp_number', 25);
            $table->string('country');
            $table->text('address');
            $table->string('company_name', 191);
            $table->string('company_email', 191);
            $table->string('company_number', 25);
            $table->string('company_website', 191);
            $table->text('company_description');
            $table->string('link_instagram', 191)->nullable();
            $table->string('link_facebook', 191)->nullable();
            $table->string('link_twitter', 191)->nullable();
            $table->enum('string', ['not_active', 'Pending', 'done'])->default('not_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
