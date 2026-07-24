<x-filament-panels::page>
    <style>
        .dz-editor-grid { display:grid; grid-template-columns:minmax(260px, 340px) minmax(0, 1fr); gap:1rem }
        .dz-panel { border:1px solid rgba(182,233,79,.16); border-radius:.4rem; background:#111813; overflow:hidden }
        .dz-panel-head { padding:1rem; border-bottom:1px solid rgba(182,233,79,.13); background:rgba(182,233,79,.035) }
        .dz-platforms { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem }
        .dz-platform { padding:.85rem 1rem; border:1px solid rgba(190,209,175,.14); border-radius:.35rem; background:#151e17 }
        .dz-platform.active { border-color:#91c52b; box-shadow:inset 3px 0 #b6e94f }
        .dz-platform small,.dz-muted { color:#aab6a4 }
        .dz-tabs { display:flex; gap:.5rem; flex-wrap:wrap }
        .dz-tab { padding:.65rem 1rem; border:1px solid rgba(190,209,175,.18); border-radius:.3rem; color:#cbd5c0; background:#151e17 }
        .dz-tab.active { color:#17210d; border-color:#b6e94f; background:#b6e94f; font-weight:800 }
        .dz-search { width:100%; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-list { max-height:650px; overflow:auto; padding:.5rem }
        .dz-item { display:flex; justify-content:space-between; width:100%; padding:.7rem .75rem; border-radius:.25rem; color:#cbd5c0; text-align:left }
        .dz-item:hover,.dz-item.active { color:#b6e94f; background:rgba(145,197,43,.12) }
        .dz-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; padding:1rem }
        .dz-field { padding:1rem; border:1px solid rgba(190,209,175,.12); border-radius:.35rem; background:#0d130f }
        .dz-field-top { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.75rem }
        .dz-field input[type=number] { width:7rem; border:1px solid rgba(190,209,175,.2); border-radius:.25rem; padding:.45rem .55rem; color:#edf2e9; background:#090d0a }
        .dz-field input[type=range] { width:100%; accent-color:#a3e635 }
        .dz-raw { width:100%; min-height:65vh; resize:vertical; border:1px solid rgba(190,209,175,.2); border-radius:.35rem; padding:1rem; color:#dce8d5; background:#070b08; font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; tab-size:4 }
        .dz-action { display:inline-flex; align-items:center; justify-content:center; padding:.7rem 1.15rem; border-radius:.25rem; color:#17210d; background:#b6e94f; font-weight:800; text-transform:uppercase; letter-spacing:.03em }
        .dz-summary { flex:1; min-width:240px; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-savebar { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; padding:1rem; border-top:1px solid rgba(182,233,79,.13); background:#111813 }
        .dz-warning { padding:.85rem 1rem; border-left:3px solid #d97738; color:#e8c8b3; background:rgba(217,119,56,.08) }
        .dz-error { margin-top:.4rem; color:#fb8b8b; font-size:.85rem }
        @media(max-width:900px){.dz-editor-grid,.dz-platforms,.dz-fields{grid-template-columns:1fr}}
    </style>

    <div class="space-y-4">
        <section class="dz-platforms">
            @foreach (['playstation' => ['PlayStation', 'Console-safe'], 'xbox' => ['Xbox', 'Console-safe'], 'steam' => ['PC / Steam', 'Módy a Workshop']] as $key => [$label, $note])
                <div class="dz-platform {{ $this->getRecord()->platform === $key ? 'active' : '' }}">
                    <strong>{{ $label }}</strong><br>
                    <small>{{ $note }}</small>
                </div>
            @endforeach
        </section>

        @if ($this->getRecord()->platform !== 'steam' && $platformReasons !== [])
            <div class="dz-warning">
                <strong>Detekovány PC-only prvky.</strong>
                Tento projekt je nastavený jako {{ ucfirst($this->getRecord()->platform) }}, ale konfigurace obsahuje:
                {{ implode(', ', $platformReasons) }}. Tyto části nemusí na konzoli fungovat.
            </div>
        @elseif ($platformWarnings !== [])
            <div class="dz-warning">{{ implode(' ', $platformWarnings) }}</div>
        @endif

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="dz-tabs">
                @if ($visualSupported)
                    <button type="button" wire:click="$set('mode', 'visual')" class="dz-tab {{ $mode === 'visual' ? 'active' : '' }}">
                        Vizuální editor
                    </button>
                @endif
                <button type="button" wire:click="$set('mode', 'raw')" class="dz-tab {{ $mode === 'raw' ? 'active' : '' }}">
                    Raw data
                </button>
            </div>
            <span class="dz-muted">Aktuální revize #{{ $revisionNumber }}</span>
        </div>

        @if ($mode === 'visual' && $visualSupported)
            <div class="dz-editor-grid">
                <section class="dz-panel">
                    <div class="dz-panel-head">
                        <strong>Položky types.xml</strong>
                        <p class="dz-muted text-sm mt-1">Zobrazuje se nejvýše 200 výsledků. Použij hledání.</p>
                        <input wire:model.live.debounce.250ms="search" class="dz-search mt-3" placeholder="Hledat například AKM…">
                    </div>
                    <div class="dz-list">
                        @foreach ($this->filteredTypes() as $entry)
                            <button type="button" wire:click="selectType(@js($entry['name']))" class="dz-item {{ $selectedType === $entry['name'] ? 'active' : '' }}">
                                <span>{{ $entry['name'] }}</span>
                                <small>{{ $entry['nominal'] }} ks</small>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="dz-panel">
                    @if ($selectedType)
                        <div class="dz-panel-head">
                            <strong>{{ $selectedType }}</strong>
                            <p class="dz-muted text-sm mt-1">Hodnoty ekonomiky jsou podporované na PlayStation, Xbox i PC.</p>
                        </div>
                        <div class="dz-fields">
                            @foreach ([
                                'nominal' => ['Cílové množství', 1000, 'Počet kusů, který se ekonomika snaží udržet.'],
                                'min' => ['Minimální množství', 1000, 'Hranice, pod kterou začne doplňování.'],
                                'lifetime' => ['Životnost (sekundy)', 3888000, 'Jak dlouho předmět zůstane ve světě.'],
                                'restock' => ['Doba doplnění (sekundy)', 86400, 'Prodleva mezi jednotlivými doplněními.'],
                                'quantmin' => ['Minimální naplnění (%)', 100, '-1 znamená výchozí chování hry.'],
                                'quantmax' => ['Maximální naplnění (%)', 100, '-1 znamená výchozí chování hry.'],
                                'cost' => ['Priorita ekonomiky', 1000, 'Relativní váha položky v ekonomice.'],
                            ] as $field => [$label, $max, $help])
                                <label class="dz-field">
                                    <span class="dz-field-top">
                                        <span><strong>{{ $label }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                        <input type="number" wire:model="typeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}" max="{{ $max }}">
                                    </span>
                                    <input type="range" wire:model.live="typeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}" max="{{ $max }}">
                                    @error("typeForm.$field") <div class="dz-error">{{ $message }}</div> @enderror
                                </label>
                            @endforeach
                        </div>
                        <div class="dz-savebar">
                            <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny (např. zvýšení lootů AKM)">
                            <button type="button" wire:click="saveType" wire:loading.attr="disabled" class="dz-action">
                                Uložit novou revizi
                            </button>
                        </div>
                    @else
                        <div class="p-10 text-center">
                            <strong>Vyber položku vlevo</strong>
                            <p class="dz-muted mt-2">Pak se zobrazí posuvníky a přesné číselné hodnoty.</p>
                        </div>
                    @endif
                </section>
            </div>
        @else
            <section class="dz-panel">
                <div class="dz-panel-head">
                    <strong>Raw XML / JSON</strong>
                    <p class="dz-muted text-sm mt-1">Určeno pro pokročilé úpravy. Před uložením proběhne validace syntaxe.</p>
                </div>
                <div class="p-4">
                    <textarea wire:model="rawContent" class="dz-raw" spellcheck="false"></textarea>
                    @error('rawContent') <div class="dz-error">{{ $message }}</div> @enderror
                </div>
                <div class="dz-savebar">
                    <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny">
                    <button type="button" wire:click="saveRaw" wire:loading.attr="disabled" class="dz-action">
                        Validovat a uložit revizi
                    </button>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
