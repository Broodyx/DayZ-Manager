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
                <div id="dz-event-catalog" class="dz-event-catalog" hidden><label>Konfigurace eventu / vozidla</label><select></select><input class="dz-point-name" placeholder="Název bodu / vozidla"><input class="dz-point-radius" type="number" min="1" max="5000" value="50" placeholder="Poloměr zóny (m)"><small>Vyberte konkrétní event nebo zadejte vlastní název. U zón lze nastavit poloměr.</small><button type="button" class="dz-point-confirm">Umístit bod</button></div>
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
            const coordinateControl = L.control({ position: 'bottomleft' });
            coordinateControl.onAdd = () => { const div = L.DomUtil.create('div', 'dz-coordinate-control'); div.textContent = 'X: — · Z: —'; return div; };
            coordinateControl.addTo(map);
            const coordinateGrid = L.GridLayer.extend({
                createTile: function (coords) {
                    const tile = L.DomUtil.create('canvas', 'leaflet-tile'); tile.width = 256; tile.height = 256;
                    const ctx = tile.getContext('2d'); const z = map.getZoom(); const scale = map.getZoomScale(1, z);
                    const step = z >= 1 ? 250 : (z < -1 ? 1000 : 500); const size = 15360 / step;
                    ctx.strokeStyle = 'rgba(184,237,85,.28)'; ctx.fillStyle = 'rgba(231,247,210,.7)'; ctx.lineWidth = 1; ctx.font = '10px sans-serif';
                    for (let i = 0; i <= size; i++) { const world = i * step; const px = (world / 15360 * 3000) * scale - coords.x * 256; const py = (2900 - world / 15360 * 2900) * scale - coords.y * 256; if (px >= 0 && px <= 256) { ctx.beginPath(); ctx.moveTo(px, 0); ctx.lineTo(px, 256); ctx.stroke(); } if (py >= 0 && py <= 256) { ctx.beginPath(); ctx.moveTo(0, py); ctx.lineTo(256, py); ctx.stroke(); } }
                    return tile;
                }
            });
            new coordinateGrid({ opacity: 0.8, zIndex: 450 }).addTo(map);
            map.on('mousemove', (event) => { const x = Math.round(event.latlng.lng / 3000 * 15360); const z = Math.round((1 - event.latlng.lat / 2900) * 15360); document.querySelector('.dz-coordinate-control').textContent = 'X: ' + x.toLocaleString() + ' · Z: ' + z.toLocaleString(); });
            map.on('click', (event) => {
                if (!event.originalEvent.ctrlKey) return;
                const modal = document.getElementById('dz-point-modal');
                modal.hidden = false;
                modal.dataset.lat = event.latlng.lat;
                modal.dataset.lng = event.latlng.lng;
            });
            const modal = document.getElementById('dz-point-modal');
            modal.querySelector('.dz-point-close').onclick = () => modal.hidden = true;
            let pendingButton = null;
            const placePoint = (button) => {
                const label = button.textContent.trim();
                if ((button.dataset.point === 'vehicle' || button.dataset.point === 'dynamic' || button.dataset.point === 'animal' || button.dataset.point === 'infected') && pendingButton !== button) {
                    pendingButton = button;
                    const catalog = document.getElementById('dz-event-catalog'); const select = catalog.querySelector('select');
                    select.innerHTML = '<option value="">Vyberte existující event...</option>' + @js($eventCatalog).map((item) => '<option>' + item.name + '</option>').join(''); catalog.hidden = false;
                    return;
                }
                const chosen = document.querySelector('#dz-event-catalog select')?.value || document.querySelector('.dz-point-name')?.value || label;
                const radius = Number(document.querySelector('.dz-point-radius')?.value || 50);
                const marker = L.marker([Number(modal.dataset.lat), Number(modal.dataset.lng)]).addTo(map);
                fetch('{{ route('map-editor.points.store') }}', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({project_id: @js($projectId), type: button.dataset.point, label: chosen || label, x: Math.round(Number(modal.dataset.lng) / 3000 * 15360), z: Math.round((1 - Number(modal.dataset.lat) / 2900) * 15360)}) }).then((response) => { if (!response.ok) throw new Error('Uložení bodu selhalo'); }).catch(() => window.alert('Bod byl zobrazen, ale nepodařilo se ho uložit do XML.'));
                const popup = () => '<strong>' + (chosen || label) + '</strong><br><small>Typ: ' + label + '<br>X/Z: ' + Math.round(Number(modal.dataset.lng) / 3000 * 15360) + ' / ' + Math.round((1 - Number(modal.dataset.lat) / 2900) * 15360) + '</small><br><button class="dz-map-edit" type="button">Upravit bod</button> <button class="dz-map-delete" type="button">Smazat</button>';
                marker.bindPopup(popup()).openPopup();
                marker.on('popupopen', (event) => { const root = event.popup.getElement(); root.querySelector('.dz-map-delete')?.addEventListener('click', () => { map.removeLayer(marker); }); root.querySelector('.dz-map-edit')?.addEventListener('click', () => { const name = window.prompt('Název bodu / vozidla', chosen || label); if (name) marker.setPopupContent('<strong>' + name + '</strong><br><small>Typ: ' + label + '</small><br><button class="dz-map-edit" type="button">Upravit bod</button> <button class="dz-map-delete" type="button">Smazat</button>'); }); });
                pendingButton = null;
                modal.hidden = true;
            };
            modal.querySelectorAll('[data-point]').forEach((button) => button.onclick = () => placePoint(button));
            modal.querySelector('.dz-point-confirm').onclick = () => { if (pendingButton) placePoint(pendingButton); };
            const markers = @js($markers);
            markers.forEach((marker) => {
                const px = (marker.worldX ?? (marker.x * 15360 / 100)) / 15360 * 3000;
                const py = 2900 - ((marker.worldZ ?? ((100 - marker.y) * 15360 / 100)) / 15360 * 2900);
                L.circleMarker([py, px], { radius: 6, color: '#b8ed55', fillColor: '#b8ed55', fillOpacity: .9 }).addTo(map).bindTooltip(marker.label);
            });
        });
    </script>
</x-filament-panels::page>
