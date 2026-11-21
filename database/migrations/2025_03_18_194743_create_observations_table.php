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
        Schema::create('observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->dateTime('dateutc');
            $table->string('softwaretype')->nullable();
            $table->float('baromin')->nullable();
            $table->float('dailyrainin')->nullable();
            $table->float('dewptf')->nullable();
            $table->float('humidity')->nullable();
            $table->float('rainin')->nullable();
            $table->float('soilmoisture')->nullable();
            $table->float('soiltempf')->nullable();
            $table->float('tempf')->nullable();
            $table->float('visibility')->nullable();
            $table->float('winddir')->nullable();
            $table->float('windspeedmph')->nullable();
            $table->float('windgustdir')->nullable();
            $table->float('windgustmph')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
