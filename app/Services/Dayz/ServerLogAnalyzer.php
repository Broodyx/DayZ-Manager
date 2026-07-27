<?php

namespace App\Services\Dayz;

final class ServerLogAnalyzer
{
    /**
     * @return array{findings: list<array<string, mixed>>, totalLines: int, matchedLines: int}
     */
    public function analyze(string $content): array
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $findings = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            foreach ($this->rules() as $rule) {
                if (! preg_match($rule['pattern'], $line, $matches)) {
                    continue;
                }
                $captured = $matches[1] ?? null;
                $key = $rule['key'].($captured !== null ? '|'.$captured : '');
                if (! isset($findings[$key])) {
                    $findings[$key] = [
                        'kind' => $rule['key'],
                        'severity' => $rule['severity'],
                        'title' => is_callable($rule['title']) ? $rule['title']($captured) : $rule['title'],
                        'detail' => is_callable($rule['detail']) ? $rule['detail']($captured) : $rule['detail'],
                        'action' => isset($rule['action']) && is_callable($rule['action']) ? $rule['action']($captured) : ($rule['action'] ?? null),
                        'link' => $rule['link'] ?? null,
                        'target' => $captured,
                        'count' => 0,
                        'example' => trim($line),
                        'first_timestamp' => $this->extractTimestamp($line),
                    ];
                }
                $findings[$key]['count']++;
                break;
            }
        }

        $ordering = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $result = array_values($findings);
        usort($result, fn ($a, $b) => $ordering[$a['severity']] <=> $ordering[$b['severity']] ?: $b['count'] <=> $a['count']);

        return [
            'findings' => $result,
            'totalLines' => count(array_filter($lines, fn ($line) => trim($line) !== '')),
            'matchedLines' => array_sum(array_column($result, 'count')),
        ];
    }

    private function extractTimestamp(string $line): ?string
    {
        if (preg_match('/^(\d{2}:\d{2}:\d{2})/', $line, $m)) {
            return $m[1];
        }
        if (preg_match('/^\w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2}/', $line, $m)) {
            return $m[0];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $line, $m)) {
            return $m[0];
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function rules(): array
    {
        return [
            [
                'pattern' => '/NO VALID SPAWNS, players will spawn at.*0,\s*0,\s*0/i',
                'key' => 'no-valid-spawns',
                'severity' => 'critical',
                'title' => 'Žádné platné spawn body hráčů',
                'detail' => 'Server nemá ani jednu platnou skupinu v cfgplayerspawnpoints.xml, takže se noví hráči objeví na souřadnicích {0,0,0} — mimo mapu nebo pod terénem.',
                'action' => 'Otevřít Mapový editor a doplnit alespoň jeden bod pro každý používaný režim (fresh/hop/travel).',
                'link' => 'map-editor',
            ],
            [
                'pattern' => '/PlayerSpawnPoints.*There are no valid groups/i',
                'key' => 'no-valid-spawn-groups',
                'severity' => 'critical',
                'title' => 'cfgplayerspawnpoints.xml nemá žádné platné skupiny',
                'detail' => 'Všechny skupiny v generator_posbubbles jsou buď prázdné, nebo chybí úplně.',
                'action' => 'Otevřít Mapový editor a doplnit spawn body.',
                'link' => 'map-editor',
            ],
            [
                'pattern' => '/\[CE\]\[LoadPrototype\]\s+(\d+)\s+groups have no points/i',
                'key' => 'groups-no-points',
                'severity' => 'warning',
                'title' => fn ($n) => "{$n}× skupina objektů nemá žádné pozice",
                'detail' => 'mapgroupproto.xml definuje skupiny/prototypy, pro které mapgrouppos.xml neobsahuje žádnou pozici (nebo naopak).',
                'action' => 'Zkontrolovat soulad mapgrouppos.xml a mapgroupproto.xml — jde obvykle jen o neškodné duplicity ve stock datech.',
                'link' => 'map-editor',
            ],
            [
                'pattern' => '/\[CE\]\[LoadPrototype\]\s+(\d+)\s+Errors? during XML parse/i',
                'key' => 'xml-parse-errors',
                'severity' => 'warning',
                'title' => fn ($n) => "{$n}× chyba při parsování mapového XML",
                'detail' => 'Herní engine narazil na syntakticky neplatné XML při načítání mapových prototypů/clusterů (mapgroupproto.xml, mapgroupcluster*.xml).',
                'action' => 'Zkontrolovat raw obsah těchto souborů v editoru — často jde o poškozený export z nástroje třetí strany.',
                'link' => 'map-editor',
            ],
            [
                'pattern' => "/Type '([^']+)' will be ignored\\. \\(Not spawnable/i",
                'key' => 'type-not-spawnable',
                'severity' => 'warning',
                'title' => fn ($t) => "Položka '{$t}' se nespawnuje (Not spawnable)",
                'detail' => 'Položka je v types.xml, ale hra ji považuje za nespawnovatelnou (scope není public, nebo chybí mod, který ji definuje).',
                'action' => 'Ověřit, že odpovídající mod je nahraný na serveru, případně položku z types.xml odebrat.',
                'link' => 'types-editor',
            ],
            [
                'pattern' => "/Type '([^']+)' will be ignored\\. \\(Type does not exist/i",
                'key' => 'type-does-not-exist',
                'severity' => 'critical',
                'title' => fn ($t) => "Položka '{$t}' v types.xml neexistuje ve hře",
                'detail' => 'Class name pravděpodobně obsahuje překlep, nebo patří k modu, který na serveru chybí.',
                'action' => fn ($t) => "Otevřít types.xml editor a opravit nebo odebrat položku '{$t}'.",
                'link' => 'types-editor',
            ],
            [
                'pattern' => "/Skipping entry for non-existing event '([^']+)'/i",
                'key' => 'missing-event-definition',
                'severity' => 'warning',
                'title' => fn ($e) => "cfgeventspawns.xml odkazuje na neexistující event '{$e}'",
                'detail' => 'Pozice v cfgeventspawns.xml patří eventu, který není definovaný v events.xml (nebo je přejmenovaný/odebraný).',
                'action' => fn ($e) => "Přidat event '{$e}' do events.xml, nebo odpovídající pozice odebrat v Mapovém editoru.",
                'link' => 'map-editor',
            ],
            [
                'pattern' => '/restarted more than \d+ times in a row.*Stopping server/i',
                'key' => 'restart-loop-stopped',
                'severity' => 'critical',
                'title' => 'Server se opakovaně restartoval a byl automaticky zastaven',
                'detail' => 'Hosting zastavil server poté, co spadl a restartoval se vícekrát za sebou v krátkém čase — obvykle kvůli chybné konfiguraci nebo módu zavedenému těsně před prvním pádem.',
                'action' => 'Zkontrolovat poslední uloženou revizi konfigurace/módů před prvním restartem v této sérii.',
            ],
            [
                'pattern' => '/BattlEye Server: Initialized/i',
                'key' => 'battleye-initialized',
                'severity' => 'info',
                'title' => 'BattlEye se úspěšně inicializoval',
                'detail' => 'Anticheat modul naběhl bez chyby.',
            ],
            [
                'pattern' => '/SUCCESS: SteamGameServer_Init/i',
                'key' => 'steam-init-success',
                'severity' => 'info',
                'title' => 'Steam Game Server se úspěšně spustil',
                'detail' => 'Server se úspěšně přihlásil ke Steam službám.',
            ],
            [
                'pattern' => '/\[CE\]\[Hive\] :: Initializing (OFFLINE|CENTRAL)/i',
                'key' => 'hive-init',
                'severity' => 'info',
                'title' => fn ($mode) => 'Central Economy startuje v režimu '.strtoupper((string) $mode),
                'detail' => 'Standardní start ekonomiky serveru.',
            ],
        ];
    }
}
