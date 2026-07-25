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
        </div>
        <div class="dz-map-layout">
            <section class="dz-map-canvas" aria-label="Mapa serveru">
                <div class="dz-map-grid"></div>
                <div class="dz-map-land">CHERNARUS</div>
                @foreach ($markers as $marker)
                    <button class="dz-map-marker dz-map-marker-{{ $marker['type'] }}" style="left: {{ $marker['x'] }}%; top: {{ $marker['y'] }}%" title="{{ $marker['label'] }}">
                        <span></span>{{ $marker['label'] }}
                    </button>
                @endforeach
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
