<x-filament-panels::page>
    <div class="dz-log-page">
        @if ($projects)
            <label class="dz-log-server-picker">
                <span>Server (pro odkazy do editoru)</span>
                <select onchange="window.location.href='{{ url('/admin/log-analyzer') }}?project='+this.value">
                    @foreach ($projects as $id => $name)
                        <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        @if (count($history))
            <details class="dz-log-history" open>
                <summary>Historie analýz <small>{{ count($history) }}</small></summary>
                <div class="dz-log-history-list">
                    @foreach ($history as $item)
                        <div class="dz-log-history-row {{ $viewingHistoryId === $item['id'] ? 'active' : '' }}">
                            <button type="button" wire:click="loadFromHistory({{ $item['id'] }})" class="dz-log-history-open">
                                <span>{{ $item['created_at'] }}</span>
                                <span class="dz-log-history-meta">
                                    @if ($item['critical_count'] > 0)<span class="dz-badge dz-log-history-badge-critical">{{ $item['critical_count'] }}× kritické</span>@endif
                                    @if ($item['warning_count'] > 0)<span class="dz-badge dz-log-history-badge-warning">{{ $item['warning_count'] }}× varování</span>@endif
                                    <small>{{ $item['matched_lines'] }}/{{ $item['total_lines'] }} řádků</small>
                                </span>
                            </button>
                            <button type="button" class="dz-danger" x-on:click="dzConfirm('Opravdu odstranit tuto uloženou analýzu z historie?').then((ok) => { if (ok) $wire.deleteHistory({{ $item['id'] }}); })">Smazat</button>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($this->currentProjectHasFtpLogConnection())
            <div class="dz-log-ftp">
                <div class="dz-log-actions">
                    <button type="button" wire:click="analyzeLatestFtpLog" wire:loading.attr="disabled" wire:target="analyzeLatestFtpLog" class="dz-action">
                        <span wire:loading.remove wire:target="analyzeLatestFtpLog">Zkontrolovat poslední restart z FTP</span>
                        <span wire:loading wire:target="analyzeLatestFtpLog">Načítám a kontroluji…</span>
                    </button>
                    <button type="button" wire:click="loadFtpLogFiles" wire:loading.attr="disabled" wire:target="loadFtpLogFiles" class="dz-secondary">Načíst seznam logů z FTP</button>
                    <span wire:loading wire:target="loadFtpLogFiles" class="dz-muted">Načítám…</span>
                </div>
                @if ($ftpLogError)
                    <div class="dz-flash-warning">{{ $ftpLogError }}</div>
                @elseif ($ftpLogsLoaded && ! count($ftpLogFiles))
                    <div class="dz-info">Ve složce s logy nejsou žádné .RPT/.ADM/.log soubory.</div>
                @elseif (count($ftpLogFiles))
                    <div class="dz-log-history-list">
                        @foreach ($ftpLogFiles as $file)
                            <div class="dz-log-history-row">
                                <span>{{ $file['name'] }}<small class="dz-muted">{{ ($file['modified'] ?? null) ? \Illuminate\Support\Carbon::createFromTimestamp($file['modified'])->format('d.m.Y H:i') : '' }}{{ ($file['size'] ?? null) !== null ? ' · '.number_format($file['size'] / 1024, 0, ',', ' ').' kB' : '' }}</small></span>
                                <button type="button" wire:click="loadFtpLogFile('{{ $file['path'] }}')" wire:loading.attr="disabled" class="dz-secondary">Načíst</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="dz-log-input">
            <label for="dz-log-textarea">Obsah logu (server console log, restart.log, script log …)</label>
            <textarea id="dz-log-textarea" wire:model="logContent" rows="12" placeholder="Vlož sem obsah .RPT / .log souboru…"></textarea>
            <div class="dz-log-actions">
                <button type="button" wire:click="analyze" wire:loading.attr="disabled" class="dz-action">Analyzovat log</button>
                <button type="button" wire:click="clear" class="dz-secondary">Vymazat</button>
            </div>
        </div>

        @if ($analyzed)
            @if ($viewingHistoryId)
                <div class="dz-info">Zobrazuješ uloženou analýzu z historie. Nové "Analyzovat log" ji nepřepíše, uloží se jako nová položka.</div>
            @endif
            <div class="dz-log-summary">
                <span><b>{{ $totalLines }}</b> řádků celkem</span>
                <span><b>{{ $matchedLines }}</b> řádků rozpoznáno</span>
                <span><b>{{ count($findings) }}</b> odlišných nálezů</span>
            </div>

            @if (! count($findings))
                <div class="dz-info"><strong>Nic nápadného.</strong> V tomto logu jsem nenašel žádný ze známých vzorů problémů. Pokud řešíš konkrétní chybu, zkopíruj i řádky kolem ní.</div>
            @endif

            @foreach (['critical' => 'KRITICKÉ', 'warning' => 'VAROVÁNÍ', 'info' => 'INFORMACE'] as $severity => $label)
                @php $group = collect($findings)->where('severity', $severity)->values(); @endphp
                @if ($group->count())
                    <section class="dz-log-group dz-log-group-{{ $severity }}">
                        <h3>{{ $label }} <small>{{ $group->count() }}</small></h3>
                        @foreach ($group as $finding)
                            <article class="dz-log-finding">
                                <div class="dz-log-finding-head">
                                    <strong>{{ $finding['title'] }}</strong>
                                    <span class="dz-log-count">{{ $finding['count'] }}×</span>
                                </div>
                                <p>{{ $finding['detail'] }}</p>
                                @if ($finding['action'])
                                    <p class="dz-log-finding-action"><strong>Co udělat:</strong> {{ $finding['action'] }}</p>
                                @endif
                                <div class="dz-log-finding-actions">
                                    @if ($finding['link'] === 'map-editor' && $finding['kind'] === 'missing-event-definition')
                                        <a class="dz-secondary" href="{{ $this->mapEditorUrl($finding['target']) }}">Otevřít Mapový editor a přidat '{{ $finding['target'] }}' →</a>
                                        @if ($projectId)
                                            @php $removeOrphanConfirm = "Bezpečná oprava: odstranit pozice eventu '{$finding['target']}' z cfgeventspawns.xml, protože event v events.xml neexistuje (tyto pozice teď stejně nic nedělají). Vytvoří se nová revize. Opravdu pokračovat?"; @endphp
                                            <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($removeOrphanConfirm)).then((ok) => { if (ok) $wire.removeOrphanEventSpawn(@js($finding['target'])); })">Bezpečně odstranit orphan pozice</button>
                                            @if ($this->latestConfigurationNeedsDeployment('cfgeventspawns.xml'))
                                                <button type="button" class="dz-secondary" wire:click="deployLatestConfiguration('cfgeventspawns.xml')">Nahrát opravu na server</button>
                                            @endif
                                        @endif
                                    @elseif ($finding['link'] === 'map-editor')
                                        <a class="dz-secondary" href="{{ $this->mapEditorUrl() }}">Otevřít Mapový editor →</a>
                                    @elseif ($finding['link'] === 'types-editor' && $this->typesEditorUrl($finding['target']))
                                        <a class="dz-secondary" href="{{ $this->typesEditorUrl($finding['target']) }}">Otevřít '{{ $finding['target'] }}' v types.xml editoru →</a>
                                        @if ($projectId)
                                            @php $removeTypeLogConfirm = "Bezpečná oprava: odebrat položku '{$finding['target']}' z types.xml. Hra ji stejně už teď nespawnuje, takže se chování serveru nezmění — jen zmizí tahle chyba z logu. Vytvoří se nová revize. Opravdu pokračovat?"; @endphp
                                            <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($removeTypeLogConfirm)).then((ok) => { if (ok) $wire.removeTypeEntry(@js($finding['target'])); })">Bezpečně odebrat z types.xml</button>
                                            @if ($this->latestConfigurationNeedsDeployment('types.xml'))
                                                <button type="button" class="dz-secondary" wire:click="deployLatestConfiguration('types.xml')">Nahrát opravu na server</button>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                                <details class="dz-log-example">
                                    <summary>Příklad řádku{{ $finding['first_timestamp'] ? ' · '.$finding['first_timestamp'] : '' }}</summary>
                                    <code>{{ $finding['example'] }}</code>
                                </details>
                            </article>
                        @endforeach
                    </section>
                @endif
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
