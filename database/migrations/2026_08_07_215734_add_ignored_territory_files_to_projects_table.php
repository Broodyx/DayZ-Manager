<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            // Territory files a user has explicitly marked as "not actually wired up, leave
            // it alone" (e.g. a leftover *_territories.xml that duplicates species already
            // covered by their own dedicated files) — the map's spawn validation skips
            // every warning for a filename listed here instead of re-flagging it forever.
            $table->json('ignored_territory_files')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('ignored_territory_files');
        });
    }
};
