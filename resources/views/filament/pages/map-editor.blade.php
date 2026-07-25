<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <div class="dz-map-page">
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
            </div>
        </div>
        <div class="dz-map-toolbar">
            <div>
                <p class="dz-eyebrow">DAYZ MAP EDITOR</p>
                <h2>Mapa {{ $map }}</h2>
                <p class="dz-muted">Přehled spawnů, heli crashů, konvojů a dalších eventů z konfigurace serveru.</p>
            </div>
            <select wire:model.live="projectId" class="dz-map-select" aria-label="Server">
                @foreach ($projects as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <div class="dz-map-upload">
                <label class="dz-file-button">Vybrat soubor<input type="file" wire:model="mapFile" accept=".xml,.json,.zip"></label>
                <button type="button" wire:click="importMapConfiguration" wire:loading.attr="disabled">Nahrát mapovou konfiguraci</button>
                <small>cfgeventspawns.xml, mapgrouppos.xml, events.xml nebo ZIP</small>
            </div>
        </div>
        <div class="dz-map-layout">
            <section wire:ignore class="dz-map-canvas" aria-label="Mapa serveru">
                <div id="dayz-leaflet-map"></div>
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('dayz-leaflet-map');
            if (!el || el.dataset.ready) return;
            el.dataset.ready = '1';
            const map = L.map(el, { crs: L.CRS.Simple, minZoom: -2, maxZoom: 4, zoomSnap: 0.25 });
            const bounds = [[0, 0], [2900, 3000]];
            L.imageOverlay('/maps/chernarus_big_hq.jpg', bounds).addTo(map);
            L.rectangle(bounds, { color: '#b8ed55', weight: 1, fill: false, opacity: .35 }).addTo(map);
            map.fitBounds(bounds);
            L.control.scale({ imperial: false }).addTo(map);
            map.on('click', (event) => {
                if (!event.originalEvent.ctrlKey) return;
                const modal = document.getElementById('dz-point-modal');
                modal.hidden = false;
                modal.dataset.lat = event.latlng.lat;
                modal.dataset.lng = event.latlng.lng;
            });
            const modal = document.getElementById('dz-point-modal');
            modal.querySelector('.dz-point-close').onclick = () => modal.hidden = true;
            modal.querySelectorAll('[data-point]').forEach((button) => button.onclick = () => {
                const label = button.textContent.trim();
                L.marker([Number(modal.dataset.lat), Number(modal.dataset.lng)]).addTo(map).bindPopup('<strong>' + label + '</strong><br>Nový bod · konfigurace').openPopup();
                modal.hidden = true;
            });
            const markers = @js($markers);
            markers.forEach((marker) => {
                const px = (marker.worldX ?? (marker.x * 15360 / 100)) / 15360 * 3000;
                const py = 2900 - ((marker.worldZ ?? ((100 - marker.y) * 15360 / 100)) / 15360 * 2900);
                L.circleMarker([py, px], { radius: 6, color: '#b8ed55', fillColor: '#b8ed55', fillOpacity: .9 }).addTo(map).bindTooltip(marker.label);
            });
        });
    </script>
</x-filament-panels::page>
