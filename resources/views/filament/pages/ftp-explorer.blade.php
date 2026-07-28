<x-filament-panels::page>
    <div class="dz-log-page">
        @if ($projects)
            <label class="dz-log-server-picker">
                <span>Server</span>
                <select onchange="window.location.href='{{ url('/admin/ftp-explorer') }}?project='+this.value">
                    @foreach ($projects as $id => $name)
                        <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <div class="dz-info">Zatím nemáš žádný server. Založ ho a pak mu v Nastavení serveru doplň FTP údaje.</div>
        @endif

        @if ($errorMessage)
            <div class="dz-flash-warning">{{ $errorMessage }}</div>
        @endif

        @if ($connected)
            <div class="dz-flash-success"><strong>Připojeno.</strong> Cesta: <code>/{{ $currentPath }}</code></div>

            @php $fileCountInFolder = collect($entries)->where('type', 'file')->count(); @endphp
            <div class="dz-log-actions" style="margin-bottom:.85rem" wire:loading.class="dz-ftp-busy" wire:target="importAllInFolder,importFile">
                <button type="button" wire:click="up" class="dz-secondary" @disabled($currentPath === '') wire:loading.attr="disabled" wire:target="importAllInFolder,importFile">← O úroveň výš</button>
                <button type="button" wire:click="loadDirectory" class="dz-secondary" wire:loading.attr="disabled" wire:target="importAllInFolder,importFile">Obnovit</button>
                @if ($fileCountInFolder > 0)
                    <button type="button" class="dz-action" wire:loading.attr="disabled" wire:target="importAllInFolder,importFile"
                        x-on:click="dzConfirm('Importovat všech {{ $fileCountInFolder }} souborů z téhle složky jako nové konfigurační revize? Nesestupuje do podsložek.').then((ok) => { if (ok) $wire.importAllInFolder(); })">
                        <span wire:loading.remove wire:target="importAllInFolder">Importovat všechny soubory v této složce ({{ $fileCountInFolder }})</span>
                        <span wire:loading wire:target="importAllInFolder">⏳ Importuji {{ $fileCountInFolder }} souborů, chvíli vydrž…</span>
                    </button>
                @endif
            </div>

            @if ($lastImportSummary)
                <div class="{{ $lastImportSummary['failed'] === [] ? 'dz-flash-success' : 'dz-flash-warning' }}" style="margin-bottom:.85rem">
                    @if (count($lastImportSummary['imported']))
                        <div><strong>Importováno ({{ count($lastImportSummary['imported']) }}):</strong> {{ implode(', ', $lastImportSummary['imported']) }}</div>
                    @endif
                    @if (count($lastImportSummary['failed']))
                        <div><strong>Selhalo ({{ count($lastImportSummary['failed']) }}):</strong> {{ implode(' | ', $lastImportSummary['failed']) }}</div>
                    @endif
                    @if ($lastImportSummary['imported'] === [] && $lastImportSummary['failed'] === [])
                        <div>Nic k importu — složka neobsahuje žádné soubory.</div>
                    @endif
                </div>
            @endif

            <div class="dz-whitelist-list" wire:loading.class="dz-ftp-busy" wire:target="importAllInFolder">
                @forelse ($entries as $entry)
                    <div class="dz-whitelist-row">
                        <span class="dz-whitelist-row-id">
                            @if ($entry['type'] === 'dir')
                                <button type="button" wire:click="open('{{ $entry['path'] }}')" class="dz-secondary" wire:loading.attr="disabled" wire:target="importAllInFolder,importFile">📁 {{ $entry['name'] }}</button>
                            @else
                                <code>📄 {{ $entry['name'] }}</code>
                            @endif
                            @if ($entry['size'] !== null)
                                <span class="dz-whitelist-comment">{{ number_format($entry['size'] / 1024, 1) }} kB</span>
                            @endif
                            @if ($entry['known'])
                                <span class="dz-badge dz-log-history-badge-known">známý</span>
                            @else
                                <span class="dz-badge dz-log-history-badge-critical">atypické</span>
                            @endif
                            @if ($entry['type'] === 'file')
                                @if (!empty($entry['last_imported_at']))
                                    <span class="dz-whitelist-comment">✓ importováno {{ $entry['last_imported_at'] }}</span>
                                @else
                                    <span class="dz-whitelist-comment">ještě neimportováno</span>
                                @endif
                            @endif
                        </span>
                        @if ($entry['type'] === 'file')
                            <button type="button" class="dz-danger" wire:loading.attr="disabled" wire:target="importAllInFolder,importFile"
                                x-on:click="dzConfirm('Importovat »{{ $entry['name'] }}« ze serveru jako novou konfigurační revizi?').then((ok) => { if (ok) $wire.importFile(@js($entry['path'])); })">
                                <span wire:loading.remove wire:target="importFile">Importovat</span>
                                <span wire:loading wire:target="importFile">⏳ Importuji…</span>
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="dz-muted">Tahle složka je prázdná.</p>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
