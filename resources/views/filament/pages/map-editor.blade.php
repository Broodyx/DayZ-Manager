<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <div class="dz-map-page">
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
                <div id="dz-event-catalog" class="dz-event-catalog" hidden>
                    <label>Možnosti pro vybraný typ</label>
                    <select></select>
                    <input class="dz-point-name" placeholder="Vlastní název (volitelné)">
                    <label class="dz-territory-zone-wrap" hidden>Druh zóny
                        <select class="dz-territory-zone">
                            <option value="HuntingGround">HuntingGround · hlavní oblast výskytu a lovu</option>
                            <option value="Rest">Rest · klidová oblast</option>
                            <option value="Graze">Graze · oblast pastvy</option>
                            <option value="Water">Water · oblast u vody</option>
                        </select>
                    </label>
                    <label class="dz-point-radius-wrap" hidden>Poloměr zóny (m)<input class="dz-point-radius" type="number" min="1" max="5000" value="150" placeholder="Např. 150"></label>
                    <small class="dz-point-help"></small>
                    <div class="dz-point-target-status"></div>
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
                <div class="dz-edit-coordinate-grid">
                    <label>Souřadnice X<input class="dz-edit-x" type="number" min="0" max="15360" step="0.001"></label>
                    <label>Souřadnice Z<input class="dz-edit-z" type="number" min="0" max="15360" step="0.001"></label>
                </div>
                <p class="dz-muted">Povolený rozsah Chernarus: 0–15 360. Uložení vždy vytvoří novou revizi původního souboru.</p>
                <p class="dz-map-feedback" hidden></p>
                <div class="dz-edit-actions">
                    <button type="button" class="dz-danger dz-edit-delete">Smazat bod</button>
                    <button type="button" class="dz-point-confirm dz-edit-save">Uložit jako novou revizi</button>
                </div>
            </div>
        </div>
        <div class="dz-map-toolbar">
            <div>
                <p class="dz-eyebrow">MAPOVÝ WORKSPACE · {{ strtoupper($map) }}</p>
                <h2>Vrstvy a body serveru</h2>
                <p class="dz-muted">Porovnejte světové souřadnice X/Z z posledních revizí a upravujte body v jejich zdrojových souborech.</p>
            </div>
            <label class="dz-map-server-picker"><span>1. Vyberte server</span><select class="dz-map-select" aria-label="Server" onchange="window.location.href='{{ url('/admin/map-editor') }}?project='+this.value">
                @foreach ($projects as $id => $name)
                    <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
                @endforeach
            </select></label>
            <div class="dz-map-upload">
                <a class="dz-map-upload-button" href="{{ url('/admin/configuration-import?area=map&project='.$projectId) }}">Přidat mapový soubor</a>
                <small>2. Nahrajte jeden soubor nebo celý ZIP balík. Každý soubor dostane vlastní revizi.</small>
            </div>
        </div>
        <div class="dz-map-info">
            <strong>Jak mapu číst:</strong>
            kruhy z <code>cfgplayerspawnpoints.xml</code> jsou oblasti, ve kterých server teprve hledá vhodný povrch.
            Nejde o přesné místo spawnu. Prototypové soubory se na mapu nekreslí, protože jejich souřadnice nejsou světové X/Z.
        </div>
        <div class="dz-map-layout">
            <section wire:ignore class="dz-map-canvas" aria-label="Mapa serveru">
                <div id="dayz-leaflet-map"></div>
            </section>
            <aside class="dz-map-legend">
                <h3>Vrstvy mapy</h3>
                <div class="dz-map-layers">
                    @foreach ($mapSources as $source)
                        @if ($source['uploaded'] && $source['plottable'] && $source['marker_count'] > 0 && $source['loaded'])
                            <label><input class="map-layer-toggle" type="checkbox" @checked($source['marker_count'] <= 3000) data-layer="{{ $source['filename'] }}"><i class="dz-layer-dot" style="background:{{ $source['color'] }}"></i><span>{{ $source['filename'] }}<small>{{ $source['marker_count'] }} bodů/oblastí{{ $source['marker_count'] > 3000 ? ' · vrstva je kvůli výkonu vypnutá' : '' }}</small></span></label>
                        @elseif ($source['uploaded'] && $source['plottable'] && $source['marker_count'] > 0)
                            <a class="dz-load-dense" href="{{ url('/admin/map-editor?project='.$projectId.'&dense=1') }}"><i class="dz-layer-dot" style="background:{{ $source['color'] }}"></i><span>Načíst {{ $source['filename'] }}<small>{{ number_format($source['marker_count'], 0, ',', ' ') }} hustých bodů</small></span></a>
                        @endif
                    @endforeach
                </div>
                <hr>
                <div class="dz-map-source-list">
                    <strong>Mapové konfigurační soubory</strong>
                    @foreach ($mapSources as $source)
                        @if ($source['uploaded'])
                            <a class="dz-map-source uploaded" href="{{ url('/admin/projects/'.$projectId.'/configuration?revision='.$source['revision_id']) }}"><span><code>{{ $source['filename'] }}</code><small>{{ $source['description'] }}</small></span><b>Upravit<br>revizi #{{ $source['revision_number'] }}</b></a>
                        @else
                            <a class="dz-map-source missing" href="{{ url('/admin/configuration-import?area=map&project='.$projectId.'&expected='.urlencode($source['filename'])) }}"><span><code>{{ $source['filename'] }}</code><small>{{ $source['description'] }}</small></span><b>Nahrát soubor</b></a>
                        @endif
                    @endforeach
                    <small>Zobrazují se pouze poslední revize. Každá změna vytvoří novou revizi a původní soubor zůstane zachovaný.</small>
                </div>
            </aside>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('dayz-leaflet-map');
            if (!el || el.dataset.ready) return;
            el.dataset.ready = '1';
            const worldSize = 15360;
            const map = L.map(el, { crs: L.CRS.Simple, minZoom: -5, maxZoom: 1, zoomSnap: 0.25, maxBoundsViscosity: .8, preferCanvas:true });
            const bounds = [[0, 0], [worldSize, worldSize]];
            L.imageOverlay('/maps/chernarus_big_hq.jpg', bounds).addTo(map);
            L.rectangle(bounds, { color: '#b8ed55', weight: 1, fill: false, opacity: .35 }).addTo(map);
            map.setMaxBounds([[-800, -800], [worldSize + 800, worldSize + 800]]);
            map.fitBounds(bounds);
            L.control.scale({ imperial: false }).addTo(map);
            const coordinateControl = L.control({ position: 'bottomleft' });
            coordinateControl.onAdd = () => { const div = L.DomUtil.create('div', 'dz-coordinate-control'); div.textContent = 'X: — · Z: —'; return div; };
            coordinateControl.addTo(map);
            const grid = L.layerGroup().addTo(map);
            for (let coordinate = 0; coordinate <= 15000; coordinate += 1000) {
                L.polyline([[0, coordinate], [worldSize, coordinate]], { color:'#d8f57b', weight:1, opacity:.2, interactive:false }).addTo(grid);
                L.polyline([[coordinate, 0], [coordinate, worldSize]], { color:'#d8f57b', weight:1, opacity:.2, interactive:false }).addTo(grid);
                if (coordinate > 0) {
                    L.marker([160, coordinate], { interactive:false, icon:L.divIcon({ className:'dz-grid-label', html:'X '+coordinate, iconSize:[62,16] }) }).addTo(grid);
                    L.marker([coordinate, 120], { interactive:false, icon:L.divIcon({ className:'dz-grid-label', html:'Z '+coordinate, iconSize:[62,16] }) }).addTo(grid);
                }
            }
            map.on('mousemove', (event) => {
                const x = Math.max(0, Math.min(worldSize, Math.round(event.latlng.lng)));
                const z = Math.max(0, Math.min(worldSize, Math.round(event.latlng.lat)));
                document.querySelector('.dz-coordinate-control').textContent = 'DayZ X: ' + x.toLocaleString() + ' · Z: ' + z.toLocaleString();
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
            let activeMarker = null;
            let pendingButton = null;
            const showFeedback = (root, message, isError = true) => {
                const feedback = root.querySelector('.dz-map-feedback');
                if (!feedback) return;
                feedback.textContent = message;
                feedback.classList.toggle('error', isError);
                feedback.hidden = false;
            };
            const pointTypeCatalog = @js($pointTypeCatalog);
            let selectedCatalogOption = null;
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
                    status.innerHTML = '<strong>Zapíše se do: <code>' + target + '</code></strong><span>Uložení vytvoří novou revizi tohoto souboru; původní revize zůstane zachována.</span>';
                } else {
                    const names = missing.length ? missing.join(', ') : (definition.target_label || 'požadovaná konfigurace');
                    status.className = 'dz-point-target-status missing';
                    status.innerHTML = '<strong>Chybí aktuální soubor: ' + names + '</strong><span>Bez něj bod nelze bezpečně uložit do DayZ konfigurace.</span>'
                        + (uploadUrl ? '<a href="' + uploadUrl + '">Nahrát aktuální konfiguraci →</a>' : '');
                }
                const confirm = catalogRoot.querySelector('.dz-point-confirm');
                confirm.disabled = !available || (!selectedCatalogOption && (definition.options || []).length > 0);
                confirm.textContent = available ? 'Umístit bod a vytvořit revizi' : 'Nejprve nahrajte požadovaný soubor';
                return { target, available };
            };
            const placePoint = (button) => {
                const label = button.dataset.label || button.textContent.trim();
                if (pendingButton !== button) {
                    pendingButton = button;
                    modal.querySelectorAll('[data-point]').forEach((item) => item.classList.toggle('selected', item === button));
                    const catalog = document.getElementById('dz-event-catalog'); const select = catalog.querySelector('select'); const nameInput = catalog.querySelector('.dz-point-name');
                    const definition = pointTypeCatalog[button.dataset.point] || { options: [], missing: ['podporovaný konfigurační soubor'], available: false };
                    const entries = definition.options || [];
                    select.replaceChildren(new Option('Vyberte existující možnost...', ''));
                    entries.forEach((item) => select.add(new Option(item.label, item.value)));
                    catalog.hidden = false;
                    nameInput.value = '';
                    select.selectedIndex = entries.length === 1 ? 1 : 0;
                    selectedCatalogOption = entries.length === 1 ? entries[0] : null;
                    select.onchange = syncPointTarget;
                    const radiusTypes = ['contaminated', 'infected', 'territory', 'custom', 'animal'];
                    catalog.querySelector('.dz-point-radius-wrap').hidden = !radiusTypes.includes(button.dataset.point);
                    catalog.querySelector('.dz-territory-zone-wrap').hidden = !['animal', 'territory'].includes(button.dataset.point);
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
                const radius = Number(document.querySelector('.dz-point-radius')?.value || 50);
                const zoneType = document.querySelector('.dz-territory-zone')?.value || null;
                const marker = L.marker([Number(modal.dataset.lat), Number(modal.dataset.lng)]).addTo(map);
                const newX = Math.round(Number(modal.dataset.lng));
                const newZ = Math.round(Number(modal.dataset.lat));
                fetch('{{ route('map-editor.points.store') }}', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({project_id: @js($projectId), type: button.dataset.point, label: chosen || label, target_filename: targetState.target, zone_type: zoneType, x: newX, z: newZ, radius}) }).then(async (response) => { if (!response.ok) throw new Error((await response.json().catch(()=>({}))).message || 'Uložení bodu selhalo'); window.location.reload(); }).catch((error) => { map.removeLayer(marker); modal.hidden = false; showFeedback(modal, error.message); });
                const popup = () => '<strong>' + (chosen || label) + '</strong><br><small>Typ: ' + label + '<br>DayZ X/Z: ' + newX + ' / ' + newZ + '</small>';
                marker.bindPopup(popup()).openPopup();
                pendingButton = null;
                modal.hidden = true;
            };
            modal.querySelectorAll('[data-point]').forEach((button) => button.onclick = () => placePoint(button));
            modal.querySelector('.dz-point-confirm').onclick = () => { if (pendingButton) placePoint(pendingButton); };
            const markers = @js($markers);
            const layerGroups = {};
            markers.forEach((marker) => {
                const color = marker.color || '#b8ed55';
                const layers = layerGroups[marker.filename] ||= [];
                if (marker.radius) {
                    const area = L.circle([marker.worldZ, marker.worldX], { radius:Number(marker.radius), color, weight:1.5, fillColor:color, fillOpacity:.1 }).addTo(map);
                    layers.push(area);
                }
                const imported = L.circleMarker([marker.worldZ, marker.worldX], { radius: 6, color, fillColor: color, fillOpacity: .9 }).addTo(map);
                layers.push(imported);
                const markerActions = marker.editable
                    ? '<br><button class="dz-map-edit" type="button">Upravit souřadnice</button> <button class="dz-map-delete" type="button">Smazat bod a vytvořit revizi</button>'
                    : '<br><a href="{{ url('/admin/projects/'.$projectId.'/configuration') }}?revision=' + marker.revision_id + '">Otevřít příslušný editor</a>';
                imported.bindPopup('<strong>' + marker.label + '</strong><br><small>' + marker.help + '<br>DayZ X/Z: ' + Math.round(marker.worldX) + ' / ' + Math.round(marker.worldZ) + '</small>' + markerActions);
                imported.on('popupopen', (event) => {
                    const root = event.popup.getElement();
                    root.querySelector('.dz-map-edit')?.addEventListener('click', () => {
                        activeMarker = marker;
                        editModal.querySelector('.dz-edit-point-context').textContent = marker.label + ' · ' + marker.filename;
                        editModal.querySelector('.dz-edit-x').value = marker.worldX;
                        editModal.querySelector('.dz-edit-z').value = marker.worldZ;
                        editModal.hidden = false;
                    });
                    root.querySelector('.dz-map-delete')?.addEventListener('click', () => {
                        activeMarker = marker;
                        editModal.querySelector('.dz-edit-point-context').textContent = marker.label + ' · ' + marker.filename;
                        editModal.querySelector('.dz-edit-x').value = marker.worldX;
                        editModal.querySelector('.dz-edit-z').value = marker.worldZ;
                        editModal.hidden = false;
                    });
                });
            });
            editModal.querySelector('.dz-edit-save').onclick = () => {
                if (!activeMarker) return;
                const newX = Number(editModal.querySelector('.dz-edit-x').value);
                const newZ = Number(editModal.querySelector('.dz-edit-z').value);
                if (!Number.isFinite(newX) || !Number.isFinite(newZ) || newX < 0 || newX > worldSize || newZ < 0 || newZ > worldSize) {
                    showFeedback(editModal, 'Souřadnice musí být v rozsahu 0–15 360.');
                    return;
                }
                fetch('{{ route('map-editor.points.update') }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({project_id:@js($projectId),revision_id:activeMarker.revision_id,filename:activeMarker.filename,path:activeMarker.path,x:activeMarker.worldX,z:activeMarker.worldZ,new_x:newX,new_z:newZ})}).then(async (r) => { if (!r.ok) throw new Error((await r.json().catch(()=>({}))).message || 'Bod se nepodařilo upravit.'); window.location.reload(); }).catch((error) => showFeedback(editModal, error.message));
            };
            editModal.querySelector('.dz-edit-delete').onclick = () => {
                if (!activeMarker) return;
                fetch('{{ route('map-editor.points.delete') }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({project_id:@js($projectId),revision_id:activeMarker.revision_id,filename:activeMarker.filename,path:activeMarker.path,x:activeMarker.worldX,z:activeMarker.worldZ})}).then(async (r) => { if (!r.ok) throw new Error((await r.json().catch(()=>({}))).message || 'Bod se nepodařilo odstranit.'); window.location.reload(); }).catch((error) => showFeedback(editModal, error.message));
            };
            document.querySelectorAll('.map-layer-toggle').forEach((toggle) => {
                const syncLayer = () => (layerGroups[toggle.dataset.layer] || []).forEach((layer) => toggle.checked ? layer.addTo(map) : map.removeLayer(layer));
                toggle.addEventListener('change', syncLayer);
                syncLayer();
            });
        });
    </script>
        @endif
</x-filament-panels::page>
