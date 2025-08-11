<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesor_course', function (Blueprint $table) {
            $table->foreignId('profesor_id')->constrained('profesors');
            $table->foreignId('course_id')->constrained('courses');
            $table->primary(['profesor_id', 'course_id']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesor_course');
    }
};
