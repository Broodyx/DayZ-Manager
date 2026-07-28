<x-filament-panels::page>
    @php
        $catalog = app(\App\Services\Dayz\ConfigurationCatalog::class);
        $areaKey = request('area') ?: 'server';
        $area = $catalog->areas()[$areaKey] ?? $catalog->areas()['server'];
        $files = $catalog->filesByArea()[$areaKey] ?? [];
        $selectedProject = request()->integer('project')
            ? \App\Models\Project::query()->when(!auth()->user()?->is_admin,fn($q)=>$q->where('user_id',auth()->id()))->find(request()->integer('project'))
            : null;
    @endphp
    <div class="dz-import-shell">
        <header class="dz-import-hero">
            <div>
                <p class="dz-kicker">NOVÝ IMPORT · {{ mb_strtoupper($area['label']) }}</p>
                <h2>Nahrajte aktuální konfiguraci</h2>
                <p>Soubor nejprve bezpečně uložíme, ověříme jeho formát a vytvoříme revizi. Původní data se nikdy nepřepisují.</p>
            </div>
            <a href="{{ url('/admin/configuration-wizard'.($selectedProject ? '?project='.$selectedProject->id : '')) }}">← Zpět na checklist</a>
        </header>

        <div class="dz-import-layout">
            <form method="POST" action="{{ route('configuration-import.upload') }}" enctype="multipart/form-data" class="dz-import-form dz-import-form-new">
                @csrf
                <div class="dz-form-section">
                    <span class="dz-form-step">01</span>
                    <div class="dz-area-field">
                        <strong>Oblast konfigurace</strong>
                        <small>{{ $area['description'] }}</small>
                        <input type="hidden" name="area" value="{{ $areaKey }}">
                        <div class="dz-area-picker" role="radiogroup" aria-label="Oblast konfigurace">
                            @foreach ($catalog->areas() as $key => $areaOption)
                                <a href="{{ url('/admin/configuration-import') }}?area={{ $key }}&project={{ request()->integer('project') }}"
                                    class="dz-area-card {{ $areaKey === $key ? 'active' : '' }}"
                                    role="radio" aria-checked="{{ $areaKey === $key ? 'true' : 'false' }}">
                                    <strong>{{ $areaOption['label'] }}</strong>
                                    <small>{{ $areaOption['files'] }}</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="dz-form-section">
                    <span class="dz-form-step">02</span>
                    <label>
                        <strong>Cílový server</strong>
                        <select name="project_id" required>
                            <option value="">Vyberte server…</option>
                            @foreach(\App\Models\Project::query()->when(!auth()->user()?->is_admin,fn($q)=>$q->where('user_id',auth()->id()))->orderBy('name')->get() as $project)
                                <option value="{{ $project->id }}" @selected((int)request('project') === (int)$project->id)>{{ $project->name }} · {{ $project->map }}</option>
                            @endforeach
                        </select>
                        <small>Import i všechny následující revize zůstanou přiřazené tomuto serveru.</small>
                    </label>
                </div>
                <div class="dz-form-section">
                    <span class="dz-form-step">03</span>
                    <label>
                        <strong>Platforma serveru</strong>
                        <select name="platform" required>
                            <option value="">Vyberte platformu…</option>
                            <option value="playstation" @selected($selectedProject?->platform === 'playstation')>PlayStation</option>
                            <option value="xbox" @selected($selectedProject?->platform === 'xbox')>Xbox</option>
                            <option value="steam" @selected($selectedProject?->platform === 'steam')>PC / Steam</option>
                        </select>
                        <small>Podle platformy provedeme kontrolu kompatibility a označíme PC-only prvky.</small>
                    </label>
                </div>
                <div class="dz-form-section">
                    <span class="dz-form-step">04</span>
                    <div class="dz-upload-drop" id="dz-multi-upload">
                        <label class="dz-upload-browse-button" for="dz-files-input">
                            <span>📂 Vybrat soubory z počítače…</span>
                            <small>Podrž Ctrl (Windows) nebo Cmd (Mac) a klikni na víc souborů najednou — nebo přetáhni víc souborů/ZIP sem.</small>
                        </label>
                        <input id="dz-files-input" type="file" name="files[]" multiple required accept=".cfg,.txt,.xml,.json,.zip,.c">
                        <span>XML, JSON, CFG, TXT, init.c nebo ZIP · každý soubor se zařadí podle svého jména automaticky · maximálně 100 MB na soubor</span>
                        @if (request('expected'))
                            <b>Průvodce očekává: {{ request('expected') }}</b>
                        @endif
                        <ul class="dz-upload-file-list" id="dz-upload-file-list" hidden></ul>
                    </div>
                </div>
                <button type="submit" class="dz-import-submit">Ověřit, importovat a otevřít editor <span>→</span></button>
                <script>
                    (function () {
                        const input = document.getElementById('dz-files-input');
                        const list = document.getElementById('dz-upload-file-list');
                        const drop = document.getElementById('dz-multi-upload');
                        if (!input || !list || !drop) return;

                        const render = () => {
                            const files = Array.from(input.files || []);
                            list.replaceChildren();
                            if (files.length === 0) { list.hidden = true; return; }
                            const heading = document.createElement('li');
                            heading.className = 'dz-upload-file-count';
                            heading.textContent = files.length === 1 ? 'Vybrán 1 soubor:' : `Vybráno ${files.length} souborů:`;
                            list.appendChild(heading);
                            files.forEach((file) => {
                                const item = document.createElement('li');
                                item.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' kB)';
                                list.appendChild(item);
                            });
                            list.hidden = false;
                        };

                        input.addEventListener('change', render);
                        ['dragover', 'dragenter'].forEach((eventName) => {
                            drop.addEventListener(eventName, (event) => { event.preventDefault(); drop.classList.add('dz-upload-drop-active'); });
                        });
                        ['dragleave', 'drop'].forEach((eventName) => {
                            drop.addEventListener(eventName, (event) => { event.preventDefault(); drop.classList.remove('dz-upload-drop-active'); });
                        });
                        drop.addEventListener('drop', (event) => {
                            if (event.dataTransfer?.files?.length) {
                                input.files = event.dataTransfer.files;
                                render();
                            }
                        });
                    })();
                </script>
            </form>

            <aside class="dz-import-guide">
                <p class="dz-kicker">CO SEM PATŘÍ</p>
                <h3>{{ $area['label'] }}</h3>
                <div>
                    @foreach ($files as $file)
                        <article @class(['expected' => request('expected') === $file['filename']])>
                            <code>{{ $file['filename'] }}</code>
                            <p>{{ $file['description'] }}</p>
                        </article>
                    @endforeach
                </div>
                <footer>
                    <x-filament::icon icon="heroicon-o-shield-check" />
                    <span><strong>Bezpečný import</strong><small>Validace proběhne před editací. Neplatný soubor zůstane uložený pro diagnostiku, ale nebude přepsána žádná funkční revize.</small></span>
                </footer>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
