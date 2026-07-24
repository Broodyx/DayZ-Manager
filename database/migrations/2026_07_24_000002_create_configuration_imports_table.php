<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuration_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->char('sha256', 64)->index();
            $table->string('detected_platform')->default('unknown')->index();
            $table->unsignedTinyInteger('detection_confidence')->default(0);
            $table->string('validation_status')->default('pending')->index();
            $table->json('validation_errors')->nullable();
            $table->timestampTz('imported_at')->useCurrent();
            $table->timestamps();
            $table->index(['project_id', 'imported_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('configuration_imports'); }
};
