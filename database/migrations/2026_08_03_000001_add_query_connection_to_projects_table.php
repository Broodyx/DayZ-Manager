<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('query_host')->nullable()->after('map');
            $table->unsignedSmallInteger('game_port')->nullable()->after('query_host');
            $table->unsignedSmallInteger('query_port')->nullable()->after('game_port');
            $table->unsignedSmallInteger('rcon_port')->nullable()->after('query_port');
            $table->index(['query_host', 'query_port']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['projects_query_host_query_port_index']);
            $table->dropColumn(['query_host', 'game_port', 'query_port', 'rcon_port']);
        });
    }
};
