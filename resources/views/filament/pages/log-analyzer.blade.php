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

        <div class="dz-log-input">
            <label for="dz-log-textarea">Obsah logu (server console log, restart.log, script log …)</label>
            <textarea id="dz-log-textarea" wire:model="logContent" rows="12" placeholder="Vlož sem obsah .RPT / .log souboru…"></textarea>
            <div class="dz-log-actions">
                <button type="button" wire:click="analyze" wire:loading.attr="disabled" class="dz-action">Analyzovat log</button>
                <button type="button" wire:click="clear" class="dz-secondary">Vymazat</button>
            </div>
        </div>

        @if ($analyzed)
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
                                @if ($finding['link'] === 'map-editor')
                                    <a class="dz-secondary" href="{{ $this->mapEditorUrl() }}">Otevřít Mapový editor →</a>
                                @endif
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
