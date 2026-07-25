<x-filament-panels::page>
    <div class="dz-map-page">
        <div class="dz-map-toolbar">
            <div>
                <p class="dz-eyebrow">DAYZ MAP EDITOR</p>
                <h2>Mapa {{ $map }}</h2>
                <p class="dz-muted">Přehled spawnů, heli crashů, konvojů a dalších eventů z konfigurace serveru.</p>
            </div>
            <select wire:model.live="map" class="dz-map-select">
                <option>Chernarus</option>
                <option>Livonia</option>
                <option>Namalsk</option>
            </select>
            <select wire:model.live="projectId" class="dz-map-select" aria-label="Server">
                @foreach ($projects as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <div class="dz-map-upload">
                <input type="file" wire:model="mapFile" accept=".xml,.json,.zip">
                <button type="button" wire:click="importMapConfiguration" wire:loading.attr="disabled">Nahrát mapovou konfiguraci</button>
                <small>cfgeventspawns.xml, mapgrouppos.xml, events.xml nebo ZIP</small>
            </div>
        </div>
        <div class="dz-map-layout">
            <section class="dz-map-canvas" aria-label="Mapa serveru">
                <div class="dz-map-grid"></div>
                <div class="dz-map-land">CHERNARUS</div>
                @forelse ($markers as $marker)
                    <button class="dz-map-marker dz-map-marker-{{ $marker['type'] }}" style="left: {{ $marker['x'] }}%; top: {{ $marker['y'] }}%" title="{{ $marker['label'] }}">
                        <span></span>{{ $marker['label'] }}
                    </button>
                @empty
                    <div class="dz-map-empty">Vybraný server zatím nemá importované souřadnice z mapových XML.</div>
                @endforelse
            </section>
            <aside class="dz-map-legend">
                <h3>Vrstvy mapy</h3>
                <label><input type="checkbox" checked> Loot skupiny</label>
                <label><input type="checkbox" checked> Heli crash</label>
                <label><input type="checkbox" checked> Konvoje</label>
                <label><input type="checkbox" checked> Event spawny</label>
                <hr>
                <p class="dz-muted">Napojení na reálné souřadnice z <code>cfgeventspawns.xml</code> a <code>mapgrouppos.xml</code> bude použito při importu konfigurace.</p>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
