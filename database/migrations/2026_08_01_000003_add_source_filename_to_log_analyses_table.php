<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('log_analyses', function (Blueprint $table): void {
            $table->string('source_filename')->nullable()->after('source_timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('log_analyses', fn (Blueprint $table) => $table->dropColumn('source_filename'));
    }
};
