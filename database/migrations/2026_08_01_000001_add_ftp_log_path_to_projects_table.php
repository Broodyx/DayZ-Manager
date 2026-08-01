<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            // Some hosts (e.g. Nitrado on PlayStation/Xbox) expose server logs under a
            // completely separate FTP root (like "0:/dayzps/config/") from the mission
            // files root ("1:/dayzps_missions/<mission>/") — Flysystem's FTP/SFTP adapters
            // chroot every request to a single configured root, so reaching the other one
            // needs its own connection built with this path instead of ftp_root_path.
            $table->string('ftp_log_path')->nullable()->after('ftp_root_path');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('ftp_log_path');
        });
    }
};
