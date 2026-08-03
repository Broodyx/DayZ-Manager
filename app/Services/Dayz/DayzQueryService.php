<?php

namespace App\Services\Dayz;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class DayzQueryService
{
    /** @return array<string, mixed> */
    public function check(Project $project): array
    {
        $key = 'dayz-query:'.$project->id;
        return Cache::remember($key, now()->addSeconds(20), function () use ($project): array {
            $started = microtime(true);
            if (! $project->query_host || ! $project->query_port) {
                return ['online' => false, 'error' => 'Server nemá nastavenou query adresu a port.', 'checked_at' => now()->toIso8601String()];
            }
            $address = filter_var($project->query_host, FILTER_VALIDATE_IP) ?: gethostbyname($project->query_host);
            if (! filter_var($address, FILTER_VALIDATE_IP)) {
                return ['online' => false, 'error' => 'Query hostname není platný.', 'checked_at' => now()->toIso8601String()];
            }
            try {
                $socket = @stream_socket_client("udp://{$address}:{$project->query_port}", $errno, $errstr, 3, STREAM_CLIENT_CONNECT);
                if (! $socket) throw new \RuntimeException($errstr ?: 'UDP query se nepodařilo otevřít.');
                stream_set_timeout($socket, 3);
                fwrite($socket, "\xff\xff\xff\xffSource Engine Query\0");
                $response = fread($socket, 4096);
                fclose($socket);
                $result = $this->parseInfoResponse($response);
                $result['ping_ms'] = (int) round((microtime(true) - $started) * 1000);
                $result['checked_at'] = now()->toIso8601String();
                return $result;
            } catch (\Throwable $e) {
                Log::warning('DayZ query failed', ['project_id' => $project->id, 'host' => $project->query_host, 'port' => $project->query_port, 'error' => $e->getMessage()]);
                return ['online' => false, 'error' => 'Server na query portu neodpověděl.', 'checked_at' => now()->toIso8601String()];
            }
        });
    }

    /** @return array<string, mixed> */
    public function parseInfoResponse(string $response): array
    {
        if (strlen($response) < 6 || substr($response, 0, 5) !== "\xff\xff\xff\xffI") throw new \RuntimeException('Neplatná A2S_INFO odpověď.');
        $offset = 5;
        $readString = function () use (&$response, &$offset): string { $end = strpos($response, "\0", $offset); if ($end === false) throw new \RuntimeException('Neúplná A2S_INFO odpověď.'); $value = substr($response, $offset, $end - $offset); $offset = $end + 1; return $value; };
        $readByte = function () use (&$response, &$offset): int { if (! isset($response[$offset])) throw new \RuntimeException('Neúplná A2S_INFO odpověď.'); return ord($response[$offset++]); };
        $readShort = function () use (&$response, &$offset): int { if ($offset + 2 > strlen($response)) throw new \RuntimeException('Neúplná A2S_INFO odpověď.'); $v = unpack('v', substr($response, $offset, 2))[1]; $offset += 2; return $v; };
        $readLong = function () use (&$response, &$offset): int { if ($offset + 4 > strlen($response)) throw new \RuntimeException('Neúplná A2S_INFO odpověď.'); $v = unpack('V', substr($response, $offset, 4))[1]; $offset += 4; return $v; };
        $readByte(); $name = $readString(); $map = $readString(); $readString(); $readString(); $readShort();
        $players = $readByte(); $max = $readByte();
        return ['online' => true, 'name' => $name, 'map' => $map, 'players' => $players, 'max_players' => $max];
    }
}
