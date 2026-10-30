<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->restrictOnDelete();
            $table->foreignId('company_supervisor_id')->constrained('company_supervisors')->restrictOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->unsignedTinyInteger('technical_skills');
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('professionalism');
            $table->unsignedTinyInteger('teamwork');
            $table->unsignedTinyInteger('punctuality');
            $table->unsignedTinyInteger('initiative');
            $table->text('strengths');
            $table->text('areas_for_improvement');
            $table->text('remarks')->nullable();
            $table->string('recommendation', 100)->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'week_start']);
            $table->index(['company_supervisor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
