<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuration_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('configuration_import_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('storage_path');
            $table->char('sha256', 64)->index();
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'revision_number']);
        });
    }

    public function down(): void { Schema::dropIfExists('configuration_revisions'); }
};
