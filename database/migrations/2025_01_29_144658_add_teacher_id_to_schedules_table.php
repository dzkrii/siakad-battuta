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
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable(); // Menambahkan kolom teacher_id
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null'); // Menambahkan foreign key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']); // Menghapus foreign key
            $table->dropColumn('teacher_id');    // Menghapus kolom teacher_id
        });
    }
};
