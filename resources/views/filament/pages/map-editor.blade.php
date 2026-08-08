<x-filament-panels::page>
    <div class="dz-map-page">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        @if (! $projects)
            <section class="dz-empty-state dz-map-page-empty">
                <x-filament::icon icon="heroicon-o-map" />
                <strong>Mapa zatím nemá k čemu patřit</strong>
                <span>Nejprve založte server. Potom do něj nahrajete mapové soubory a jejich vrstvy se zobrazí zde.</span>
                <a href="{{ url('/admin/projects/create') }}">Založit první server →</a>
            </section>
        @else
        <div id="dz-point-modal" class="dz-point-modal" hidden>
            <div class="dz-point-modal-card">
                <button type="button" class="dz-point-close" aria-label="Zavřít">×</button>
                <h3>Přidat bod do mapy</h3>
                <p class="dz-muted">Vyberte typ bodu, který chcete umístit na mapu.</p>
                <div class="dz-point-options">
                    <button data-point="loot">Loot / loot skupina</button>
                    <button data-point="heli">Heli crash</button>
                    <button data-point="convoy">Konvoj</button>
                    <button data-point="dynamic">Dynamický event</button>
                    <button data-point="contaminated">Kontaminovaná zóna</button>
                    <button data-point="infected">Zóna nakažených</button>
                    <button data-point="animal">Stádo zvířat</button>
                    <button data-point="vehicle">Spawn vozidla</button>
                    <button data-point="player">Spawn hráče</button>
                    <button data-point="territory">Území / základna</button>
                    <button data-point="aerial">Letecký event</button>
                    <button data-point="custom">Vlastní bod</button>
                </div>
                <p class="dz-muted dz-point-new-event-hint">
                    Event, vozidlo, heli crash ani konvoj (ani statický kontejner typu barel/bedna) ještě neexistují v <code>events.xml</code>?
                    <button type="button" class="dz-secondary" wire:click="openAddEventModal('')" onclick="document.getElementById('dz-point-modal').hidden = true;">+ Přidat nový event do events.xml</button>
                    Po vytvoření se objeví v seznamu u „Dynamický event" a dalších typů níže.
                </p>
                <div id="dz-event-catalog" class="dz-event-catalog" hidden>
                    <label>Možnosti pro vybraný typ</label>
                    <select></select>
                    <div class="dz-point-target-status"></div>
                    <input class="dz-point-name" placeholder="Vlastní název (volitelné)">
                    <div class="dz-point-fields"></div>
                    <small class="dz-point-help"></small>
                    <div class="dz-event-settings" hidden></div>
                    <div class="dz-related-files" hidden></div>
                    <p class="dz-map-feedback" hidden></p>
                    <button type="button" class="dz-point-confirm">Umístit bod a vytvořit revizi</button>
                </div>
            </div>
        </div>
        <div id="dz-edit-point-modal" class="dz-point-modal" hidden>
            <div class="dz-point-modal-card dz-edit-point-card">
                <button type="button" class="dz-point-close" aria-label="Zavřít">×</button>
                <p class="dz-eyebrow">ÚPRAVA MAPOVÉ KONFIGURACE</p>
                <h3>Upravit bod</h3>
                <p class="dz-edit-point-context dz-muted"></p>
                <p class="dz-edit-point-contents-badge" hidden></p>
                <div class="dz-edit-coordinate-grid">
                    <label>Souřadnice X<input class="dz-edit-x" type="number" min="0" max="15360" step="0.001"></label>
                    <label>Souřadnice Z<input class="dz-edit-z" type="number" min="0" max="15360" step="0.001"></label>
                </div>
                <div class="dz-edit-point-fields"></div>
                <p class="dz-muted">Povolený rozsah Chernarus: 0–15 360. Uložení vždy vytvoří novou revizi původního souboru.</p>
                <p class="dz-map-feedback" hidden></p>
                <div class="dz-edit-actions">
                    <button type="button" class="dz-danger dz-edit-delete">Smazat bod</button>
                    <button type="button" class="dz-point-confirm dz-edit-save">Uložit jako novou revizi</button>
                </div>
            </div>
        </div>
        <div id="dz-system-dialog" class="dz-point-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dz-system-dialog-title">
            <div class="dz-point-modal-card dz-system-dialog-card">
                <p class="dz-eyebrow">POTVRZENÍ ZMĚNY</p>
                <h3 id="dz-system-dialog-title">Potvrdit akci</h3>
                <p class="dz-system-dialog-message"></p>
                <div class="dz-system-dialog-actions">
                    <button type="button" class="dz-dialog-cancel">Zrušit</button>
                    <button type="button" class="dz-dialog-confirm">Potvrdit</button>
                </div>
            </div>
        </div>
        <div id="dz-raw-modal" class="dz-point-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dz-raw-modal-title">
            <div class="dz-point-modal-card dz-map-raw-card">
                <button type="button" class="dz-point-close" aria-label="Zavřít">×</button>
                <div class="dz-map-raw-heading">
                    <div>
                        <p class="dz-eyebrow">RAW DATA · AKTUÁLNÍ REVIZE</p>
                        <h3 id="dz-raw-modal-title">Načítání souboru…</h3>
                    </div>
                    <button type="button" class="dz-map-raw-copy">Kopírovat</button>
                </div>
                <p class="dz-map-raw-status dz-muted">Načítám obsah revize…</p>
                <pre class="dz-map-raw-content" tabindex="0"><code></code></pre>
            </div>
        </div>
        <div id="dz-cleanup-modal" class="dz-point-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dz-cleanup-modal-title">
            <div class="dz-point-modal-card">
                <button type="button" class="dz-point-close" aria-label="Zavřít">×</button>
                <p class="dz-eyebrow">HROMADNÉ ODSTRANĚNÍ BODŮ</p>
                <h3 id="dz-cleanup-modal-title">Vybrat skupiny k odstranění</h3>
                <p class="dz-muted">Zvol jednu nebo víc skupin. Odstraní se jen vybrané body/pozice, ostatní parametry souboru zůstanou a vznikne jedna nová revize.</p>
                <div class="dz-cleanup-list"></div>
                <p class="dz-map-feedback" hidden></p>
                <div class="dz-edit-actions">
                    <button type="button" class="dz-cleanup-cancel dz-secondary">Zrušit</button>
                    <button type="button" class="dz-point-confirm dz-cleanup-confirm">Odstranit vybrané</button>
                </div>
            </div>
        </div>
        <div class="dz-map-intro">
            <b>Co tady můžeš upravovat:</b>
            @foreach ($this->mapCategoryIntro() as $category)
                <span class="dz-map-intro-chip" title="{{ $category['help'] }}">{{ $category['label'] }}</span>
            @endforeach
            <small class="dz-muted">Klikni na mapu (<code>Ctrl</code>) a vyber typ.</small>
        </div>
        <div class="dz-map-toolbar">
            <a class="dz-map-upload-button" href="{{ url('/admin/configuration-import?area=map&project='.$projectId) }}">+ Přidat mapový soubor</a>
            <button type="button" class="dz-secondary" wire:click="checkSpawnEventLinks" wire:loading.attr="disabled" wire:target="checkSpawnEventLinks">
                <span wire:loading.remove wire:target="checkSpawnEventLinks">Zkontrolovat vazby spawnů</span>
                <span wire:loading wire:target="checkSpawnEventLinks">Kontroluji vazby…</span>
            </button>
            <button type="button" class="dz-secondary dz-map-fullscreen-toggle" aria-pressed="false">Na celou obrazovku</button>
            <details class="dz-map-help">
                <summary>Jak mapu číst?</summary>
                <div>
                    Kruhy z <code>cfgplayerspawnpoints.xml</code> jsou oblasti, ve kterých server teprve hledá vhodný povrch.
                    Nejde o přesné místo spawnu. Prototypové soubory se na mapu nekreslí, protože jejich souřadnice nejsou světové X/Z.
                    Každý soubor nebo ZIP balík nahraný přes „Přidat mapový soubor“ dostane vlastní revizi.
                </div>
            </details>
        </div>
        @php $undeployedSources = collect($mapSources)->filter(fn ($source) => $source['uploaded'] && $source['undeployed'])->values(); @endphp
        @if ($undeployedSources->count())
            <div class="dz-map-alert">
                <div class="dz-map-alert-row">
                    <strong>{{ $undeployedSources->count() }} soubor{{ $undeployedSources->count() > 1 ? 'y' : '' }} není nahráno na server.</strong>
                    @if ($hasFtpConnection)
                        {{-- wire:ignore: the bulk push loops with awaited per-file Livewire calls, each of
                             which re-renders the page (loadMapSources() shrinks $undeployedSources) — without
                             this the progress UI would get reset/removed mid-loop by Livewire's own morph. --}}
                        <div wire:ignore x-data="{
                            uploading: false, done: 0,
                            ids: @js($undeployedSources->pluck('revision_id')->all()),
                            total: {{ $undeployedSources->count() }},
                        }">
                            <button type="button" class="dz-action" x-show="!uploading" x-on:click="
                                dzConfirm('Nahrát všech ' + total + ' nenahraných souborů přímo na živý server přes FTP? Přepíše odpovídající soubory na serveru.').then(async (ok) => {
                                    if (!ok) return;
                                    uploading = true; done = 0;
                                    for (const id of ids) {
                                        try { await $wire.pushSourceToFtp(id); } catch (e) {}
                                        done++;
                                    }
                                })
                            ">Nahrát vše na FTP</button>
                            <div class="dz-upload-progress" x-show="uploading" x-cloak>
                                <div class="dz-upload-progress-track"><div class="dz-upload-progress-fill" x-bind:style="'width:' + Math.round((total ? done / total : 1) * 100) + '%'"></div></div>
                                <span x-text="(done < total ? 'Nahrávám ' : 'Hotovo — nahráno ') + done + ' / ' + total"></span>
                            </div>
                        </div>
                    @endif
                </div>
                <span>Tyto revize vznikly v editoru, ale ještě nebyly stažené ani nahrané na živý server.</span>
                <ul>
                    @foreach ($undeployedSources as $source)
                        <li>
                            <div class="dz-map-alert-row">
                                <span><strong>{{ $source['filename'] }}</strong></span>
                                <span class="dz-map-alert-actions">
                                    @if ($hasFtpConnection)
                                        <button type="button" class="dz-secondary" x-on:click="dzConfirm('Nahrát {{ $source['filename'] }} přímo na živý server přes FTP? Přepíše aktuální soubor na serveru.').then((ok) => { if (ok) $wire.pushSourceToFtp({{ $source['revision_id'] }}); })">Nahrát</button>
                                    @endif
                                    <a class="dz-secondary" href="{{ route('configuration-revision.download', ['project' => $projectId, 'revision' => $source['revision_id']]) }}">Stáhnout</a>
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (count($spawnPointWarnings))
            <div class="dz-map-alert">
                <strong>Chybí spawn body hráčů.</strong>
                cfgplayerspawnpoints.xml neobsahuje ani jeden bod pro následující režim{{ count($spawnPointWarnings) > 1 ? 'y' : '' }} — hráči v něm nemají kam se objevit.
                <ul>
                    @foreach ($spawnPointWarnings as $warning)
                        <li>
                            <strong>{{ $warning['label'] }}:</strong>
                            Podržte <code>Ctrl</code> a klikněte na mapě na vhodné místo, zvolte „Spawn hráče“, v poli „Režim spawnu“ vyberte
                            <code>{{ $warning['mode'] }}</code> a uložte — vytvoří se nová revize cfgplayerspawnpoints.xml.
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (count($eventSpawnWarnings))
            <div class="dz-map-alert">
                <strong>cfgeventspawns.xml odkazuje na neexistující event{{ count($eventSpawnWarnings) > 1 ? 'y' : '' }}.</strong>
                Tyto pozice patří eventu, který není definovaný v events.xml — hra je při startu přeskočí (server log hlásí „Skipping entry for non-existing event“).
                <ul>
                    @foreach ($eventSpawnWarnings as $eventName)
                        <li>
                            <div class="dz-map-alert-row">
                                <span><strong>{{ $eventName }}</strong> — chybí v events.xml.</span>
                                <span class="dz-map-alert-actions">
                                    <button type="button" wire:click="openAddEventModal('{{ $eventName }}')" class="dz-secondary">Přidat event do events.xml</button>
                                    <x-dz-confirm-button call="removeEventSpawnPositions('{{ $eventName }}')" label="Odstranit pozice" saved-label="Odstraněno" class="dz-danger" />
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (count($animalPopulationWarnings))
            <div class="dz-map-alert">
                <strong>{{ count($animalPopulationWarnings) }} zvíře{{ count($animalPopulationWarnings) > 1 ? '' : '' }} v cfgenvironment.xml nemá v events.xml žádnou populaci.</strong>
                Teritorium samo o sobě nic nespawnuje — bez odpovídajícího eventu v events.xml (nominal/min/max) Central Economy tohle zvíře nikdy nevytvoří, i když jsou zóny nastavené správně.
                <ul>
                    @foreach ($animalPopulationWarnings as $warning)
                        <li>
                            <div class="dz-map-alert-row">
                                <span><strong>{{ $warning['territory'] }}</strong> — chybí event <code>{{ $warning['expected_event'] }}</code> v events.xml.</span>
                                <span class="dz-map-alert-actions">
                                    <button type="button" wire:click="openAddAnimalEventModal('{{ $warning['territory'] }}', '{{ $warning['expected_event'] }}')" class="dz-secondary">Přidat event do events.xml</button>
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (count($animalTypeWarnings))
            <div class="dz-map-alert">
                <strong>{{ count($animalTypeWarnings) }} spawnovaný classname{{ count($animalTypeWarnings) > 1 ? 'ů' : '' }} chybí v types.xml.</strong>
                Central Economy potřebuje pro každou třídu spawnovanou přes cfgenvironment.xml nebo events.xml záznam v types.xml — týká se to zvířat, aut i dalších eventových objektů.
                <ul>
                    @foreach ($animalTypeWarnings as $warning)
                        <li>
                            <div class="dz-map-alert-row">
                                <span><strong>{{ $warning['classname'] }}</strong> — chybí v types.xml (zdroj {{ $warning['territory'] }}).</span>
                                <span class="dz-map-alert-actions">
                                    <x-dz-confirm-button call="addAnimalTypeEntry('{{ $warning['classname'] }}')" label="Doplnit do types.xml" saved-label="Doplněno" class="dz-secondary" />
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (count($spawnValidationWarnings))
            <div class="dz-map-alert">
                <strong>Kontrola použitelnosti spawnů našla {{ collect($spawnValidationWarnings)->sum('occurrences') }} problémů.</strong>
                <span>Tyto kontroly ověřují XML hodnoty. Vhodnost terénu a skutečné odmítnutí kandidáta potvrzuje až poslední RPT.</span>
                <ul>
                    @foreach ($spawnValidationWarnings as $warning)
                        <li>
                            <div class="dz-map-alert-row">
                                <span><strong>{{ $warning['occurrences'] > 1 ? $warning['occurrences'].'× ' : '' }}{{ $warning['title'] }}</strong> — {{ $warning['detail'] }}<br><small>Co udělat: {{ $warning['action'] }}</small></span>
                                @if (!empty($warning['event_name']))
                                    <x-dz-confirm-button call="repairEventPopulation('{{ $warning['event_name'] }}')" label="Automaticky opravit event" saved-label="Opraveno" class="dz-secondary" />
                                @elseif (!empty($warning['unregistered']))
                                    <a class="dz-secondary" href="{{ url('/admin/projects/'.$projectId.'/configuration?register_territory='.rawurlencode($warning['territory_file'])) }}">Ručně registrovat</a>
                                    @php $ignoreConfirm = "Označit {$warning['territory_file']} jako nepoužívaný? Validátor ho přestane kontrolovat, dokud ho zase neodebereš ze seznamu."; @endphp
                                    <button type="button" class="dz-secondary" x-on:click="dzConfirm(@js($ignoreConfirm)).then((ok) => { if (ok) $wire.ignoreUnregisteredTerritoryFile(@js($warning['territory_file'])); })">Ignorovat jako nepoužívaný</button>
                                    @php $deleteConfirm = "Trvale smazat {$warning['territory_file']} i celou jeho historii revizí? Tohle se nedá vrátit."; @endphp
                                    <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($deleteConfirm)).then((ok) => { if (ok) $wire.deleteUnregisteredTerritoryFile(@js($warning['territory_file'])); })">Smazat soubor</button>
                                @elseif (!empty($warning['territory_file']))
                                    @if (!empty($warning['zero_population']))
                                        <x-dz-confirm-button call="repairZeroTerritoryPopulation('{{ $warning['territory_file'] }}')" label="Doplnit spawny (1–3)" saved-label="Opraveno" class="dz-secondary" />
                                        <x-dz-confirm-button call="removeZeroTerritoryPopulation('{{ $warning['territory_file'] }}')" label="Smazat neaktivní zóny" saved-label="Odstraněno" class="dz-secondary" />
                                    @else
                                        <a class="dz-secondary" href="{{ url('/admin/projects/'.$projectId.'/configuration?register_territory='.rawurlencode($warning['territory_file'])) }}">Otevřít průvodce registrací</a>
                                    @endif
                                @else
                                    <a class="dz-secondary" href="{{ url('/admin/projects/'.$projectId.'/configuration') }}">Otevřít konfiguraci</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($showAddEventModal)
            <div class="dz-point-modal">
                <div class="dz-point-modal-card">
                    <button type="button" class="dz-point-close" wire:click="closeAddEventModal" aria-label="Zavřít">×</button>
                    <h3>Přidat event{{ $addEventName !== '' ? ' „'.$addEventName.'“' : '' }} do events.xml</h3>
                    <p class="dz-muted">Vyplňte základní parametry eventu. Vzniklá revize events.xml se dá později doladit v editoru konfigurace.</p>
                    <div class="dz-add-event-grid">
                        <label class="dz-add-event-span2">Název eventu *
                            <input type="text" wire:model="addEventName" placeholder="Např. StaticTestWeaponsChest_DEV2" autocomplete="off" required>
                        </label>
                        <label>Nominal<input type="number" min="0" wire:model="addEventForm.nominal"></label>
                        <label>Min<input type="number" min="0" wire:model="addEventForm.min"></label>
                        <label>Max<input type="number" min="0" wire:model="addEventForm.max"></label>
                        <label>Lifetime (s)<input type="number" min="0" wire:model="addEventForm.lifetime"></label>
                        <label>Restock (s)<input type="number" min="0" wire:model="addEventForm.restock"></label>
                        <label>Safe radius (m)<input type="number" min="0" wire:model="addEventForm.saferadius"></label>
                        <label>Distance radius (m)<input type="number" min="0" wire:model="addEventForm.distanceradius"></label>
                        <label>Cleanup radius (m)<input type="number" min="0" wire:model="addEventForm.cleanupradius"></label>
                        <label>Position
                            <select wire:model="addEventForm.position">
                                <option value="fixed">fixed</option>
                                <option value="player">player</option>
                            </select>
                        </label>
                        <label>Limit
                            <select wire:model="addEventForm.limit">
                                <option value="mixed">mixed</option>
                                <option value="unlimited">unlimited</option>
                                <option value="nearest">nearest</option>
                                <option value="farthest">farthest</option>
                                <option value="child">child · pevný jednorázový objekt (např. statický kontejner)</option>
                            </select>
                        </label>
                        <label class="dz-add-event-span2">Classname objektu ke spawnutí (volitelné)
                            <input type="text" list="dz-classname-catalog" placeholder="Např. VehicleTransitBus" wire:model="addEventForm.child_type" autocomplete="off">
                            <small>Našeptávač nabízí classnames ze známého katalogu; klidně zadej i vlastní/modovaný název. Nechte prázdné, pokud event žádný konkrétní objekt nespawnuje (např. loot event).</small>
                        </label>
                    </div>
                    <div class="dz-edit-actions">
                        <button type="button" wire:click="closeAddEventModal" class="dz-secondary">Zrušit</button>
                        <x-dz-confirm-button call="submitAddEvent()" label="Přidat event a vytvořit revizi" saved-label="Přidáno" class="dz-action" />
                    </div>
                </div>
            </div>
            <datalist id="dz-classname-catalog">
                @foreach ($classnameOptions as $name)
                    <option value="{{ $name }}"></option>
                @endforeach
            </datalist>
        @endif
        <div class="dz-map-layout">
            <section wire:ignore class="dz-map-canvas" aria-label="Mapa serveru">
                <div id="dayz-leaflet-map"></div>
            </section>
            <aside class="dz-map-legend">
                <h3>Vrstvy mapy</h3>
                @php $placeLabels = \App\Support\ChernarusPlaces::all(); @endphp
                <div class="dz-map-layers">
                    <article class="dz-map-layer-card">
                        <label title="Statické názvy měst, vesnic a vojenských základen Chernarusu. Nezávisí na nahraných souborech serveru."><input class="map-layer-toggle" type="checkbox" checked data-layer="__place_labels"><i class="dz-layer-dot" style="background:#f0d488"></i><span><b>Názvy míst</b><small>{{ count($placeLabels) }} lokací · Cherno, Elektro, Berezino…</small></span></label>
                    </article>
                    @foreach ($mapSources as $source)
                        @if ($source['uploaded'] && $source['plottable'] && $source['marker_count'] > 0 && $source['loaded'])
                            <article class="dz-map-layer-card">
                                <label title="{{ $source['description'] }}"><input class="map-layer-toggle" type="checkbox" @checked(($source['filename'] === 'cfgplayerspawnpoints.xml' || $source['filename'] === request()->string('show')->toString()) && $source['marker_count'] <= 3000) data-layer="{{ $source['filename'] }}"><i class="dz-layer-dot" style="{{ $source['dot_style'] }}"></i><b>{{ $source['display_label'] }}</b></label>
                                <div class="dz-map-layer-meta">
                                    <small>{{ $source['marker_count'] }} bodů/oblastí{{ $source['marker_count'] > 3000 ? ' · vrstva je kvůli výkonu vypnutá' : '' }}</small>
                                <details class="dz-layer-details">
                                    <summary>Detaily a akce</summary>
                                    <p>{{ $source['description'] }}</p>
                                    <div class="dz-layer-actions">
                                        <a href="{{ url('/admin/projects/'.$projectId.'/configuration?revision='.$source['revision_id']) }}">Upravit</a>
                                        <a href="{{ route('configuration-revision.download', ['project' => $projectId, 'revision' => $source['revision_id']]) }}">Stáhnout</a>
                                        <button type="button" class="dz-source-raw"
                                            data-filename="{{ $source['filename'] }}"
                                            data-url="{{ route('configuration-revision.raw', ['project' => $projectId, 'revision' => $source['revision_id']]) }}">Raw data</button>
                                        @if ($hasFtpConnection && $source['undeployed'])
                                            <button type="button" class="dz-source-ftp"
                                                x-on:click="dzConfirm('Nahrát {{ $source['filename'] }} přímo na živý server přes FTP? Přepíše aktuální soubor na serveru.').then((ok) => { if (ok) $wire.pushSourceToFtp({{ $source['revision_id'] }}); })">Nahrát na FTP</button>
                                        @endif
                                        <label class="dz-layer-label-toggle"><input type="checkbox" class="map-layer-label-toggle" data-layer="{{ $source['filename'] }}"> Popisky</label>
                                    </div>
                                    @if (in_array($source['filename'], ['cfgeventspawns.xml', 'cfgplayerspawnpoints.xml'], true))
                                        <button type="button" class="dz-layer-cleanup-open"
                                            data-filename="{{ $source['filename'] }}"
                                            data-revision="{{ $source['revision_id'] }}"
                                            data-count="{{ $source['marker_count'] }}">Vybrat skupiny k odstranění…</button>
                                    @endif
                                    @if ($source['filename'] === 'mapgrouppos.xml' && count($lootCategoryLegend))
                                        <details class="dz-loot-legend">
                                            <summary>Barvy podle kategorie lootu (mapgroupproto.xml + types.xml)</summary>
                                            <div class="dz-loot-legend-list">
                                                @foreach ($lootCategoryLegend as $entry)
                                                    <span class="dz-loot-legend-item">
                                                        <i style="background:{{ $entry['color'] }}"></i>
                                                        {{ $entry['category'] }}
                                                        <small>{{ $entry['item_count'] }} položek types.xml</small>
                                                    </span>
                                                @endforeach
                                                <span class="dz-loot-legend-item">
                                                    <i style="background:#6b7a6d"></i>
                                                    bez rozpoznané kategorie
                                                </span>
                                            </div>
                                        </details>
                                    @endif
                                </details>
                                </div>
                            </article>
                        @elseif ($source['uploaded'] && $source['plottable'] && $source['marker_count'] > 0)
                            <a class="dz-load-dense" href="{{ url('/admin/map-editor?project='.$projectId.'&dense=1') }}" data-count="{{ $source['marker_count'] }}" data-filename="{{ $source['filename'] }}"><i class="dz-layer-dot" style="{{ $source['dot_style'] }}"></i><span>Načíst {{ $source['filename'] }}<small>{{ number_format($source['marker_count'], 0, ',', ' ') }} hustých bodů</small></span></a>
                        @endif
                    @endforeach
                </div>
                <hr>
                <div class="dz-map-source-list">
                    <strong>Mapové konfigurační soubory</strong>
                    @foreach ($mapSources as $source)
                        @if ($source['uploaded'])
                            <article class="dz-map-source uploaded">
                                <span><code>{{ $source['filename'] }}</code><small>{{ $source['description'] }}</small><em>Aktuální revize #{{ $source['revision_number'] }}</em></span>
                                <div class="dz-map-source-actions">
                                    <a href="{{ url('/admin/projects/'.$projectId.'/configuration?revision='.$source['revision_id']) }}">Upravit</a>
                                    <a href="{{ route('configuration-revision.download', ['project' => $projectId, 'revision' => $source['revision_id']]) }}">Stáhnout</a>
                                    <button type="button" class="dz-source-raw"
                                        data-filename="{{ $source['filename'] }}"
                                        data-url="{{ route('configuration-revision.raw', ['project' => $projectId, 'revision' => $source['revision_id']]) }}">Raw data</button>
                                    @if ($hasFtpConnection && $source['undeployed'])
                                        <button type="button" class="dz-source-ftp"
                                            x-on:click="dzConfirm('Nahrát {{ $source['filename'] }} přímo na živý server přes FTP? Přepíše aktuální soubor na serveru.').then((ok) => { if (ok) $wire.pushSourceToFtp({{ $source['revision_id'] }}); })">Nahrát na FTP</button>
                                    @endif
                                </div>
                            </article>
                        @else
                            <a class="dz-map-source missing" href="{{ url('/admin/configuration-import?area=map&project='.$projectId.'&expected='.urlencode($source['filename'])) }}"><span><code>{{ $source['filename'] }}</code><small>{{ $source['description'] }}</small></span><b>Nahrát soubor</b></a>
                        @endif
                    @endforeach
                    <small>Zobrazují se pouze poslední revize. Každá změna vytvoří novou revizi a původní soubor zůstane zachovaný.</small>
                </div>
            </aside>
        </div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('dayz-leaflet-map');
            if (!el || el.dataset.ready) return;
            el.dataset.ready = '1';
            const worldSize = 15360;
            const tileSize = 512;
            const tileMaxZoom = 6;
            // Thousands of event/territory points are rendered on this page. Force
            // Leaflet's canvas renderer for both dots and circles; SVG creates one
            // DOM node per point and makes pan/zoom effectively freeze on console
            // maps containing stock territory data.
            const canvasRenderer = L.canvas({ padding: 0.5 });
            // CRS.Simple's own scale (2^zoom) treats 1 world meter as 1px at zoom 0 —
            // that doesn't match the tile pyramid, which covers the whole worldSize in a
            // single tileSize px tile at zoom 0 and doubles per zoom level like any XYZ
            // pyramid. This keeps LatLng == raw world X/Z (so every marker/coordinate
            // calculation below is untouched) while rescaling pixels-per-zoom to match
            // what tiles/{z}/{x}/{y}.webp actually contains.
            // The last parameter (d) shifts the Y origin so pixel.y=0 lands on Z=worldSize
            // (the top row of the source image / tile row 0) instead of Z=0 — without it,
            // the entire world falls inside tile row -1 and row 0 is never requested.
            const dayzCrs = L.extend({}, L.CRS.Simple, {
                transformation: new L.Transformation(tileSize / worldSize, 0, -tileSize / worldSize, tileSize),
            });
            const map = L.map(el, { crs: dayzCrs, minZoom: 0, maxZoom: tileMaxZoom, zoomSnap: 0.25, inertia:false, preferCanvas:true, renderer: canvasRenderer });
            const bounds = [[0, 0], [worldSize, worldSize]];
            L.tileLayer('/maps/chernarusplus/tiles/{z}/{x}/{y}.webp', {
                tileSize, minZoom: 0, maxZoom: tileMaxZoom, bounds, noWrap: true,
                attribution: 'Satelitní podklad: iZurvive.com',
            }).addTo(map);
            L.rectangle(bounds, { color: '#b8ed55', weight: 1, fill: false, opacity: .35 }).addTo(map);
            map.fitBounds(bounds);
            L.control.scale({ imperial: false }).addTo(map);
            const coordinateControl = L.control({ position: 'bottomleft' });
            coordinateControl.onAdd = () => { const div = L.DomUtil.create('div', 'dz-coordinate-control'); div.textContent = 'X: — · Z: —'; return div; };
            coordinateControl.addTo(map);
            const grid = L.layerGroup().addTo(map);
            const coordinateLabels = L.layerGroup().addTo(map);
            const coordinateAxes = L.DomUtil.create('div', 'dz-coordinate-axes', el);
            const xAxis = L.DomUtil.create('div', 'dz-coordinate-axis dz-coordinate-axis-x', coordinateAxes);
            const zAxis = L.DomUtil.create('div', 'dz-coordinate-axis dz-coordinate-axis-z', coordinateAxes);
            for (let coordinate = 0; coordinate <= 15000; coordinate += 1000) {
                L.polyline([[0, coordinate], [worldSize, coordinate]], { color:'#d8f57b', weight:1, opacity:.2, interactive:false }).addTo(grid);
                L.polyline([[coordinate, 0], [coordinate, worldSize]], { color:'#d8f57b', weight:1, opacity:.2, interactive:false }).addTo(grid);
            }
            const coordinateText = (x, z, source = 'střed') => {
                document.querySelector('.dz-coordinate-control').textContent = source + ' · X: ' + Math.round(x).toLocaleString() + ' · Z: ' + Math.round(z).toLocaleString();
            };
            const visibleCoordinateRange = () => {
                const zoom = map.getZoom();
                // Thresholds shifted by log2(worldSize/tileSize) ≈ 4.9 versus the old
                // CRS.Simple numbering (see dayzCrs above) — same visual density as before,
                // just re-based onto the tile pyramid's 0..6 zoom range.
                const step = zoom >= 5 ? 100 : (zoom >= 3.5 ? 500 : (zoom >= 2 ? 1000 : 2000));
                const visible = map.getBounds();
                const west = Math.max(0, visible.getWest());
                const east = Math.min(worldSize, visible.getEast());
                const south = Math.max(0, visible.getSouth());
                const north = Math.min(worldSize, visible.getNorth());
                const firstX = Math.ceil(west / step) * step;
                const firstZ = Math.ceil(south / step) * step;

                return { zoom, step, west, east, south, north, firstX, firstZ };
            };
            const refreshCoordinateAxes = () => {
                const { step, east, north, firstX, firstZ } = visibleCoordinateRange();
                xAxis.replaceChildren();
                zAxis.replaceChildren();
                for (let x = firstX; x <= east; x += step) {
                    const point = map.latLngToContainerPoint([map.getCenter().lat, x]);
                    const label = document.createElement('span');
                    label.style.left = point.x + 'px';
                    label.textContent = 'X ' + Math.round(x).toLocaleString();
                    xAxis.appendChild(label);
                }
                for (let z = firstZ; z <= north; z += step) {
                    const point = map.latLngToContainerPoint([z, map.getCenter().lng]);
                    const label = document.createElement('span');
                    label.style.top = point.y + 'px';
                    label.textContent = 'Z ' + Math.round(z).toLocaleString();
                    zAxis.appendChild(label);
                }
            };
            const refreshCoordinateGrid = () => {
                coordinateLabels.clearLayers();
                const { zoom, step, west, east, south, north, firstX, firstZ } = visibleCoordinateRange();
                for (let x = firstX; x <= east; x += step) {
                    L.polyline([[south, x], [north, x]], { color:'#ecf8d7', weight:1, opacity:.28, interactive:false, dashArray:zoom >= 0 ? '3 4' : null }).addTo(coordinateLabels);
                }
                for (let z = firstZ; z <= north; z += step) {
                    L.polyline([[z, west], [z, east]], { color:'#ecf8d7', weight:1, opacity:.28, interactive:false, dashArray:zoom >= 0 ? '3 4' : null }).addTo(coordinateLabels);
                }
                refreshCoordinateAxes();
                const center = map.getCenter();
                coordinateText(center.lng, center.lat, 'Střed mapy');
            };
            let axisFrame = null;
            map.on('move zoom', () => {
                if (axisFrame !== null) cancelAnimationFrame(axisFrame);
                axisFrame = requestAnimationFrame(() => {
                    refreshCoordinateAxes();
                    axisFrame = null;
                });
            });
            map.on('moveend zoomend', refreshCoordinateGrid);
            refreshCoordinateGrid();
            map.on('mousemove', (event) => {
                const x = Math.max(0, Math.min(worldSize, Math.round(event.latlng.lng)));
                const z = Math.max(0, Math.min(worldSize, Math.round(event.latlng.lat)));
                coordinateText(x, z, 'Kurzor');
            });
            map.on('mouseout', () => {
                const center = map.getCenter();
                coordinateText(center.lng, center.lat, 'Střed mapy');
            });
            map.on('click', (event) => {
                if (!event.originalEvent.ctrlKey) return;
                const modal = document.getElementById('dz-point-modal');
                modal.hidden = false;
                modal.dataset.lat = event.latlng.lat;
                modal.dataset.lng = event.latlng.lng;
            });
            const modal = document.getElementById('dz-point-modal');
            modal.querySelector('.dz-point-close').onclick = () => modal.hidden = true;
            const editModal = document.getElementById('dz-edit-point-modal');
            editModal.querySelector('.dz-point-close').onclick = () => editModal.hidden = true;

            // Fullscreen toggles the whole page section (not just the Leaflet canvas) so
            // every modal/dialog on this page — all of them live outside .dz-map-layout in
            // the DOM — stays reachable; the Fullscreen API only renders the fullscreened
            // element's own subtree, so anything outside it would otherwise vanish.
            const mapPage = document.querySelector('.dz-map-page');
            const fullscreenToggle = document.querySelector('.dz-map-fullscreen-toggle');
            if (mapPage && fullscreenToggle) {
                fullscreenToggle.addEventListener('click', () => {
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        mapPage.requestFullscreen?.();
                    }
                });
                document.addEventListener('fullscreenchange', () => {
                    const isFullscreen = document.fullscreenElement === mapPage;
                    fullscreenToggle.textContent = isFullscreen ? 'Zavřít celou obrazovku' : 'Na celou obrazovku';
                    fullscreenToggle.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
                    // Leaflet caches its container size; it never sees a fullscreen resize
                    // by itself, so panning/zooming stays limited to the old dimensions
                    // until this runs. requestAnimationFrame waits for the CSS `:fullscreen`
                    // height change to actually apply before Leaflet re-measures.
                    requestAnimationFrame(() => map.invalidateSize());
                });
            }
            const systemDialog = document.getElementById('dz-system-dialog');
            const dialogCancel = systemDialog.querySelector('.dz-dialog-cancel');
            const dialogConfirm = systemDialog.querySelector('.dz-dialog-confirm');
            let dialogResolver = null;
            const closeSystemDialog = (result) => {
                systemDialog.hidden = true;
                const resolver = dialogResolver;
                dialogResolver = null;
                resolver?.(result);
            };
            const showSystemDialog = ({ title, message, confirmLabel = 'Potvrdit', cancelLabel = 'Zrušit', danger = false, notice = false }) => new Promise((resolve) => {
                if (dialogResolver) dialogResolver(false);
                dialogResolver = resolve;
                systemDialog.querySelector('#dz-system-dialog-title').textContent = title;
                systemDialog.querySelector('.dz-system-dialog-message').textContent = message;
                systemDialog.querySelector('.dz-eyebrow').textContent = notice ? 'INFORMACE' : 'POTVRZENÍ ZMĚNY';
                dialogCancel.textContent = cancelLabel;
                dialogCancel.hidden = notice;
                dialogConfirm.textContent = confirmLabel;
                dialogConfirm.classList.toggle('danger', danger);
                systemDialog.hidden = false;
                requestAnimationFrame(() => dialogConfirm.focus());
            });
            dialogCancel.onclick = () => closeSystemDialog(false);
            dialogConfirm.onclick = () => closeSystemDialog(true);
            const rawModal = document.getElementById('dz-raw-modal');
            const rawCode = rawModal.querySelector('.dz-map-raw-content code');
            const rawStatus = rawModal.querySelector('.dz-map-raw-status');
            const rawCopy = rawModal.querySelector('.dz-map-raw-copy');
            let rawText = '';
            const closeRawModal = () => {
                rawModal.hidden = true;
                rawText = '';
                rawCode.textContent = '';
            };
            rawModal.querySelector('.dz-point-close').onclick = closeRawModal;
            rawModal.addEventListener('click', (event) => {
                if (event.target === rawModal) closeRawModal();
            });
            rawCopy.onclick = async () => {
                if (!rawText) return;
                try {
                    await navigator.clipboard.writeText(rawText);
                    rawCopy.textContent = 'Zkopírováno';
                    setTimeout(() => rawCopy.textContent = 'Kopírovat', 1400);
                } catch {
                    await showSystemDialog({
                        title:'Kopírování se nepodařilo',
                        message:'Prohlížeč nepovolil přístup ke schránce. Označte text v okně a zkopírujte jej ručně.',
                        confirmLabel:'Rozumím',
                        notice:true
                    });
                }
            };
            systemDialog.addEventListener('click', (event) => {
                if (event.target === systemDialog && !dialogCancel.hidden) closeSystemDialog(false);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (!systemDialog.hidden) closeSystemDialog(false);
                else if (!rawModal.hidden) closeRawModal();
            });
            let activeMarker = null;
            let pendingButton = null;
            const showFeedback = (root, message, isError = true) => {
                const feedback = root.querySelector('.dz-map-feedback');
                if (!feedback) return;
                feedback.textContent = message;
                feedback.classList.toggle('error', isError);
                feedback.hidden = false;
            };
            // A 5xx means the request never actually reached the app (proxy/server/container
            // issue) — the body is an HTML error page, not JSON, so .json() always fails for it.
            // Surfacing that distinction beats the generic fallback, which reads like the save
            // itself was rejected when actually nothing was ever processed.
            const describeFetchError = async (response, fallback) => {
                if (response.status >= 500) {
                    return 'Server na chvíli neodpověděl (chyba ' + response.status + '). Zkus to prosím znovu; pokud se to opakuje, nejde o tuhle úpravu, ale o výpadek serveru.';
                }
                try {
                    const data = await response.json();
                    return data.message || fallback;
                } catch {
                    return fallback;
                }
            };
            const mkEl = (tag, props = {}, children = []) => {
                const node = document.createElement(tag);
                Object.entries(props).forEach(([key, value]) => {
                    if (key === 'text') node.textContent = value;
                    else node.setAttribute(key, value);
                });
                (Array.isArray(children) ? children : [children]).forEach((child) => {
                    if (child === null || child === undefined) return;
                    node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
                });
                return node;
            };
            const pointTypeCatalog = @js($pointTypeCatalog);
            const mapGroupNameCatalog = document.createElement('datalist');
            mapGroupNameCatalog.id = 'dz-mapgroup-name-catalog';
            (pointTypeCatalog.loot?.options || []).forEach((option) => mapGroupNameCatalog.appendChild(mkEl('option', { value: option.value })));
            document.body.appendChild(mapGroupNameCatalog);
            const zoneTypeCatalog = document.createElement('datalist');
            zoneTypeCatalog.id = 'dz-zone-type-catalog';
            (@js($this->zoneTypeCatalog())).forEach((name) => zoneTypeCatalog.appendChild(mkEl('option', { value: name })));
            document.body.appendChild(zoneTypeCatalog);
            const animalZoneTypeCatalog = document.createElement('datalist');
            animalZoneTypeCatalog.id = 'dz-animal-zone-type-catalog';
            ['Graze', 'Water', 'Rest'].forEach((name) => animalZoneTypeCatalog.appendChild(mkEl('option', { value: name })));
            document.body.appendChild(animalZoneTypeCatalog);
            const cargoPresetCatalog = document.createElement('datalist');
            cargoPresetCatalog.id = 'dz-cargo-preset-catalog';
            (@js($pointTypeCatalog['_spawnable_suggestions']['cargo_presets'] ?? [])).forEach((name) => cargoPresetCatalog.appendChild(mkEl('option', { value: name })));
            document.body.appendChild(cargoPresetCatalog);
            const attachmentCatalog = document.createElement('datalist');
            attachmentCatalog.id = 'dz-attachment-catalog';
            (@js($pointTypeCatalog['_spawnable_suggestions']['attachments'] ?? [])).forEach((name) => attachmentCatalog.appendChild(mkEl('option', { value: name })));
            document.body.appendChild(attachmentCatalog);
            let selectedCatalogOption = null;
            const editDefinitionForMarker = (marker) => {
                if (marker.type === 'player-spawn-area') return pointTypeCatalog.player;
                if (marker.type === 'event-spawn') return pointTypeCatalog.dynamic;
                if (marker.type === 'map-group') return pointTypeCatalog.loot;
                if (marker.type === 'territory') return pointTypeCatalog.territory;
                return null;
            };
            const buildPointFields = (root, fields, values = {}, editMode = false) => {
                root.replaceChildren();
                const sections = new Map();
                (fields || []).forEach((field) => {
                    const sectionName = field.section || '';
                    let section = root;
                    if (sectionName) {
                        if (!sections.has(sectionName)) {
                            const group = document.createElement('fieldset');
                            group.className = 'dz-point-field-section';
                            const legend = document.createElement('legend');
                            legend.textContent = sectionName;
                            group.appendChild(legend);
                            const grid = document.createElement('div');
                            grid.className = 'dz-point-field-section-grid';
                            group.appendChild(grid);
                            root.appendChild(group);
                            sections.set(sectionName, grid);
                        }
                        section = sections.get(sectionName);
                    }
                    if (field.type === 'item-list') {
                        section.appendChild(buildItemListField(field, values[field.name] || field.default || []));
                        return;
                    }
                    const label = document.createElement('label');
                    label.textContent = field.label + (field.required ? ' *' : '');
                    if (field.required) label.classList.add('dz-required-field');
                    let input;
                    if (field.type === 'select') {
                        input = document.createElement('select');
                        (field.options || []).forEach((option) => input.add(new Option(option.label, option.value)));
                    } else {
                        input = document.createElement('input');
                        input.type = field.type || 'text';
                        ['min', 'max', 'step', 'list'].forEach((attribute) => {
                            if (field[attribute] !== undefined) input.setAttribute(attribute, field[attribute]);
                        });
                        if (field.autocomplete === false) input.setAttribute('autocomplete', 'off');
                    }
                    input.dataset.parameter = field.name;
                    if (field.required) input.required = true;
                    if (field.type === 'checkbox') {
                        input.checked = !!(values[field.name] ?? field.default ?? false);
                        label.classList.add('dz-checkbox-field');
                    } else {
                        input.value = values[field.name] ?? field.default ?? '';
                    }
                    if (editMode && field.name === 'spawn_mode') {
                        input.disabled = true;
                        input.title = 'Existující bod nelze přesunout mezi režimy; lze jej smazat a vytvořit v jiném režimu.';
                    }
                    if (!editMode && field.name === 'name') {
                        input.disabled = true;
                        input.title = 'Při přidávání nového bodu vyber prototyp výše v poli „Vyberte existující možnost“. Toto pole slouží k pozdější změně, až bod edituješ.';
                    }
                    label.appendChild(input);
                    if (field.help) {
                        const help = document.createElement('small');
                        help.textContent = field.help;
                        label.appendChild(help);
                    }
                    section.appendChild(label);
                });
            };
            const buildItemListField = (field, initialItems) => {
                const wrap = document.createElement('div');
                wrap.className = 'dz-item-list-field';
                const legend = document.createElement('label');
                legend.textContent = field.label;
                wrap.appendChild(legend);
                const rows = document.createElement('div');
                rows.className = 'dz-item-list-rows';
                wrap.appendChild(rows);
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.dataset.parameter = field.name;
                wrap.appendChild(hidden);
                const itemFields = field.item_fields || ['name', 'chance', 'quantmin', 'quantmax'];
                const fieldMeta = {
                    name: { placeholder: 'classname', type: 'text' },
                    chance: { placeholder: 'šance 0–1', type: 'number', min: 0, max: 1, step: 0.01 },
                    quantmin: { placeholder: 'min ks', type: 'number', min: 0, step: 1 },
                    quantmax: { placeholder: 'max ks', type: 'number', min: 0, step: 1 },
                };
                const sync = () => {
                    const items = Array.from(rows.children).map((row) => {
                        const item = {};
                        itemFields.forEach((key) => {
                            const cell = row.querySelector('[data-item-field="' + key + '"]');
                            item[key] = cell ? cell.value : '';
                        });
                        return item;
                    }).filter((item) => String(item.name || '').trim() !== '');
                    hidden.value = JSON.stringify(items);
                };
                const addRow = (item = {}) => {
                    const row = document.createElement('div');
                    row.className = 'dz-item-list-row';
                    itemFields.forEach((key) => {
                        const meta = fieldMeta[key] || { type: 'text' };
                        const cell = document.createElement('input');
                        cell.type = meta.type;
                        cell.dataset.itemField = key;
                        cell.placeholder = meta.placeholder || key;
                        if (meta.min !== undefined) cell.min = meta.min;
                        if (meta.max !== undefined) cell.max = meta.max;
                        if (meta.step !== undefined) cell.step = meta.step;
                        if (key === 'name' && field.list) cell.setAttribute('list', field.list);
                        cell.value = item[key] ?? '';
                        cell.addEventListener('input', sync);
                        row.appendChild(cell);
                    });
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'dz-item-list-remove';
                    remove.textContent = '×';
                    remove.addEventListener('click', () => { row.remove(); sync(); });
                    row.appendChild(remove);
                    rows.appendChild(row);
                };
                (initialItems || []).forEach((item) => addRow(item));
                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.className = 'dz-item-list-add';
                addButton.textContent = '+ přidat item';
                addButton.addEventListener('click', () => { addRow(); sync(); });
                wrap.appendChild(addButton);
                if (field.help) {
                    const help = document.createElement('small');
                    help.textContent = field.help;
                    wrap.appendChild(help);
                }
                sync();
                return wrap;
            };
            const renderPointFields = () => {
                const catalogRoot = document.getElementById('dz-event-catalog');
                const definition = pointTypeCatalog[pendingButton?.dataset.point] || {};
                const root = catalogRoot.querySelector('.dz-point-fields');
                buildPointFields(root, definition.fields || []);
                const mode = root.querySelector('[data-parameter="spawn_mode"]');
                if (mode && definition.mode_defaults) {
                    const applyModeDefaults = () => {
                        const values = definition.mode_defaults[mode.value] || {};
                        Object.entries(values).forEach(([name, value]) => {
                            const input = root.querySelector('[data-parameter="' + name + '"]');
                            if (input && value !== '') input.value = value;
                        });
                    };
                    mode.addEventListener('change', applyModeDefaults);
                    applyModeDefaults();
                }
            };
            const renderRelatedSettings = () => {
                const catalogRoot = document.getElementById('dz-event-catalog');
                const definition = pointTypeCatalog[pendingButton?.dataset.point] || {};
                const eventRoot = catalogRoot.querySelector('.dz-event-settings');
                const relatedRoot = catalogRoot.querySelector('.dz-related-files');
                const settings = selectedCatalogOption?.event_settings || {};
                const children = selectedCatalogOption?.children || [];
                eventRoot.hidden = Object.keys(settings).length === 0;
                if (!eventRoot.hidden) {
                    const labels = {nominal:'Cílový počet eventů',min:'Minimum současně',max:'Maximum současně',lifetime:'Životnost (s)',restock:'Doplnění (s)',saferadius:'Bezpečný poloměr (m)',distanceradius:'Vzdálenost od hráče (m)',cleanupradius:'Poloměr úklidu (m)',position:'Režim pozice',limit:'Způsob limitu',active:'Aktivní',deletable:'Lze odstranit',init_random:'Náhodný start',remove_damaged:'Odstranit poškozené'};
                    const editable = ['nominal','min','max','lifetime','restock','saferadius','distanceradius','cleanupradius'];
                    const grid = mkEl('div', {class:'dz-event-setting-grid'}, Object.entries(settings).map(([key,value]) => {
                        if (!editable.includes(key)) return mkEl('span', {}, [mkEl('small', {text:labels[key] || key}), mkEl('b', {text:String(value)})]);
                        return mkEl('label', {}, [mkEl('small', {text:labels[key] || key}), mkEl('input', {type:'number', min:'0', value:String(value), 'data-parameter':'event_'+key})]);
                    }));
                    const nodes = [
                        mkEl('strong', {text:'Související pravidla z events.xml'}),
                        mkEl('p', {text:'Tato nastavení platí pro celý event, ne jen pro právě přidávaný bod.'}),
                        grid,
                    ];
                    if (children.length) {
                        const list = mkEl('ul', {}, children.map((child) => mkEl('li', {}, [mkEl('code', {text:child.type}), ' · min '+child.min+', max '+child.max+', loot '+child.lootmin+'–'+child.lootmax])));
                        nodes.push(mkEl('details', {}, [mkEl('summary', {text:'Varianty / children ('+children.length+')'}), list]));
                    }
                    if (['vehicle', 'heli', 'convoy', 'dynamic', 'aerial'].includes(pendingButton?.dataset.point)) {
                        if (children.length) {
                            const spawnList = mkEl('div', {class:'dz-spawn-class-list'}, [
                                mkEl('strong', {text:'Třídy, které tento event může spawnovat'}),
                                mkEl('small', {text:'Tyto classnames pocházejí z events.xml/cfgeventgroups.xml. Zaškrtnuté třídy se při uložení doplní do types.xml.'}),
                                ...children.map((child) => mkEl('label', {class:'dz-spawn-class-row'}, [
                                    mkEl('input', {type:'checkbox', checked:'checked', 'data-parameter':'spawn_type_'+child.type}),
                                    mkEl('code', {text:child.type}),
                                    mkEl('span', {text:'min '+child.min+' · max '+child.max}),
                                ])),
                            ]);
                            nodes.push(spawnList);
                        }
                        const auto = mkEl('label', {class:'dz-auto-types'}, [
                            mkEl('input', {type:'checkbox', checked:'checked'}),
                            mkEl('span', {}, [mkEl('strong', {text:'Automaticky doplnit vybrané třídy do types.xml'}), mkEl('small', {text:'Zaeviduje vybrané classnames. Počet vozidel se nastavuje v events.xml.'})]),
                        ]);
                        auto.querySelector('input').dataset.parameter = 'auto_add_types';
                        nodes.push(mkEl('div', {class:'dz-related-editor-section'}, [auto]));
                        nodes.push(mkEl('label', {}, [mkEl('strong', {text:'Stav vozidla při spawnu'}), mkEl('small', {text:'0 = 100% funkční, 1 = zcela poškozené. Uložení proběhne do cfgspawnabletypes.xml.'}), mkEl('span', {class:'dz-inline-fields'}, [mkEl('label', {}, [mkEl('small', {text:'Minimum damage'}), mkEl('input', {type:'number', min:'0', max:'1', step:'0.01', value:'0', 'data-parameter':'damage_min'})]), mkEl('label', {}, [mkEl('small', {text:'Maximum damage'}), mkEl('input', {type:'number', min:'0', max:'1', step:'0.01', value:'0', 'data-parameter':'damage_max'})])])]));
                        nodes.push(mkEl('label', {}, [mkEl('strong', {text:'Výbava a náklad vozidla'}), mkEl('small', {text:'Volitelné. Nabídka vychází z aktuálního cfgspawnabletypes.xml; lze zadat i vlastní hodnotu.'}), mkEl('input', {type:'text', list:'dz-cargo-preset-catalog', placeholder:'např. mixHunter', 'data-parameter':'cargo_preset'}), mkEl('input', {type:'text', list:'dz-attachment-catalog', placeholder:'SparkPlug,CarRadiator,CarBattery', 'data-parameter':'attachments'})]));
                    }
                    eventRoot.replaceChildren(...nodes);
                }
                const related = {...(definition.related || {})};
                if (Object.keys(settings).length && (selectedCatalogOption?.event_name || selectedCatalogOption?.value)) delete related.events_xml;
                relatedRoot.hidden = Object.keys(related).length === 0;
                if (!relatedRoot.hidden) {
                    const list = mkEl('ul', {}, Object.entries(related).map(([file,description]) => mkEl('li', {}, [mkEl('code', {text:file}), ' – '+description])));
                    relatedRoot.replaceChildren(
                        mkEl('strong', {text: pendingButton?.dataset.point === 'vehicle' ? 'Nastavení vozidla – kam se co uloží' : 'Nastavení zvířecího eventu – kam se co uloží'}),
                        list,
                        ...(selectedCatalogOption?.value ? [mkEl('a', {href:'{{ url('/admin/projects') }}/' + @js($projectId) + '/configuration?event=' + encodeURIComponent(selectedCatalogOption.value), text:'Otevřít tento event v editoru events.xml →'})] : []),
                        mkEl('small', {text: pendingButton?.dataset.point === 'vehicle' ? 'Pozice a natočení se ukládají do cfgeventspawns.xml. Počet, limity a životnost jsou v events.xml; stav, cargo a attachmenty v cfgspawnabletypes.xml.' : 'Pozice zóny se ukládá do příslušného *_territories.xml. Počet zvířat, velikost stáda, lifetime a aktivace se řídí eventem v events.xml.'}),
                    );
                }
            };
            const syncPointTarget = () => {
                const catalogRoot = document.getElementById('dz-event-catalog');
                const definition = pointTypeCatalog[pendingButton?.dataset.point] || {};
                const selectedIndex = catalogRoot.querySelector('select').selectedIndex - 1;
                selectedCatalogOption = selectedIndex >= 0 ? (definition.options || [])[selectedIndex] : null;
                const target = selectedCatalogOption?.target || definition.target;
                const available = Boolean(target && definition.available !== false && selectedCatalogOption?.available !== false);
                const missing = selectedCatalogOption?.available === false
                    ? [selectedCatalogOption.target]
                    : (definition.missing || []);
                const uploadUrl = selectedCatalogOption?.upload_url || definition.upload_url;
                const status = catalogRoot.querySelector('.dz-point-target-status');
                if (available) {
                    status.className = 'dz-point-target-status ready';
                    status.replaceChildren(
                        mkEl('strong', {}, ['Zapíše se do: ', mkEl('code', {text:target})]),
                        mkEl('span', {text:'Uložení vytvoří novou revizi tohoto souboru; původní revize zůstane zachována.'}),
                    );
                    catalogRoot.querySelector('.dz-map-feedback').hidden = true;
                } else {
                    const names = missing.length ? missing.join(', ') : (definition.target_label || 'požadovaná konfigurace');
                    status.className = 'dz-point-target-status missing';
                    const nodes = [
                        mkEl('strong', {text:'Chybí aktuální soubor: ' + names}),
                        mkEl('span', {text:'Bez něj bod nelze bezpečně uložit do DayZ konfigurace.'}),
                    ];
                    if (uploadUrl) nodes.push(mkEl('a', {href:uploadUrl, text:'Nahrát aktuální konfiguraci →'}));
                    status.replaceChildren(...nodes);
                    if (selectedCatalogOption || (definition.options || []).length === 0) {
                        showFeedback(catalogRoot, 'Chybí ' + names + ' — nejdřív ho nahraj, jinak se bod neuloží.');
                    }
                }
                const confirm = catalogRoot.querySelector('.dz-point-confirm');
                confirm.disabled = !available || (!selectedCatalogOption && (definition.options || []).length > 0);
                confirm.textContent = available ? 'Umístit bod a vytvořit revizi' : 'Nejprve nahrajte požadovaný soubor';
                renderRelatedSettings();
                syncTerritoryZoneField();
                return { target, available };
            };
            const syncTerritoryZoneField = () => {
                if (!['animal', 'territory'].includes(pendingButton?.dataset.point)) return;
                const input = document.querySelector('#dz-event-catalog [data-parameter="zone_type"]');
                if (!input) return;
                const option = selectedCatalogOption?.value || '';
                const animal = option.startsWith('Animal') || pendingButton?.dataset.point === 'animal';
                if (!animal) return;
                const allowed = ['Graze', 'Water', 'Rest'];
                input.setAttribute('list', 'dz-animal-zone-type-catalog');
                input.setAttribute('placeholder', allowed.join(', '));
                const current = String(input.value || '').trim();
                if (!allowed.includes(current)) input.value = 'Graze';
                const help = input.closest('label')?.querySelector('small');
                if (help) help.textContent = 'Povinné pro zvířata. Povolené hodnoty: Graze, Water, Rest. HuntingGround hra pro stáda nepoužije.';
            };
            const placePoint = (button) => {
                const label = button.dataset.label || button.textContent.trim();
                const definition = pointTypeCatalog[button.dataset.point] || { options: [], missing: ['podporovaný konfigurační soubor'], available: false };
                if (pendingButton !== button) {
                    pendingButton = button;
                    modal.querySelectorAll('[data-point]').forEach((item) => item.classList.toggle('selected', item === button));
                    const catalog = document.getElementById('dz-event-catalog'); const select = catalog.querySelector('select'); const nameInput = catalog.querySelector('.dz-point-name');
                    const entries = definition.options || [];
                    select.replaceChildren(new Option('Vyberte existující možnost...', ''));
                    entries.forEach((item) => select.add(new Option(item.label, item.value)));
                    catalog.hidden = false;
                    nameInput.value = '';
                    select.selectedIndex = entries.length === 1 ? 1 : 0;
                    selectedCatalogOption = entries.length === 1 ? entries[0] : null;
                    select.onchange = syncPointTarget;
                    renderPointFields();
                    catalog.querySelector('.dz-point-help').textContent = definition.help || 'Vyberte existující možnost.';
                    catalog.querySelector('.dz-map-feedback').hidden = true;
                    syncPointTarget();
                    return;
                }
                const targetState = syncPointTarget();
                if (!targetState.available) return;
                const customName = document.querySelector('.dz-point-name')?.value?.trim();
                const selectedName = document.querySelector('#dz-event-catalog select')?.value;
                const chosen = ['contaminated', 'player'].includes(button.dataset.point)
                    ? (customName || selectedName || label)
                    : (selectedName || customName || label);
                const parameters = {};
                document.querySelectorAll('#dz-event-catalog [data-parameter]').forEach((input) => parameters[input.dataset.parameter] = input.type === 'checkbox' ? input.checked : input.value);
                const requiredMissing = (definition.fields || []).filter((field) => field.required && !String(parameters[field.name] ?? '').trim());
                if (requiredMissing.length) {
                    showFeedback(catalogRoot, 'Vyplň povinná pole: ' + requiredMissing.map((field) => field.label).join(', ') + '.');
                    const firstMissing = catalogRoot.querySelector('[data-parameter="' + requiredMissing[0].name + '"]');
                    firstMissing?.focus();
                    return;
                }
                if (['animal', 'territory'].includes(button.dataset.point) && selectedCatalogOption?.value?.startsWith('Animal')) {
                    const allowed = ['Graze', 'Water', 'Rest'];
                    if (!allowed.includes(String(parameters.zone_type || '').trim())) {
                        showFeedback(catalogRoot, 'Neplatná úloha zóny pro zvířata. Použij Graze, Water nebo Rest.');
                        catalogRoot.querySelector('[data-parameter="zone_type"]')?.focus();
                        return;
                    }
                }
                const marker = L.marker([Number(modal.dataset.lat), Number(modal.dataset.lng)]).addTo(map);
                const newX = Math.round(Number(modal.dataset.lng));
                const newZ = Math.round(Number(modal.dataset.lat));
                fetch('{{ route('map-editor.points.store') }}', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({project_id: @js($projectId), type: button.dataset.point, label: chosen || label, target_filename: targetState.target, x: newX, z: newZ, parameters}) }).then(async (response) => {
                    if (!response.ok) throw new Error(await describeFetchError(response, 'Uložení bodu selhalo'));
                    // A freshly-touched layer is unchecked by default (only cfgplayerspawnpoints.xml
                    // starts on) — force it on after reload, otherwise the point that was just added
                    // exists in the file but never actually renders, looking like nothing happened.
                    const reloadUrl = new URL(window.location.href);
                    reloadUrl.searchParams.set('show', targetState.target);
                    window.location.href = reloadUrl.toString();
                }).catch((error) => { map.removeLayer(marker); modal.hidden = false; showFeedback(modal, error.message); });
                const popup = () => mkEl('span', {}, [
                    mkEl('strong', {text:chosen || label}),
                    mkEl('br'),
                    mkEl('small', {}, ['Typ: ' + label, mkEl('br'), 'DayZ X/Z: ' + newX + ' / ' + newZ]),
                ]);
                marker.bindPopup(popup()).openPopup();
                pendingButton = null;
                modal.hidden = true;
            };
            modal.querySelectorAll('[data-point]').forEach((button) => button.onclick = () => placePoint(button));
            modal.querySelector('.dz-point-confirm').onclick = () => { if (pendingButton) placePoint(pendingButton); };
            const hexToHsl = (hex) => {
                const r = parseInt(hex.slice(1, 3), 16) / 255, g = parseInt(hex.slice(3, 5), 16) / 255, b = parseInt(hex.slice(5, 7), 16) / 255;
                const max = Math.max(r, g, b), min = Math.min(r, g, b);
                let h = 0, s = 0; const l = (max + min) / 2;
                if (max !== min) {
                    const d = max - min;
                    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                    if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
                    else if (max === g) h = (b - r) / d + 2;
                    else h = (r - g) / d + 4;
                    h /= 6;
                }
                return [h * 360, s * 100, l * 100];
            };
            const hashString = (value) => { let hash = 0; for (let i = 0; i < value.length; i++) hash = (hash * 31 + value.charCodeAt(i)) | 0; return Math.abs(hash); };
            const shadeCache = {};
            const shadeFor = (baseHex, key) => {
                const cacheKey = baseHex + '|' + key;
                if (shadeCache[cacheKey]) return shadeCache[cacheKey];
                const [h, s, l] = hexToHsl(baseHex);
                const hash = hashString(key || '');
                const hueShift = (hash % 21) - 10;
                const lightShift = ((hash >> 5) % 31) - 15;
                const newHue = (h + hueShift + 360) % 360;
                const newLight = Math.min(78, Math.max(28, l + lightShift));
                const newSat = Math.min(90, Math.max(35, s));
                return shadeCache[cacheKey] = `hsl(${newHue.toFixed(1)}, ${newSat.toFixed(0)}%, ${newLight.toFixed(1)}%)`;
            };
            const markers = @js($markers);
            const placeLabels = @js($placeLabels);
            const layerGroups = {};
            const placeLabelLayers = layerGroups['__place_labels'] = [];
            placeLabels.forEach((place) => {
                const label = L.tooltip({ permanent: true, direction: 'center', className: 'dz-place-label', interactive: false })
                    .setLatLng([place.z, place.x])
                    .setContent(place.name)
                    .addTo(map);
                placeLabelLayers.push(label);
            });
            const updatePlaceLabelVisibility = () => {
                el.classList.toggle('dz-hide-place-labels', map.getZoom() < 2);
                el.style.setProperty('--dz-zoom', map.getZoom());
            };
            map.on('zoomend', updatePlaceLabelVisibility);
            updatePlaceLabelVisibility();
            const markerRecords = [];
            markers.forEach((marker) => {
                const baseColor = marker.color || '#b8ed55';
                const groupKey = marker.parameters?.group_name || marker.parameters?.zone_type || marker.label || marker.filename;
                const color = shadeFor(baseColor, groupKey);
                const layers = layerGroups[marker.filename] ||= [];
                const visualLayers = [];
                if (marker.radius) {
                    const area = L.circle([marker.worldZ, marker.worldX], { renderer:canvasRenderer, radius:Number(marker.radius), color, weight:1.5, fillColor:color, fillOpacity:.1, interactive:false }).addTo(map);
                    layers.push(area);
                    visualLayers.push({ layer:area, kind:'area' });
                }
                const imported = L.circleMarker([marker.worldZ, marker.worldX], { renderer:canvasRenderer, radius: 6, color, fillColor: color, fillOpacity: .9 }).addTo(map);
                layers.push(imported);
                visualLayers.push({ layer:imported, kind:'point' });
                markerRecords.push({ marker, color, visualLayers });
                const markerActions = marker.editable
                    ? [mkEl('br'), mkEl('button', {class:'dz-map-edit', type:'button', text:'Upravit souřadnice'}), ' ', mkEl('button', {class:'dz-map-delete', type:'button', text:'Smazat bod a vytvořit revizi'})]
                    : [mkEl('br'), mkEl('a', {href:'{{ url('/admin/projects/'.$projectId.'/configuration') }}?revision=' + marker.revision_id, text:'Otevřít příslušný editor'})];
                imported.bindPopup(mkEl('span', {}, [
                    mkEl('strong', {text:marker.label}),
                    mkEl('br'),
                    mkEl('small', {}, [marker.help, mkEl('br'), 'DayZ X/Z: ' + Math.round(marker.worldX) + ' / ' + Math.round(marker.worldZ)]),
                    ...markerActions,
                ]));
                imported.on('popupopen', (event) => {
                    const root = event.popup.getElement();
                    root.querySelector('.dz-map-edit')?.addEventListener('click', () => {
                        activeMarker = marker;
                        editModal.querySelector('.dz-edit-point-context').textContent = marker.label + ' · ' + marker.filename;
                        editModal.querySelector('.dz-edit-x').value = marker.worldX;
                        editModal.querySelector('.dz-edit-z').value = marker.worldZ;
                        const editDefinition = editDefinitionForMarker(marker);
                        buildPointFields(editModal.querySelector('.dz-edit-point-fields'), editDefinition?.fields || [], marker.parameters || {}, true);
                        const badge = editModal.querySelector('.dz-edit-point-contents-badge');
                        const cargoItemCount = (marker.parameters?.cargo_items || []).length;
                        const hasHoarder = !!marker.parameters?.hoarder;
                        const hasPreset = !!(marker.parameters?.cargo_preset || '').trim();
                        if (cargoItemCount || hasHoarder || hasPreset) {
                            const parts = [];
                            if (cargoItemCount) parts.push(cargoItemCount + ' konkrétních itemů uvnitř');
                            if (hasPreset) parts.push('cargo preset „' + marker.parameters.cargo_preset + '“');
                            if (hasHoarder) parts.push('hoarder');
                            badge.textContent = '📦 Tento bod má definovaný obsah (' + parts.join(', ') + ') — viz „Obsah kontejneru“ níže.';
                            badge.hidden = false;
                        } else {
                            badge.hidden = true;
                        }
                        editModal.hidden = false;
                    });
                    root.querySelector('.dz-map-delete')?.addEventListener('click', () => {
                        activeMarker = marker;
                        editModal.querySelector('.dz-edit-point-context').textContent = marker.label + ' · ' + marker.filename;
                        editModal.querySelector('.dz-edit-x').value = marker.worldX;
                        editModal.querySelector('.dz-edit-z').value = marker.worldZ;
                        const editDefinition = editDefinitionForMarker(marker);
                        buildPointFields(editModal.querySelector('.dz-edit-point-fields'), editDefinition?.fields || [], marker.parameters || {}, true);
                        editModal.hidden = false;
                    });
                });
            });
            const markerMatchesScope = (marker, filename, scope) => {
                if (marker.filename !== filename) return false;
                if (scope === 'all') return true;
                if (scope.startsWith('event:')) return marker.label === scope.slice(6);
                if (scope.startsWith('mode:')) return marker.path.includes('/playerspawnpoints/' + scope.slice(5) + '/');
                if (scope.startsWith('group:')) {
                    const [mode, name] = scope.slice(6).split('|');
                    return marker.path.includes('/playerspawnpoints/' + mode + '/') && marker.label.startsWith(name + ' ·');
                }
                return false;
            };
            const highlightScope = (filename, scope) => {
                markerRecords.forEach((record) => {
                    const sameLayer = record.marker.filename === filename;
                    const selected = scope && markerMatchesScope(record.marker, filename, scope);
                    record.visualLayers.forEach(({layer, kind}) => {
                        if (!scope || !sameLayer) {
                            layer.setStyle({ color:record.color, fillColor:record.color, opacity:1, fillOpacity:kind === 'area' ? .1 : .9, weight:kind === 'area' ? 1.5 : 1 });
                            if (kind === 'point') layer.setRadius(6);
                        } else if (selected) {
                            layer.setStyle({ color:'#ffffff', fillColor:record.color, opacity:1, fillOpacity:kind === 'area' ? .28 : 1, weight:kind === 'area' ? 3 : 4 });
                            if (kind === 'point') layer.setRadius(10);
                            layer.bringToFront?.();
                        } else {
                            layer.setStyle({ color:record.color, fillColor:record.color, opacity:.14, fillOpacity:kind === 'area' ? .025 : .08, weight:1 });
                            if (kind === 'point') layer.setRadius(4);
                        }
                    });
                });
            };
            editModal.querySelector('.dz-edit-save').onclick = async () => {
                if (!activeMarker) return;
                const newX = Number(editModal.querySelector('.dz-edit-x').value);
                const newZ = Number(editModal.querySelector('.dz-edit-z').value);
                if (!Number.isFinite(newX) || !Number.isFinite(newZ) || newX < 0 || newX > worldSize || newZ < 0 || newZ > worldSize) {
                    showFeedback(editModal, 'Souřadnice musí být v rozsahu 0–15 360.');
                    return;
                }
                const parameters = {};
                editModal.querySelectorAll('[data-parameter]').forEach((input) => parameters[input.dataset.parameter] = input.type === 'checkbox' ? input.checked : input.value);
                if (activeMarker.filename === 'mapgrouppos.xml' && parameters.name && parameters.name !== activeMarker.label) {
                    const known = (pointTypeCatalog.loot?.options || []).some((option) => option.value === parameters.name);
                    if (!known) {
                        const confirmed = await showSystemDialog({
                            title: 'Neznámý classname',
                            message: '„' + parameters.name + '“ není v katalogu z mapgroupproto.xml na tomhle serveru. Pokud tahle třída ve hře neexistuje (nebo patří k modu, který server nemá), na této pozici nevznikne žádný loot. Opravdu pokračovat?',
                            confirmLabel: 'Použít i tak',
                            danger: true,
                        });
                        if (!confirmed) return;
                    }
                }
                fetch('{{ route('map-editor.points.update') }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({project_id:@js($projectId),revision_id:activeMarker.revision_id,filename:activeMarker.filename,label:activeMarker.label,path:activeMarker.path,x:activeMarker.worldX,z:activeMarker.worldZ,new_x:newX,new_z:newZ,parameters})}).then(async (r) => {
                    if (!r.ok) throw new Error(await describeFetchError(r, 'Bod se nepodařilo upravit.'));
                    const result = await r.json();
                    const reloadUrl = new URL(window.location.href);
                    reloadUrl.searchParams.set('_map_revision', String(result.revision_id || Date.now()));
                    if (result.warning) {
                        showFeedback(editModal, result.warning, false);
                        setTimeout(() => { window.location.href = reloadUrl.toString(); }, 2200);
                    } else {
                        window.location.href = reloadUrl.toString();
                    }
                }).catch((error) => showFeedback(editModal, error.message));
            };
            const deleteConsequence = (filename) => {
                if (filename === 'cfgplayerspawnpoints.xml') {
                    return 'Pokud to byl poslední bod v jeho skupině (fresh/hop/travel), daný režim spawnu zůstane bez platné skupiny a hráči dostanou "no valid groups"/"NO VALID SPAWNS".';
                }
                if (filename === 'cfgeventspawns.xml') {
                    return 'Pokud to byla poslední pozice pro tuto událost, událost zůstane definovaná v events.xml, ale nebude mít kam spawnovat — fakticky přestane fungovat.';
                }
                if (filename === 'mapgrouppos.xml') {
                    return 'Vizuální model budovy tím nezmizí (ten je daný terénem mapy) — zmizí jen tenhle loot bod, takže na této pozici přestane vznikat loot podle mapgroupproto.xml.';
                }
                return 'Souřadnice ani jiné soubory tím jinak neovlivníš.';
            };
            editModal.querySelector('.dz-edit-delete').onclick = async () => {
                if (!activeMarker) return;
                const confirmed = await showSystemDialog({
                    title: 'Smazat bod?',
                    message: 'Opravdu odstranit tento bod (' + activeMarker.filename + ')? Vytvoří se nová revize, originál zůstane zachovaný. ' + deleteConsequence(activeMarker.filename),
                    confirmLabel: 'Smazat bod',
                    danger: true,
                });
                if (!confirmed) return;
                fetch('{{ route('map-editor.points.delete') }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({project_id:@js($projectId),revision_id:activeMarker.revision_id,filename:activeMarker.filename,label:activeMarker.label,path:activeMarker.path,x:activeMarker.worldX,z:activeMarker.worldZ})}).then(async (r) => { if (!r.ok) throw new Error(await describeFetchError(r, 'Bod se nepodařilo odstranit.')); const result = await r.json(); const reloadUrl = new URL(window.location.href); reloadUrl.searchParams.set('_map_revision', String(result.revision_id || Date.now())); window.location.href = reloadUrl.toString(); }).catch((error) => showFeedback(editModal, error.message));
            };
            const layerScopeData = @js($layerScopes);
            const cleanupModal = document.getElementById('dz-cleanup-modal');
            const cleanupList = cleanupModal.querySelector('.dz-cleanup-list');
            const cleanupConfirm = cleanupModal.querySelector('.dz-cleanup-confirm');
            let cleanupTarget = null;
            const closeCleanupModal = () => { cleanupModal.hidden = true; cleanupTarget = null; };
            cleanupModal.querySelector('.dz-point-close').onclick = closeCleanupModal;
            cleanupModal.querySelector('.dz-cleanup-cancel').onclick = closeCleanupModal;
            cleanupModal.addEventListener('click', (event) => { if (event.target === cleanupModal) closeCleanupModal(); });
            document.querySelectorAll('.dz-layer-cleanup-open').forEach((button) => {
                button.addEventListener('click', () => {
                    const filename = button.dataset.filename;
                    cleanupTarget = { filename, revisionId: Number(button.dataset.revision) };
                    cleanupModal.querySelector('#dz-cleanup-modal-title').textContent = 'Vybrat skupiny k odstranění · ' + filename;
                    const scopes = layerScopeData[filename] || [];
                    const options = scopes.map((scope) => mkEl('label', { class: 'dz-cleanup-option' }, [
                        mkEl('input', { type: 'checkbox', value: scope.value }),
                        mkEl('span', {}, [scope.label + ' · ' + scope.count + ' bodů']),
                    ]));
                    options.push(mkEl('label', { class: 'dz-cleanup-option dz-cleanup-option-all' }, [
                        mkEl('input', { type: 'checkbox', value: 'all' }),
                        mkEl('span', {}, ['VŠECHNY BODY VRSTVY · ' + button.dataset.count]),
                    ]));
                    cleanupList.replaceChildren(...options);
                    cleanupModal.querySelector('.dz-map-feedback').hidden = true;
                    cleanupModal.hidden = false;
                });
            });
            cleanupConfirm.onclick = async () => {
                if (!cleanupTarget) return;
                const checked = Array.from(cleanupList.querySelectorAll('input:checked')).map((input) => input.value);
                if (checked.length === 0) {
                    showFeedback(cleanupModal, 'Vyber aspoň jednu skupinu.');
                    return;
                }
                const confirmed = await showSystemDialog({
                    title: 'Odstranit mapové body?',
                    message: 'Opravdu odstranit ' + checked.length + ' vybranou/vybrané skupinu/skupiny? Vznikne nová revize a původní zůstane zachována. ' + deleteConsequence(cleanupTarget.filename),
                    confirmLabel: 'Odstranit body',
                    danger: true,
                });
                if (!confirmed) return;
                cleanupConfirm.disabled = true;
                cleanupConfirm.textContent = 'Odstraňuji…';
                try {
                    const response = await fetch('{{ route('map-editor.points.bulk-delete') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ project_id: @js($projectId), revision_id: cleanupTarget.revisionId, filename: cleanupTarget.filename, scopes: checked }),
                    });
                    if (!response.ok) throw new Error(await describeFetchError(response, 'Body se nepodařilo odstranit.'));
                    window.location.reload();
                } catch (error) {
                    showFeedback(cleanupModal, error.message);
                    cleanupConfirm.disabled = false;
                    cleanupConfirm.textContent = 'Odstranit vybrané';
                }
            };
            cleanupList?.addEventListener('change', (event) => {
                if (event.target.matches('input[type="checkbox"]') && cleanupTarget) {
                    const checkedValues = Array.from(cleanupList.querySelectorAll('input:checked')).map((input) => input.value);
                    highlightScope(cleanupTarget.filename, checkedValues.length === 1 ? checkedValues[0] : null);
                }
            });
            document.querySelectorAll('.dz-load-dense').forEach((link) => {
                link.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const count = Number(link.dataset.count || 0);
                    const confirmed = await showSystemDialog({
                        title: 'Načíst hustou vrstvu?',
                        message: link.dataset.filename + ' obsahuje ' + count.toLocaleString('cs-CZ') + ' bodů. Načtení a vykreslení může na pomalejším připojení nebo starším počítači trvat i několik desítek sekund — stránka se během toho nebude hýbat, to je normální.',
                        confirmLabel: 'Načíst i tak',
                        danger: count > 20000,
                    });
                    if (!confirmed) return;
                    link.classList.add('dz-load-dense-loading');
                    link.querySelector('span').innerHTML = '';
                    link.querySelector('span').appendChild(mkEl('span', {}, ['Načítám ' + count.toLocaleString('cs-CZ') + ' bodů…']));
                    link.querySelector('span').appendChild(mkEl('small', {}, ['Stránka se teď na chvíli nebude hýbat, počkej prosím.']));
                    window.location.href = link.href;
                });
            });
            document.querySelectorAll('.dz-source-raw').forEach((button) => {
                button.addEventListener('click', async () => {
                    rawModal.querySelector('#dz-raw-modal-title').textContent = button.dataset.filename;
                    rawStatus.textContent = 'Načítám obsah revize…';
                    rawStatus.classList.remove('error');
                    rawCode.textContent = '';
                    rawText = '';
                    rawCopy.disabled = true;
                    rawCopy.textContent = 'Kopírovat';
                    rawModal.hidden = false;
                    try {
                        const response = await fetch(button.dataset.url, { headers:{'Accept':'text/plain'} });
                        if (!response.ok) throw new Error('Raw obsah se nepodařilo načíst (HTTP ' + response.status + ').');
                        rawText = await response.text();
                        rawCode.textContent = rawText;
                        rawStatus.textContent = 'Načteno ' + rawText.length.toLocaleString() + ' znaků. Obsah je pouze pro čtení.';
                        rawCopy.disabled = false;
                    } catch (error) {
                        rawStatus.textContent = error.message;
                        rawStatus.classList.add('error');
                    }
                });
            });
            const layerPrefsKey = 'dz-map-layer-prefs-' + @js($projectId);
            const layerPrefs = (() => {
                try { return JSON.parse(localStorage.getItem(layerPrefsKey) || '{}'); } catch { return {}; }
            })();
            const saveLayerPrefs = () => { try { localStorage.setItem(layerPrefsKey, JSON.stringify(layerPrefs)); } catch {} };
            document.querySelectorAll('.map-layer-toggle').forEach((toggle) => {
                const filename = toggle.dataset.layer;
                if (layerPrefs.visible && Object.prototype.hasOwnProperty.call(layerPrefs.visible, filename)) {
                    toggle.checked = layerPrefs.visible[filename];
                }
                const syncLayer = () => {
                    (layerGroups[filename] || []).forEach((layer) => toggle.checked ? layer.addTo(map) : map.removeLayer(layer));
                    layerPrefs.visible = layerPrefs.visible || {};
                    layerPrefs.visible[filename] = toggle.checked;
                    saveLayerPrefs();
                };
                toggle.addEventListener('change', syncLayer);
                syncLayer();
            });
            document.querySelectorAll('.map-layer-label-toggle').forEach((toggle) => {
                const filename = toggle.dataset.layer;
                if (layerPrefs.labels && Object.prototype.hasOwnProperty.call(layerPrefs.labels, filename)) {
                    toggle.checked = layerPrefs.labels[filename];
                }
                const syncLabels = () => {
                    markerRecords.filter((record) => record.marker.filename === filename).forEach(({ marker, visualLayers }) => {
                        visualLayers.forEach(({ layer, kind }) => {
                            if (kind !== 'point') return;
                            if (toggle.checked) {
                                if (!layer.getTooltip()) layer.bindTooltip(marker.label, { permanent: true, direction: 'top', offset: [0, -8], className: 'dz-map-label' });
                            } else if (layer.getTooltip()) {
                                layer.unbindTooltip();
                            }
                        });
                    });
                    layerPrefs.labels = layerPrefs.labels || {};
                    layerPrefs.labels[filename] = toggle.checked;
                    saveLayerPrefs();
                };
                toggle.addEventListener('change', syncLabels);
                syncLabels();
            });
        });
        </script>
        @endif
    </div>
</x-filament-panels::page>

