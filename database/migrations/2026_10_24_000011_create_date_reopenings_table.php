<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_reopenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->restrictOnDelete();
            $table->date('date');
            $table->foreignId('reopened_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamps();

            $table->unique(['internship_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_reopenings');
    }
};
