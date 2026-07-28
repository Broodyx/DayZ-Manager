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
            <div class="dz-log-actions" style="margin-bottom:.85rem">
                <button type="button" wire:click="up" class="dz-secondary" @disabled($currentPath === '')>← O úroveň výš</button>
                <button type="button" wire:click="loadDirectory" class="dz-secondary">Obnovit</button>
                @if ($fileCountInFolder > 0)
                    <button type="button" class="dz-action" x-on:click="dzConfirm('Importovat všech {{ $fileCountInFolder }} souborů z téhle složky jako nové konfigurační revize? Nesestupuje do podsložek.').then((ok) => { if (ok) $wire.importAllInFolder(); })">Importovat všechny soubory v této složce ({{ $fileCountInFolder }})</button>
                @endif
            </div>

            <div class="dz-whitelist-list">
                @forelse ($entries as $entry)
                    <div class="dz-whitelist-row">
                        <span class="dz-whitelist-row-id">
                            @if ($entry['type'] === 'dir')
                                <button type="button" wire:click="open('{{ $entry['path'] }}')" class="dz-secondary">📁 {{ $entry['name'] }}</button>
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
                        </span>
                        @if ($entry['type'] === 'file')
                            <button type="button" class="dz-danger" x-on:click="dzConfirm('Importovat »{{ $entry['name'] }}« ze serveru jako novou konfigurační revizi?').then((ok) => { if (ok) $wire.importFile(@js($entry['path'])); })">Importovat</button>
                        @endif
                    </div>
                @empty
                    <p class="dz-muted">Tahle složka je prázdná.</p>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
