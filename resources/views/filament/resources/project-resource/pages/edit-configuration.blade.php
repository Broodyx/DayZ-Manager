<x-filament-panels::page>
    <style>
        .fi-main { max-width:100rem !important }
        .dz-heading-badge { display:inline-flex; margin-left:.55rem; padding:.25rem .6rem; border-radius:999px; vertical-align:.2em; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em }
        .dz-heading-badge.safe { color:#c9f67a; background:rgba(145,197,43,.14); border:1px solid rgba(182,233,79,.25) }
        .dz-heading-badge.pc { color:#ffc08f; background:rgba(217,119,56,.14); border:1px solid rgba(217,119,56,.3) }
        .dz-editor-grid { display:grid; grid-template-columns:minmax(360px, 460px) minmax(620px, 1fr); gap:1rem }
        .dz-panel { border:1px solid rgba(182,233,79,.16); border-radius:.4rem; background:#111813; overflow:hidden }
        .dz-panel-head { padding:1rem; border-bottom:1px solid rgba(182,233,79,.13); background:rgba(182,233,79,.035) }
        .dz-muted { color:#aab6a4 }
        .dz-tabs { display:flex; gap:.5rem; flex-wrap:wrap }
        .dz-tab { padding:.65rem 1rem; border:1px solid rgba(190,209,175,.18); border-radius:.3rem; color:#cbd5c0; background:#151e17 }
        .dz-tab.active { color:#17210d; border-color:#b6e94f; background:#b6e94f; font-weight:800 }
        .dz-search { width:100%; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-list { max-height:720px; overflow:auto; padding:.5rem }
        .dz-group { margin-bottom:.5rem; border:1px solid rgba(190,209,175,.1); border-radius:.3rem; overflow:hidden }
        .dz-group summary { display:flex; align-items:center; justify-content:space-between; padding:.75rem; color:#edf2e9; background:#151e17; cursor:pointer; font-weight:700; text-transform:uppercase; letter-spacing:.04em }
        .dz-group summary::marker { color:#b6e94f }
        .dz-group-body { padding:.35rem }
        .dz-item { display:flex; justify-content:space-between; width:100%; padding:.7rem .75rem; border-radius:.25rem; color:#cbd5c0; text-align:left }
        .dz-item:hover,.dz-item.active { color:#b6e94f; background:rgba(145,197,43,.12) }
        .dz-item-meta { display:flex; align-items:center; gap:.45rem }
        .dz-badge { display:inline-flex; padding:.16rem .45rem; border-radius:999px; font-size:.68rem; font-weight:800; text-transform:uppercase }
        .dz-badge.safe { color:#c9f67a; background:rgba(145,197,43,.14); border:1px solid rgba(182,233,79,.25) }
        .dz-badge.pc { color:#ffc08f; background:rgba(217,119,56,.14); border:1px solid rgba(217,119,56,.3) }
        .dz-server-settings { border-color:rgba(56,189,248,.35); background:linear-gradient(135deg,rgba(14,116,144,.18),rgba(17,24,19,.95)) }
        .dz-server-badge { color:#8bdcff; background:rgba(14,165,233,.14); border:1px solid rgba(56,189,248,.35) }
        .dz-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; padding:1rem }
        .dz-field { padding:1rem; border:1px solid rgba(190,209,175,.12); border-radius:.35rem; background:#0d130f }
        .dz-field-top { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.75rem }
        .dz-field input[type=number] { width:7rem; border:1px solid rgba(190,209,175,.2); border-radius:.25rem; padding:.45rem .55rem; color:#edf2e9; background:#090d0a }
        .dz-field input[type=range] { width:100%; accent-color:#a3e635 }
        .dz-raw { width:100%; min-height:65vh; resize:vertical; border:1px solid rgba(190,209,175,.2); border-radius:.35rem; padding:1rem; color:#dce8d5; background:#070b08; font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; tab-size:4 }
        .dz-action { display:inline-flex; align-items:center; justify-content:center; padding:.7rem 1.15rem; border-radius:.25rem; color:#17210d; background:#b6e94f; font-weight:800; text-transform:uppercase; letter-spacing:.03em }
        .dz-summary { flex:1; min-width:240px; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-file-select { min-width:280px; appearance:none; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.65rem 2.25rem .65rem .8rem; color:#edf2e9; background-color:#090d0a; background-image:linear-gradient(45deg,transparent 50%,#b6e94f 50%),linear-gradient(135deg,#b6e94f 50%,transparent 50%); background-position:calc(100% - 16px) 50%,calc(100% - 11px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat }
        .dz-file-buttons { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end }
        .dz-file-button { display:inline-flex; align-items:center; gap:.35rem; padding:.58rem .75rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; color:#cbd5c0; background:#090d0a; font-size:.78rem; font-weight:700; text-align:left }
        .dz-file-button:hover { border-color:#91c52b; color:#edf2e9 }
        .dz-file-button.active { border-color:#b6e94f; color:#17210d; background:#b6e94f }
        .dz-savebar { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; padding:1rem; border-top:1px solid rgba(182,233,79,.13); background:#111813 }
        .dz-warning { padding:.85rem 1rem; border-left:3px solid #d97738; color:#e8c8b3; background:rgba(217,119,56,.08) }
        .dz-error { margin-top:.4rem; color:#fb8b8b; font-size:.85rem }
        .dz-info { padding:.85rem 1rem; border-left:3px solid #91c52b; color:#dbeacb; background:rgba(145,197,43,.07) }
        .dz-secondary { display:inline-flex; align-items:center; justify-content:center; padding:.65rem 1rem; border:1px solid rgba(182,233,79,.25); border-radius:.25rem; color:#b6e94f; background:#151e17; font-weight:700 }
        .dz-add-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; padding:1rem }
        .dz-control label { display:block; margin-bottom:.35rem; color:#cbd5c0; font-size:.85rem; font-weight:700 }
        .dz-control input,.dz-control select { width:100%; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.65rem .75rem; color:#edf2e9; background:#090d0a }
        .dz-checks { display:flex; gap:.5rem; flex-wrap:wrap }
        .dz-check { display:flex; align-items:center; gap:.35rem; padding:.4rem .55rem; border:1px solid rgba(190,209,175,.14); border-radius:.25rem; color:#cbd5c0; background:#0d130f }
        @media(max-width:900px){
            .dz-editor-grid,.dz-fields,.dz-add-grid{grid-template-columns:1fr}
            .dz-editor-grid{gap:.75rem}
        }
        @media(max-width:640px){
            .fi-main{padding-inline:.65rem !important}
            .dz-heading-badge{margin-left:.2rem;padding:.2rem .4rem;font-size:.58rem}
            .dz-tabs{width:100%;gap:.35rem}
            .dz-tab{flex:1;padding:.6rem .45rem;font-size:.78rem;text-align:center}
            .dz-file-select{width:100%;min-width:0}
            .dz-file-buttons{width:100%;justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap;padding-bottom:.2rem}
            .dz-file-button{flex:0 0 auto;white-space:nowrap}
            .dz-list{max-height:50vh}
            .dz-fields,.dz-add-grid{padding:.65rem;gap:.65rem}
            .dz-field{padding:.75rem}
            .dz-field-top{align-items:flex-start;gap:.5rem}
            .dz-field input[type=number]{width:5.5rem}
            .dz-savebar{padding:.75rem;align-items:stretch}
            .dz-summary,.dz-action,.dz-secondary{width:100%;min-width:0}
            .dz-action,.dz-secondary{padding:.7rem .6rem}
        }
    </style>

    <div class="space-y-4">
        @if ($this->getRecord()->platform !== 'steam' && $detectedPlatform === 'steam')
            <div class="dz-warning">
                <strong>Detekovány PC-only prvky.</strong>
                Tento projekt je nastavený jako {{ ucfirst($this->getRecord()->platform) }}, ale konfigurace obsahuje:
                {{ implode(', ', $platformReasons) }}. Tyto části nemusí na konzoli fungovat.
            </div>
        @elseif ($platformWarnings !== [])
            <div class="dz-info">
                <strong>Konfigurace je konzolově kompatibilní.</strong>
                Nebyly nalezeny žádné PC-only prvky. Samotný běžný XML soubor ale nedokáže automaticky rozlišit PlayStation od Xboxu.
            </div>
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
            <div class="flex items-center gap-3 flex-wrap">
                <div class="dz-file-buttons" aria-label="Výběr konfigurace">
                    @foreach ($this->editableFiles() as $id => $label)
                        <button type="button" wire:click="selectRevision({{ $id }})" class="dz-file-button {{ (int) $revisionId === (int) $id ? 'active' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <span class="dz-muted">Aktuální revize #{{ $revisionNumber }}</span>
            </div>
        </div>

        @if ($mode === 'visual' && $visualSupported)
            @if ($visualKind === 'types')
            <div class="dz-editor-grid">
                <section class="dz-panel">
                    <div class="dz-panel-head">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <strong>Položky types.xml</strong>
                                <p class="dz-muted text-sm mt-1">Rozbal sekci nebo použij hledání. Nejvýše 200 výsledků.</p>
                            </div>
                            <button type="button" wire:click="openAddForm" class="dz-secondary">+ Nová položka</button>
                        </div>
                        <input wire:model.live.debounce.250ms="search" class="dz-search mt-3" placeholder="Hledat například AKM…">
                    </div>
                    <div class="dz-list">
                        @foreach ($this->groupedTypes() as $category => $entries)
                            <details wire:key="types-category-{{ $category }}" wire:ignore.self class="dz-group" {{ $search !== '' ? 'open' : '' }}>
                                <summary>
                                    <span>{{ $category }}</span>
                                    <small>{{ count($entries) }} položek</small>
                                </summary>
                                <div class="dz-group-body">
                                    @foreach ($entries as $entry)
                                        <button type="button" wire:click="selectType(@js($entry['name']))" class="dz-item {{ $selectedType === $entry['name'] ? 'active' : '' }}">
                                            <span>{{ $entry['name'] }}</span>
                                            <span class="dz-item-meta">
                                                <span class="dz-badge {{ $entry['pc_only'] ? 'pc' : 'safe' }}">
                                                    {{ $entry['pc_only'] ? 'PC only' : 'PS · Xbox · PC' }}
                                                </span>
                                                <small>{{ $entry['nominal'] }} ks</small>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>

                <section class="dz-panel">
                    @if ($showAddForm)
                        <div class="dz-panel-head">
                            <strong>Nová položka types.xml</strong>
                            <p class="dz-muted text-sm mt-1">Název se kontroluje bez ohledu na velikost písmen. Duplicitní položku nelze uložit.</p>
                        </div>
                        <div class="dz-add-grid">
                            <div class="dz-control">
                                <label>Název třídy</label>
                                <input wire:model="newTypeForm.name" list="dayz-class-names" placeholder="Například AKM">
                                <datalist id="dayz-class-names">
                                    @foreach ($typeEntries as $knownType)
                                        <option value="{{ $knownType['name'] }}">{{ $knownType['category'] }}</option>
                                    @endforeach
                                </datalist>
                                <small class="dz-muted">Vyber existující třídu ze seznamu, nebo zadej vlastní název z konfigurace.</small>
                                @error('newTypeForm.name') <div class="dz-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="dz-control">
                                <label>Kategorie</label>
                                <select wire:model="newTypeForm.category">
                                    @foreach (['weapons' => 'Zbraně', 'food' => 'Jídlo', 'medical' => 'Zdravotnictví', 'tools' => 'Nástroje', 'clothes' => 'Oblečení', 'containers' => 'Kontejnery', 'vehicles' => 'Vozidla', 'other' => 'Ostatní'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @foreach ([
                                'nominal' => 'Cílové množství',
                                'min' => 'Minimum',
                                'lifetime' => 'Životnost',
                                'restock' => 'Doplnění',
                                'quantmin' => 'Min. naplnění',
                                'quantmax' => 'Max. naplnění',
                                'cost' => 'Priorita',
                            ] as $field => $label)
                                <div class="dz-control">
                                    <label>{{ $label }}</label>
                                    <input type="number" wire:model="newTypeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}">
                                    @error("newTypeForm.$field") <div class="dz-error">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="px-4 pb-4">
                            <strong>Oblasti výskytu</strong>
                            <div class="dz-checks mt-2">
                                @foreach (['Military', 'Hunting', 'Police', 'Medic', 'Town', 'Village', 'Farm', 'Industrial', 'Coast'] as $usage)
                                    <label class="dz-check">
                                        <input type="checkbox" wire:model="newTypeForm.usages" value="{{ $usage }}">
                                        {{ $usage }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="dz-savebar">
                            <button type="button" wire:click="addType" wire:loading.attr="disabled" class="dz-action">Zkontrolovat a přidat</button>
                        </div>
                    @elseif ($selectedType)
                        <div class="dz-panel-head">
                            @php
                                $selectedEntry = collect($typeEntries)->firstWhere('name', $selectedType);
                            @endphp
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $selectedType }}</strong>
                                    <p class="dz-muted text-sm mt-1">
                                        Kategorie: {{ $selectedEntry['category'] ?? 'other' }}
                                        @if (!empty($selectedEntry['usages'])) · Výskyt: {{ implode(', ', $selectedEntry['usages']) }} @endif
                                    </p>
                                </div>
                                <span class="dz-badge {{ ($selectedEntry['pc_only'] ?? false) ? 'pc' : 'safe' }}">
                                    {{ ($selectedEntry['pc_only'] ?? false) ? 'Pouze PC' : 'PlayStation · Xbox · PC' }}
                                </span>
                            </div>
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
            @elseif ($visualKind === 'weather')
                @php
                    $weatherSections = [
                        'overcast' => ['Oblačnost', 0, 1, 0.01],
                        'fog' => ['Mlha', 0, 1, 0.01],
                        'rain' => ['Déšť', 0, 1, 0.01],
                        'windMagnitude' => ['Síla větru (m/s)', 0, 50, 0.1],
                        'windDirection' => ['Směr větru (radiány)', -3.14, 3.14, 0.01],
                        'snowfall' => ['Sněžení', 0, 1, 0.01],
                    ];
                @endphp
                <section class="dz-panel dz-server-settings">
                    <div class="dz-panel-head">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <strong>Serverová nastavení · cfgweather.xml</strong>
                                <p class="dz-muted text-sm mt-1">Počasí je samostatná konfigurace. Vyberte ji nahoře v seznamu souborů/revizí.</p>
                            </div>
                            <span class="dz-badge dz-server-badge">PS · Xbox · PC</span>
                            <div class="dz-checks">
                                <label class="dz-check"><input type="checkbox" wire:model="weatherForm.enable"> Aktivovat soubor</label>
                                <label class="dz-check"><input type="checkbox" wire:model="weatherForm.reset"> Reset počasí po restartu</label>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        @foreach ($weatherSections as $section => [$label, $rangeMin, $rangeMax, $step])
                            <details class="dz-group" open>
                                <summary>
                                    <span>{{ $label }}</span>
                                    <span class="dz-badge safe">PS · Xbox · PC</span>
                                </summary>
                                <div class="dz-fields">
                                    @foreach ([
                                        'current_actual' => ['Aktuální intenzita', $rangeMin, $rangeMax, $step, 'Aktuální síla jevu; 0 znamená vypnuto a 1 maximální intenzitu.'],
                                        'current_time' => ['Čas přechodu (min)', 0, 1440, 1, 'Za kolik minut se začne měnit na novou hodnotu.'],
                                        'current_duration' => ['Doba trvání (min)', 1, 1440, 1, 'Jak dlouho přibližně vydrží aktuální stav.'],
                                        'limits_min' => ['Minimální intenzita', $rangeMin, $rangeMax, $step, 'Nejnižší intenzita, kterou hra náhodně nastaví.'],
                                        'limits_max' => ['Maximální intenzita', $rangeMin, $rangeMax, $step, 'Nejvyšší intenzita, kterou hra náhodně nastaví.'],
                                        'timelimits_min' => ['Min. čas změny (min)', 0, 1440, 1, 'Nejkratší prodleva mezi změnami počasí.'],
                                        'timelimits_max' => ['Max. čas změny (min)', 1, 1440, 1, 'Nejdelší prodleva mezi změnami počasí.'],
                                        'changelimits_min' => ['Minimální změna', $rangeMin, $rangeMax, $step, 'Nejmenší velikost náhodné změny.'],
                                        'changelimits_max' => ['Maximální změna', $rangeMin, $rangeMax, $step, 'Největší velikost náhodné změny.'],
                                    ] as $suffix => [$fieldLabel, $min, $max, $fieldStep, $help])
                                        @php
                                            $weatherKey = $section . '_' . $suffix;
                                        @endphp
                                        <label class="dz-field">
                                            <span class="dz-field-top">
                                                <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }} (pro {{ strtolower($label) }}).</small></span>
                                                <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                            </span>
                                            <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                            @error("weatherForm.$weatherKey") <div class="dz-error">{{ $message }}</div> @enderror
                                        </label>
                                    @endforeach

                                    @if (in_array($section, ['rain', 'snowfall'], true))
                                        @foreach ([
                                            'thresholds_min' => ['Min. oblačnost pro srážky', 0, 1, 0.01, 'Pod touto oblačností se déšť nebo sníh nespustí.'],
                                            'thresholds_max' => ['Max. oblačnost pro srážky', 0, 1, 0.01, 'Nad touto oblačností se srážky mohou objevit.'],
                                            'thresholds_end' => ['Čas ukončení srážek (min)', 0, 1440, 1, 'Jak dlouho po poklesu podmínky doznívají srážky.'],
                                        ] as $suffix => [$fieldLabel, $min, $max, $fieldStep, $help])
                                            @php
                                                $weatherKey = $section . '_' . $suffix;
                                            @endphp
                                            <label class="dz-field">
                                                <span class="dz-field-top">
                                                    <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                                    <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                                </span>
                                                <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </details>
                        @endforeach

                        <details class="dz-group" open>
                            <summary><span>Bouřka a blesky</span><span class="dz-badge safe">PS · Xbox · PC</span></summary>
                            <div class="dz-fields">
                                @foreach ([
                                    'storm_density' => ['Hustota blesků', 0, 1, 0.01, 'Jak často se při bouřce generují blesky.'],
                                    'storm_threshold' => ['Práh oblačnosti', 0, 1, 0.01, 'Minimální oblačnost potřebná pro bouřku.'],
                                    'storm_timeout' => ['Prodleva mezi blesky (min)', 1, 60, 1, 'Minimální čas mezi dvěma blesky.'],
                                ] as $weatherKey => [$fieldLabel, $min, $max, $fieldStep, $help])
                                    <label class="dz-field">
                                        <span class="dz-field-top">
                                            <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                            <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                        </span>
                                        <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    <div class="dz-savebar">
                        <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny počasí">
                        <button type="button" wire:click="saveWeather" wire:loading.attr="disabled" class="dz-action">Uložit počasí jako revizi</button>
                    </div>
                </section>
            @endif
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
