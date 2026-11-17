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
        Schema::create('exam_answers', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('exam_submission_id');
            $table->unsignedInteger('exam_question_id');
            $table->char('selected_option', 1);
            $table->boolean('is_correct');

            $table->foreign('exam_submission_id')->references('id')->on('exam_submissions')->cascadeOnDelete();
            $table->foreign('exam_question_id')->references('id')->on('exam_questions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
