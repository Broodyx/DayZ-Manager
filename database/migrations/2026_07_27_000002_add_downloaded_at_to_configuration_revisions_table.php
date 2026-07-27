<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuration_revisions', function (Blueprint $table): void {
            $table->timestamp('downloaded_at')->nullable()->after('change_summary');
        });
    }

    public function down(): void
    {
        Schema::table('configuration_revisions', function (Blueprint $table): void {
            $table->dropColumn('downloaded_at');
        });
    }
};
