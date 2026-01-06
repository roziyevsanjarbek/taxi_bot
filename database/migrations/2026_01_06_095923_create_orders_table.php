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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['tk_bsh', 'bsh_tk']);
            $table->string('city')->nullable();
            $table->integer('passenger_count')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['new', 'sent'])->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
