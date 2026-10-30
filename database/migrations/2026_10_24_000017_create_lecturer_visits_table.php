<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturer_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->restrictOnDelete();
            $table->foreignId('lecturer_id')->constrained('lecturers')->restrictOnDelete();
            $table->date('visit_date');
            $table->string('purpose');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->text('observations')->nullable();
            $table->text('student_performance')->nullable();
            $table->text('company_feedback')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('action_items')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['internship_id', 'visit_date']);
            $table->index(['lecturer_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_visits');
    }
};
