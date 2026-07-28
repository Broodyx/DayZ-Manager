<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('ftp_protocol')->nullable()->after('hosting');
            $table->string('ftp_host')->nullable()->after('ftp_protocol');
            $table->unsignedInteger('ftp_port')->nullable()->after('ftp_host');
            $table->string('ftp_username')->nullable()->after('ftp_port');
            $table->text('ftp_password')->nullable()->after('ftp_username');
            $table->string('ftp_root_path')->nullable()->after('ftp_password');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['ftp_protocol', 'ftp_host', 'ftp_port', 'ftp_username', 'ftp_password', 'ftp_root_path']);
        });
    }
};
