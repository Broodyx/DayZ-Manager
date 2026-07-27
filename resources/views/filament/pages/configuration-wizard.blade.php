<x-filament-panels::page>
    @php
        $totalFiles = collect($areas)->sum(fn ($area) => count($area['entries']));
        $readyFiles = collect($areas)->sum(fn ($area) => collect($area['entries'])->where('uploaded_count', '>', 0)->count());
        $progress = $totalFiles ? (int) round(($readyFiles / $totalFiles) * 100) : 0;
    @endphp
    <div class="dz-setup">
        <header class="dz-setup-hero">
            <div>
                <p class="dz-kicker">CENTRUM NASTAVENÍ</p>
                <h2>Konfigurace bez hledání názvů souborů</h2>
                <p>Vyberte server a oblast. Každý soubor má vlastní vysvětlení a jednu jasnou akci: nahrát chybějící konfiguraci, nebo pokračovat v její poslední revizi.</p>
            </div>
            <div class="dz-setup-server">
                @if ($projects)
                    <label>
                        <span>Aktivní server</span>
                        <select onchange="window.location.href='{{ url('/admin/configuration-wizard') }}?project='+this.value">
                            @foreach ($projects as $id => $name)
                                <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="dz-setup-progress">
                        <span><b>{{ $readyFiles }}</b> z {{ $totalFiles }} typů souborů nahráno</span>
                        <i><b style="width:{{ $progress }}%"></b></i>
                        <small>{{ $progress }} % pokrytí katalogu. Není nutné nahrávat soubory, které váš hosting nepoužívá.</small>
                    </div>
                    @if ($readyFiles > 0)
                        <a class="dz-secondary" href="{{ route('project.configuration.download-all', ['project' => $projectId]) }}">Stáhnout všechny soubory (ZIP) →</a>
                    @endif
                @else
                    <div class="dz-empty-state">
                        <strong>Zatím nemáte žádný server</strong>
                        <span>Založte ho a průvodce vám sestaví konkrétní checklist.</span>
                        <a href="{{ url('/admin/projects/create') }}">Založit první server</a>
                    </div>
                @endif
            </div>
        </header>

        <nav class="dz-setup-shortcuts" aria-label="Rychlé oblasti konfigurace">
            @foreach ($areas as $key => $area)
                <a href="#area-{{ $key }}">
                    <x-filament::icon :icon="$area['icon']" />
                    <span>{{ $area['label'] }}<small>{{ collect($area['entries'])->where('uploaded_count', '>', 0)->count() }}/{{ count($area['entries']) }}</small></span>
                </a>
            @endforeach
        </nav>

        <div class="dz-setup-areas">
            @foreach ($areas as $key => $area)
                @php $areaReady = collect($area['entries'])->where('uploaded_count', '>', 0)->count(); @endphp
                <details id="area-{{ $key }}" class="dz-setup-area" @if ($loop->first || $areaReady) open @endif>
                    <summary>
                        <span class="dz-setup-area-icon"><x-filament::icon :icon="$area['icon']" /></span>
                        <span class="dz-setup-area-title">
                            <strong>{{ $area['label'] }}</strong>
                            <small>{{ $area['description'] }}</small>
                        </span>
                        <span class="dz-setup-area-count">{{ $areaReady }}/{{ count($area['entries']) }}</span>
                        <span class="dz-setup-chevron">⌄</span>
                    </summary>
                    <div class="dz-setup-files">
                        @foreach ($area['entries'] as $entry)
                            <article class="dz-setup-file {{ $entry['uploaded_count'] ? 'is-ready' : 'is-missing' }}">
                                <div class="dz-file-status-icon">
                                    @if ($entry['uploaded_count'])
                                        <x-filament::icon icon="heroicon-o-check" />
                                    @else
                                        <x-filament::icon icon="heroicon-o-plus" />
                                    @endif
                                </div>
                                <div class="dz-file-copy">
                                    <strong><code>{{ $entry['actual_filename'] ?: $entry['filename'] }}</code></strong>
                                    <p>{{ $entry['description'] }}</p>
                                    @if ($entry['uploaded_count'])
                                        <small>Aktuální revize #{{ $entry['revision_number'] }}{{ $entry['uploaded_count'] > 1 ? ' · '.$entry['uploaded_count'].' odpovídající soubory' : '' }}</small>
                                    @else
                                        <small>Nenahráno · doplňte pouze pokud tento soubor váš server používá</small>
                                    @endif
                                </div>
                                <a class="dz-file-action" href="{{ $entry['url'] }}">
                                    {{ $entry['uploaded_count'] ? 'Otevřít editor' : 'Nahrát soubor' }}
                                    <span>→</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    @if ($key === 'map' && $projectId)
                        <footer class="dz-setup-area-footer">
                            <span>Máte souřadnicové soubory? Zobrazte je společně nad mapou Chernarus.</span>
                            <a href="{{ url('/admin/map-editor?project='.$projectId) }}">Otevřít mapový editor →</a>
                        </footer>
                    @endif
                </details>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
