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
        .dz-field[data-tooltip] { position:relative }
        .dz-field[data-tooltip]::after { content:attr(data-tooltip); position:absolute; z-index:20; left:.75rem; bottom:calc(100% + .35rem); max-width:28rem; padding:.45rem .6rem; border:1px solid rgba(182,233,79,.3); border-radius:.25rem; color:#e7f4dc; background:#0a100c; box-shadow:0 8px 24px rgba(0,0,0,.45); font:12px/1.35 ui-monospace,SFMono-Regular,Consolas,monospace; white-space:normal; opacity:0; pointer-events:none; transform:translateY(.25rem); transition:opacity .15s,transform .15s }
        .dz-field[data-tooltip]:hover::after,.dz-field[data-tooltip]:focus-within::after { opacity:1; transform:translateY(0) }
        .dz-raw { width:100%; min-height:65vh; resize:vertical; border:1px solid rgba(190,209,175,.2); border-radius:.35rem; padding:1rem; color:#dce8d5; background:#070b08; font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; tab-size:4 }
        .dz-action { display:inline-flex; align-items:center; justify-content:center; padding:.7rem 1.15rem; border-radius:.25rem; color:#17210d; background:#b6e94f; font-weight:800; text-transform:uppercase; letter-spacing:.03em }
        .dz-summary { flex:1; min-width:240px; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-file-select { min-width:280px; appearance:none; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.65rem 2.25rem .65rem .8rem; color:#edf2e9; background-color:#090d0a; background-image:linear-gradient(45deg,transparent 50%,#b6e94f 50%),linear-gradient(135deg,#b6e94f 50%,transparent 50%); background-position:calc(100% - 16px) 50%,calc(100% - 11px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat }
        .dz-file-buttons { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end }
        .dz-file-button { display:inline-flex; align-items:center; gap:.35rem; padding:.58rem .75rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; color:#cbd5c0; background:#090d0a; font-size:.78rem; font-weight:700; text-align:left }
        .dz-file-button:hover { border-color:#91c52b; color:#edf2e9 }
        .dz-file-button.active { border-color:#b6e94f; color:#17210d; background:#b6e94f }
        .dz-file-menu { position:relative; width:min(28rem,100%); z-index:30 }
        .dz-file-menu summary { display:flex; align-items:center; justify-content:space-between; gap:.75rem; cursor:pointer; list-style:none; padding:.65rem .8rem; border:1px solid rgba(182,233,79,.35); border-radius:.3rem; color:#edf2e9; background:#111813; font-weight:800 }
        .dz-file-menu summary::-webkit-details-marker { display:none }
        .dz-file-menu summary::after { content:'▾'; color:#b6e94f }
        .dz-file-menu[open] summary::after { content:'▴' }
        .dz-file-menu-list { position:absolute; right:0; top:calc(100% + .4rem); width:min(32rem,calc(100vw - 2rem)); max-height:24rem; overflow:auto; padding:.3rem; border:1px solid rgba(182,233,79,.3); border-radius:.5rem; background:#0d140f; box-shadow:0 16px 40px rgba(0,0,0,.55) }
        .dz-file-option { display:block; width:100%; padding:.55rem .65rem; border:0; border-bottom:1px solid rgba(190,209,175,.1); color:#dce8d5; background:transparent; text-align:left; cursor:pointer }
        .dz-file-option:last-child { border-bottom:0 }
        .dz-file-option:hover { color:#17210d; background:#b6e94f }
        .dz-file-option.active { color:#e7f7d2; background:linear-gradient(90deg,rgba(182,233,79,.24),rgba(182,233,79,.1)); box-shadow:inset 3px 0 #b6e94f }
        .dz-file-option-title { display:block; font-size:.95rem; font-weight:800; line-height:1.25; color:#e7f7d2 }
        .dz-file-option-revision { display:block; margin-top:.12rem; color:#b8ed55; font-size:.72rem; font-weight:800; letter-spacing:.03em }
        .dz-file-option-description { display:block; margin-top:.15rem; color:#aab6a4; font-size:.7rem; line-height:1.25; font-weight:400 }
        .dz-file-option:hover .dz-file-option-description { color:#31451f }
        .dz-file-option.active .dz-file-option-title { color:#f3ffd9 }
        .dz-file-option.active .dz-file-option-revision { color:#b6e94f }
        .dz-file-option.active .dz-file-option-description { color:#c4d6b8 }
        .dz-modal-backdrop { position:fixed; inset:0; z-index:60; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(0,0,0,.72) }
        .dz-modal { width:min(48rem,100%); max-height:85vh; overflow:hidden; border:1px solid rgba(182,233,79,.35); border-radius:.45rem; background:#111813; box-shadow:0 24px 80px rgba(0,0,0,.65) }
        .dz-modal-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem; border-bottom:1px solid rgba(182,233,79,.15) }
        .dz-modal-body { max-height:65vh; overflow:auto; padding:1rem }
        .dz-picker-categories { display:flex; gap:.45rem; flex-wrap:wrap; margin-bottom:1rem }
        .dz-picker-category { padding:.5rem .7rem; border:1px solid rgba(190,209,175,.18); border-radius:.25rem; color:#cbd5c0; background:#0d130f; cursor:pointer }
        .dz-picker-category.active { color:#17210d; background:#b6e94f; border-color:#b6e94f }
        .dz-picker-items { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.35rem }
        .dz-picker-item { display:flex; align-items:center; justify-content:space-between; gap:.5rem; min-height:2.65rem; padding:.45rem .6rem; border:1px solid rgba(190,209,175,.14); border-radius:.25rem; color:#dce8d5; background:#0d130f; text-align:left; cursor:pointer }
        .dz-picker-item small { color:#aab6a4; white-space:nowrap }
        .dz-picker-item:hover { border-color:#b6e94f; color:#17210d; background:#b6e94f }
        .dz-picker-loading { padding:.75rem; color:#b6e94f; text-align:center }
        .dz-picker-search { width:100%; margin-bottom:1rem; padding:.7rem .8rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; color:#edf2e9; background:#090d0a }
        .dz-picker-more { display:block; width:100%; margin-top:1rem; padding:.7rem; border:1px solid rgba(182,233,79,.25); border-radius:.25rem; color:#b6e94f; background:#151e17; font-weight:700 }
        @media(max-width:640px){.dz-picker-items{grid-template-columns:1fr}.dz-picker-item{min-height:2.4rem}}
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
                <details class="dz-file-menu">
                    <summary>{{ $currentFilename ?: 'Vybrat konfiguraci' }} · revize #{{ $revisionNumber }}</summary>
                    <div class="dz-file-menu-list" aria-label="Výběr konfigurace">
                    @foreach ($this->editableFiles() as $id => $label)
                        @php
                            [$fileLabel, $revisionLabel] = array_pad(explode(' · ', $label, 2), 2, '');
                        @endphp
                        <button type="button" wire:click="selectRevision({{ $id }})" class="dz-file-option {{ (int) $revisionId === (int) $id ? 'active' : '' }}">
                            <span class="dz-file-option-title">{{ $fileLabel }}</span>
                            <span class="dz-file-option-revision">{{ $revisionLabel }}</span>
                            <span class="dz-file-option-description">{{ $this->descriptionForFilename($fileLabel) }}</span>
                        </button>
                    @endforeach
                    </div>
                </details>
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
                                <div class="flex gap-2">
                                    <input wire:model="newTypeForm.name" placeholder="Například AKM">
                                    <button type="button" wire:click="openClassPicker" class="dz-secondary">Vybrat z katalogu</button>
                                </div>
                                <small class="dz-muted">Vyber existující třídu z katalogu, nebo zadej vlastní název z konfigurace.</small>
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
                                <label class="dz-field" title="Raw: &lt;{{ $field }}&gt;{{ $typeForm[$field] ?? '' }}&lt;/{{ $field }}&gt;">
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
            @elseif ($visualKind === 'server')
                @php
                $cfgDescriptions = ['hostname'=>'Název serveru zobrazovaný v prohlížeči serverů.','maxPlayers'=>'Maximální počet současně připojených hráčů.','password'=>'Heslo potřebné pro připojení na server.','passwordAdmin'=>'Heslo pro administrátorské příkazy ve hře.','enableWhitelist'=>'Zapíná kontrolu UID v whitelist.txt.','verifySignatures'=>'Ověřuje .pbo soubory proti podpisům .bisign.','forceSameBuild'=>'Povolí připojení pouze klientům se stejnou revizí hry.','disableVoN'=>'Vypíná vestavěnou hlasovou komunikaci.','vonCodecQuality'=>'Kvalita hlasového kodeku; vyšší hodnota znamená lepší zvuk.','disable3rdPerson'=>'Zakáže hráčům pohled ze třetí osoby.','disableCrosshair'=>'Zakáže zaměřovací kříž u střelných zbraní.','disablePersonalLight'=>'Zakáže osobní světlo, které hráč používá v noci.','lightingConfig'=>'Volba profilu osvětlení mapy.','serverTime'=>'Čas, kterým server začne po restartu; SystemTime použije čas stroje.','serverTimeAcceleration'=>'Násobek rychlosti plynutí denního času.','serverNightTimeAcceleration'=>'Násobek rychlosti plynutí noci (násobí se i denní akcelerací).','serverTimePersistent'=>'Uloží čas do persistence a použije ho po dalším restartu.','guaranteedUpdates'=>'Zapíná garantované síťové aktualizace objektů.','loginQueueConcurrentPlayers'=>'Kolik připojení se zpracovává současně ve frontě.','loginQueueMaxPlayers'=>'Maximální počet hráčů čekajících na připojení.','instanceId'=>'ID instance určující složku persistence při více serverech na stroji.','storeHouseStateDisabled'=>'Vypíná ukládání stavu domů a dveří do persistence.','storageAutoFix'=>'Při poškození persistence vytvoří prázdný náhradní soubor.','template'=>'Mission template/mapa, například dayzOffline.chernarusplus.','steamport'=>'UDP port serveru pro Steam komunikaci.','steamqueryport'=>'Port pro dotazy serverového prohlížeče ve Steamu.','adminLogPlayerHitsOnly'=>'Do admin logu zapisuje pouze zásahy hráčů.','adminLogPlacement'=>'Loguje umisťování předmětů a staveb.','adminLogBuildActions'=>'Loguje stavební akce hráčů.','adminLogPlayerList'=>'Loguje seznam připojených hráčů.','disableBaseDamage'=>'Zakáže poškození základů a staveb.','disableContainerDamage'=>'Zakáže poškození kontejnerů a jejich obsahu.','disableRespawnDialog'=>'Zakáže dialog pro volbu respawnu.','disableRespawnInUnconsciousness'=>'Zakáže respawn během bezvědomí.','enableCfgGameplayFile'=>'Načte doplňkový soubor cfggameplay.json.','networkObjectBatchSend'=>'Počet síťových objektů odesílaných v jedné dávce.','networkObjectBatchCompute'=>'Počet objektů zpracovaných při výpočtu síťové dávky.','description'=>'Textový popis serveru zobrazovaný v prohlížeči.'];
                @endphp
                <section class="dz-editor-card"><h3>Serverová nastavení · serverDZ.cfg</h3><p class="dz-muted">Upravujte hodnoty serveru bez ručního psaní CFG syntaxe. Hesla jsou skrytá; prázdné pole zachová původní hodnotu.</p><div class="dz-fields-grid">
                    @foreach ($serverConfig as $key => $value)
                        @php
                            $secret = in_array(strtolower($key), ['password', 'passwordadmin'], true);
                        @endphp
                        <label class="dz-field"><span>{{ $key }}</span><input type="{{ $secret ? 'password' : 'text' }}" autocomplete="{{ $secret ? 'new-password' : 'off' }}" placeholder="{{ $secret ? 'Ponechte prázdné pro zachování' : '' }}" wire:model.defer="serverConfig.{{ $key }}"><small>{{ $cfgDescriptions[$key] ?? 'Popis této volby není v aktuální dokumentaci DayZ dostupný; podrobnosti ověřte v komentáři raw konfigurace.' }}</small><small>Raw: {{ $key }} = {{ $secret ? '•••••••• (skryto)' : $value }};</small></label>
                    @endforeach
                </div><button type="button" wire:click="saveServerConfig" class="dz-save-button">Uložit serverDZ.cfg jako novou revizi</button></section>
            @elseif ($visualKind === 'whitelist')
                @php
                $cfgDescriptions = [
                    'hostname' => 'Název serveru zobrazovaný v seznamu serverů.',
                    'maxPlayers' => 'Maximální počet současně připojených hráčů.',
                    'password' => 'Heslo pro připojení na server; prázdné znamená bez hesla.',
                    'passwordAdmin' => 'Heslo pro administrátorské příkazy ve hře.',
                    'enableWhitelist' => 'Zapíná nebo vypíná kontrolu whitelistu.',
                    'verifySignatures' => 'Kontrola podpisů modů při připojení.',
                    'forceSameBuild' => 'Vyžaduje stejnou verzi hry jako server.',
                    'serverTime' => 'Výchozí čas serveru při startu.',
                    'serverTimeAcceleration' => 'Zrychlení průběhu dne.',
                    'serverNightTimeAcceleration' => 'Zrychlení průběhu noci.',
                    'template' => 'Název mapového template/economy profilu.',
                ];
                @endphp
                <section class="dz-editor-card"><h3>Whitelist hráčů · whitelist.txt</h3><p class="dz-muted">Každý řádek obsahuje jedno Steam/Xbox/PlayStation UID. Duplicitní a neplatné hodnoty se nepřidají.</p>
                    <div class="dz-whitelist-add"><input wire:model.defer="newWhitelistUid" placeholder="UID hráče" autocomplete="off"><button type="button" wire:click="addWhitelistEntry" class="dz-save-button">Přidat hráče</button></div>
                    <div class="dz-whitelist-list">@forelse($whitelistEntries as $index => $uid)<div class="dz-whitelist-row"><code>{{ $uid }}</code><button type="button" wire:click="removeWhitelistEntry({{ $index }})" class="dz-danger">Odebrat</button></div>@empty<p class="dz-muted">Whitelist je zatím prázdný.</p>@endforelse</div>
                    <button type="button" wire:click="saveWhitelist" class="dz-save-button">Uložit whitelist.txt jako novou revizi</button>
                </section>
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
                                        <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
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
                                            <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
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
                                    <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
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
            @elseif ($visualKind === 'json')
                <section class="dz-panel dz-server-settings">
                    <div class="dz-panel-head"><strong>{{ $currentFilename }} · vizuální editor</strong><p class="dz-muted text-sm mt-1">Nastavení je rozdělené podle sekcí JSON. Pole jsou odvozena přímo z importovaného souboru.</p></div>
                    <div class="p-3">
                        @foreach (collect($jsonFields)->groupBy('section') as $section => $fields)
                            <details class="dz-group" open>
                                <summary><span>{{ $section }}</span><span class="dz-badge dz-server-badge">{{ count($fields) }} nastavení</span></summary>
                                <div class="dz-fields">
                                    @foreach ($fields as $field)
                                        <label class="dz-field" data-tooltip="Raw JSON: {{ $field['path'] }}">
                                            <span class="dz-field-top"><span><strong>{{ $field['label'] }}</strong><br><small class="dz-muted">{{ $field['path'] }}</small></span>
                                                @if ($field['type'] === 'boolean')
                                                    <input type="checkbox" wire:model="jsonValues.{{ $field['path'] }}">
                                                @elseif ($field['type'] === 'number')
                                                    <input type="number" wire:model="jsonValues.{{ $field['path'] }}">
                                                @else
                                                    <input type="text" wire:model="jsonValues.{{ $field['path'] }}">
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                    <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny JSON konfigurace"><button type="button" wire:click="saveJson" wire:loading.attr="disabled" class="dz-action">Validovat a uložit JSON revizi</button></div>
                </section>
            @endif
        @elseif ($visualKind === 'xml')
            <section class="dz-panel dz-server-settings">
                <div class="dz-panel-head"><strong>{{ $currentFilename }} · vizuální editor</strong><p class="dz-muted text-sm mt-1">Parametry XML jsou rozdělené podle sekcí a u každého pole je uveden raw XPath zápis.</p></div>
                <div class="p-3">
                    @foreach (collect($xmlFields)->groupBy('section') as $section => $fields)
                        <details class="dz-group" open><summary><span>{{ $section }}</span><span class="dz-badge dz-server-badge">{{ count($fields) }} parametrů</span></summary>
                            <div class="dz-fields">
                                @foreach ($fields as $field)
                                    <label class="dz-field" data-tooltip="Raw XML: {{ $field['raw'] }}">
                                        <span class="dz-field-top"><span><strong>{{ $field['label'] }}</strong><br><small class="dz-muted">{{ $field['raw'] }}</small></span><input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" wire:model="xmlValues.{{ $field['path'] }}"></span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
                <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny XML konfigurace"><button type="button" wire:click="saveXml" wire:loading.attr="disabled" class="dz-action">Validovat a uložit XML revizi</button></div>
            </section>
        @else
            <section class="dz-panel">
                <div class="dz-panel-head">
                    <strong>{{ $currentFilename ?: 'Konfigurace serveru' }}</strong>
                    <p class="dz-muted text-sm mt-1">{{ $this->configurationDescription() }}</p>
                    <span class="dz-badge dz-server-badge mt-2">Serverová konfigurace · Raw XML / JSON</span>
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
    @if ($showClassPicker)
        <div class="dz-modal-backdrop" wire:click.self="closeClassPicker">
            <section class="dz-modal" role="dialog" aria-modal="true" aria-label="Výběr položky">
                <div class="dz-modal-head">
                    <div><strong>Vyber položku z katalogu</strong><p class="dz-muted text-sm mt-1">Katalog obsahuje položky načtené z aktuálního types.xml.</p></div>
                    <button type="button" wire:click="closeClassPicker" class="dz-secondary">Zavřít</button>
                </div>
                <div class="dz-modal-body" wire:loading.class="opacity-60" wire:target="classPickerSearch, classPickerCategory, loadMoreCatalog, chooseClass">
                    <div wire:loading wire:target="classPickerSearch, classPickerCategory, loadMoreCatalog, chooseClass" class="dz-picker-loading">Načítám katalog…</div>
                    <input wire:model.live.debounce.200ms="classPickerSearch" class="dz-picker-search" placeholder="Hledat v celém katalogu, například PlateCarrier nebo AKM…">
                    <div class="dz-picker-categories">
                        @foreach ($this->pickerCategories() as $category)
                            <button type="button" wire:click="$set('classPickerCategory', @js($category))" class="dz-picker-category {{ $classPickerCategory === $category ? 'active' : '' }}">{{ strtoupper($category) }}</button>
                        @endforeach
                    </div>
                    <div class="dz-picker-items">
                        @forelse ($this->pickerEntries() as $entry)
                            <button type="button" wire:click="chooseClass(@js($entry['name']), @js($entry['category']))" class="dz-picker-item"><strong>{{ $entry['name'] }}</strong><br><small>{{ $entry['nominal'] }} ks · {{ $entry['category'] }}</small></button>
                        @empty
                            <p class="dz-muted">V této kategorii nejsou v aktuálním souboru žádné položky.</p>
                        @endforelse
                    </div>
                    @if (count($this->pickerEntries()) >= $classPickerLimit)
                        <button type="button" wire:click="loadMoreCatalog" class="dz-picker-more">Načíst dalších 60 položek</button>
                    @endif
                </div>
            </section>
        </div>
    @endif
</x-filament-panels::page>
