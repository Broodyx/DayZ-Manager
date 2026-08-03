<x-filament-panels::page>
    <style>
        .fi-main { max-width:100rem !important }
        .dz-heading-badge { display:inline-flex; margin-left:.55rem; padding:.25rem .6rem; border-radius:999px; vertical-align:.2em; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em }
        .dz-heading-badge.safe { color:#c9f67a; background:rgba(145,197,43,.14); border:1px solid rgba(182,233,79,.25) }
        .dz-heading-badge.pc { color:#ffc08f; background:rgba(217,119,56,.14); border:1px solid rgba(217,119,56,.3) }
        .dz-editor-grid { display:grid; grid-template-columns:minmax(360px, 460px) minmax(620px, 1fr); gap:1rem }
        .dz-panel { border:1px solid rgba(182,233,79,.16); border-radius:.4rem; background:#111813; overflow:hidden }
        .dz-panel-head { padding:1rem; border-bottom:1px solid rgba(182,233,79,.13); background:rgba(182,233,79,.035) }
        .dz-muted { color:#aab6a4 }
        .dz-tabs { display:flex; gap:.5rem; flex-wrap:wrap }
        .dz-tab { padding:.65rem 1rem; border:1px solid rgba(190,209,175,.18); border-radius:.3rem; color:#cbd5c0; background:#151e17 }
        .dz-tab.active { color:#17210d; border-color:#b6e94f; background:#b6e94f; font-weight:800 }
        .dz-search { width:100%; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-list { max-height:720px; overflow:auto; padding:.5rem }
        .dz-group { margin-bottom:.5rem; border:1px solid rgba(190,209,175,.1); border-radius:.3rem; overflow:hidden }
        .dz-group summary { display:flex; align-items:center; justify-content:space-between; padding:.75rem; color:#edf2e9; background:#151e17; cursor:pointer; font-weight:700; text-transform:uppercase; letter-spacing:.04em }
        .dz-group summary::marker { color:#b6e94f }
        .dz-group-body { padding:.35rem }
        .dz-item { display:flex; justify-content:space-between; width:100%; padding:.7rem .75rem; border-radius:.25rem; color:#cbd5c0; text-align:left }
        .dz-item:hover,.dz-item.active { color:#b6e94f; background:rgba(145,197,43,.12) }
        .dz-item-meta { display:flex; align-items:center; gap:.45rem }
        .dz-badge { display:inline-flex; padding:.16rem .45rem; border-radius:999px; font-size:.68rem; font-weight:800; text-transform:uppercase }
        .dz-badge.safe { color:#c9f67a; background:rgba(145,197,43,.14); border:1px solid rgba(182,233,79,.25) }
        .dz-badge.pc { color:#ffc08f; background:rgba(217,119,56,.14); border:1px solid rgba(217,119,56,.3) }
        .dz-file-option-undeployed { margin-left:.4rem; color:#f2a49c; background:rgba(224,85,74,.16); border:1px solid rgba(224,85,74,.35) }
        .dz-server-settings { border-color:rgba(56,189,248,.35); background:linear-gradient(135deg,rgba(14,116,144,.18),rgba(17,24,19,.95)) }
        .dz-server-badge { color:#8bdcff; background:rgba(14,165,233,.14); border:1px solid rgba(56,189,248,.35) }
        .dz-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; padding:1rem }
        .dz-field { min-width:0; padding:1rem; border:1px solid rgba(190,209,175,.12); border-radius:.35rem; background:#0d130f }
        .dz-field-top { display:grid; grid-template-columns:minmax(0,1fr) 7rem; align-items:start; gap:1rem; margin-bottom:.75rem }
        .dz-field-top > span:first-child { min-width:0; overflow-wrap:break-word; word-break:normal; white-space:normal }
        .dz-field-top strong { display:block; overflow-wrap:break-word; word-break:normal; white-space:normal }
        .dz-field input[type=number] { width:7rem; border:1px solid rgba(190,209,175,.2); border-radius:.25rem; padding:.45rem .55rem; color:#edf2e9; background:#090d0a }
        .dz-field-top input[type=text],.dz-field-top input[type=number] { min-width:0; width:100%; box-sizing:border-box }
        .dz-switch-control { display:flex; align-items:center; justify-content:flex-end; min-width:7rem; cursor:pointer }
        .dz-switch-control input { position:absolute; opacity:0; pointer-events:none }
        .dz-toggle-button { min-width:6.8rem; padding:.55rem .75rem; border-radius:.35rem; text-align:center; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; transition:.15s }
        .dz-toggle-button.on { color:#17210d; background:#b6e94f; border:1px solid #d4fa78 }
        .dz-toggle-button.off { color:#cbd5c0; background:#263229; border:1px solid rgba(190,209,175,.35) }
        .dz-toggle-button:hover { filter:brightness(1.12) }
        .dz-field input[type=range] { width:100%; accent-color:#a3e635 }
        .dz-field textarea { width:100%; min-height:5rem; box-sizing:border-box; resize:vertical; border:1px solid rgba(190,209,175,.2); border-radius:.25rem; padding:.55rem .65rem; color:#edf2e9; background:#090d0a; font:inherit }
        .dz-message-type { display:grid; gap:.4rem }
        .dz-message-type-select { width:100%; min-height:2.7rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.5rem .6rem; color:#edf2e9; background:#090d0a; font:inherit; cursor:pointer }
        .dz-field-wide { grid-column:1 / -1 }
        .dz-empty-state { margin:1rem; padding:1rem; border:1px dashed rgba(182,233,79,.35); border-radius:.4rem; background:rgba(182,233,79,.04) }
        .dz-field[data-tooltip] { position:relative }
        .dz-field[data-tooltip]::after { content:attr(data-tooltip); position:absolute; z-index:20; left:.75rem; bottom:calc(100% + .35rem); max-width:28rem; padding:.45rem .6rem; border:1px solid rgba(182,233,79,.3); border-radius:.25rem; color:#e7f4dc; background:#0a100c; box-shadow:0 8px 24px rgba(0,0,0,.45); font:12px/1.35 ui-monospace,SFMono-Regular,Consolas,monospace; white-space:normal; opacity:0; pointer-events:none; transform:translateY(.25rem); transition:opacity .15s,transform .15s }
        .dz-field[data-tooltip]:hover::after,.dz-field[data-tooltip]:focus-within::after { opacity:1; transform:translateY(0) }
        .dz-raw { width:100%; min-height:65vh; resize:vertical; border:1px solid rgba(190,209,175,.2); border-radius:.35rem; padding:1rem; color:#dce8d5; background:#070b08; font:13px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace; tab-size:4 }
        .dz-action { display:inline-flex; align-items:center; justify-content:center; padding:.7rem 1.15rem; border-radius:.25rem; color:#17210d; background:#b6e94f; font-weight:800; text-transform:uppercase; letter-spacing:.03em }
        .dz-summary { flex:1; min-width:240px; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.7rem .8rem; color:#edf2e9; background:#090d0a }
        .dz-file-select { min-width:280px; appearance:none; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.65rem 2.25rem .65rem .8rem; color:#edf2e9; background-color:#090d0a; background-image:linear-gradient(45deg,transparent 50%,#b6e94f 50%),linear-gradient(135deg,#b6e94f 50%,transparent 50%); background-position:calc(100% - 16px) 50%,calc(100% - 11px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat }
        .dz-file-buttons { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end }
        .dz-file-button { display:inline-flex; align-items:center; gap:.35rem; padding:.58rem .75rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; color:#cbd5c0; background:#090d0a; font-size:.78rem; font-weight:700; text-align:left }
        .dz-file-button:hover { border-color:#91c52b; color:#edf2e9 }
        .dz-file-button.active { border-color:#b6e94f; color:#17210d; background:#b6e94f }
        .dz-file-menu { position:relative; width:min(28rem,100%); z-index:30 }
        .dz-file-menu summary { display:flex; align-items:center; justify-content:space-between; gap:.75rem; cursor:pointer; list-style:none; padding:.65rem .8rem; border:1px solid rgba(182,233,79,.35); border-radius:.3rem; color:#edf2e9; background:#111813; font-weight:800 }
        .dz-file-menu summary::-webkit-details-marker { display:none }
        .dz-file-menu summary::after { content:'▾'; color:#b6e94f }
        .dz-file-menu[open] summary::after { content:'▴' }
        .dz-file-menu-list { position:absolute; right:0; top:calc(100% + .4rem); width:min(32rem,calc(100vw - 2rem)); max-height:24rem; overflow:auto; padding:.3rem; border:1px solid rgba(182,233,79,.3); border-radius:.5rem; background:#0d140f; box-shadow:0 16px 40px rgba(0,0,0,.55) }
        .dz-file-option { display:block; width:100%; padding:.55rem .65rem; border:0; border-bottom:1px solid rgba(190,209,175,.1); color:#dce8d5; background:transparent; text-align:left; cursor:pointer }
        .dz-file-option:last-child { border-bottom:0 }
        .dz-file-option:hover { color:#17210d; background:#b6e94f }
        .dz-file-option.active { color:#e7f7d2; background:linear-gradient(90deg,rgba(182,233,79,.24),rgba(182,233,79,.1)); box-shadow:inset 3px 0 #b6e94f }
        .dz-file-option-title { display:block; font-size:.95rem; font-weight:800; line-height:1.25; color:#e7f7d2 }
        .dz-file-option-revision { display:block; margin-top:.12rem; color:#b8ed55; font-size:.72rem; font-weight:800; letter-spacing:.03em }
        .dz-file-option-description { display:block; margin-top:.15rem; color:#aab6a4; font-size:.7rem; line-height:1.25; font-weight:400 }
        .dz-file-option:hover .dz-file-option-description { color:#31451f }
        .dz-file-option.active .dz-file-option-title { color:#f3ffd9 }
        .dz-file-option.active .dz-file-option-revision { color:#b6e94f }
        .dz-file-option.active .dz-file-option-description { color:#c4d6b8 }
        .dz-modal-backdrop { position:fixed; inset:0; z-index:60; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(0,0,0,.72) }
        .dz-modal { width:min(48rem,100%); max-height:85vh; overflow:hidden; border:1px solid rgba(182,233,79,.35); border-radius:.45rem; background:#111813; box-shadow:0 24px 80px rgba(0,0,0,.65) }
        .dz-modal-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem; border-bottom:1px solid rgba(182,233,79,.15) }
        .dz-modal-body { max-height:65vh; overflow:auto; padding:1rem }
        .dz-picker-categories { display:flex; gap:.45rem; flex-wrap:wrap; margin-bottom:1rem }
        .dz-picker-category { padding:.5rem .7rem; border:1px solid rgba(190,209,175,.18); border-radius:.25rem; color:#cbd5c0; background:#0d130f; cursor:pointer }
        .dz-picker-category.active { color:#17210d; background:#b6e94f; border-color:#b6e94f }
        .dz-picker-items { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.35rem }
        .dz-picker-item { display:flex; align-items:center; justify-content:space-between; gap:.5rem; min-height:2.65rem; padding:.45rem .6rem; border:1px solid rgba(190,209,175,.14); border-radius:.25rem; color:#dce8d5; background:#0d130f; text-align:left; cursor:pointer }
        .dz-picker-item small { color:#aab6a4; white-space:nowrap }
        .dz-picker-item:hover { border-color:#b6e94f; color:#17210d; background:#b6e94f }
        .dz-picker-loading { padding:.75rem; color:#b6e94f; text-align:center }
        .dz-picker-search { width:100%; margin-bottom:1rem; padding:.7rem .8rem; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; color:#edf2e9; background:#090d0a }
        .dz-picker-more { display:block; width:100%; margin-top:1rem; padding:.7rem; border:1px solid rgba(182,233,79,.25); border-radius:.25rem; color:#b6e94f; background:#151e17; font-weight:700 }
        @media(max-width:640px){.dz-picker-items{grid-template-columns:1fr}.dz-picker-item{min-height:2.4rem}}
        .dz-savebar { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; padding:1rem; border-top:1px solid rgba(182,233,79,.13); background:#111813 }
        .dz-warning { padding:.85rem 1rem; border-left:3px solid #d97738; color:#e8c8b3; background:rgba(217,119,56,.08) }
        .dz-error { margin-top:.4rem; color:#fb8b8b; font-size:.85rem }
        .dz-info { padding:.85rem 1rem; border-left:3px solid #91c52b; color:#dbeacb; background:rgba(145,197,43,.07) }
        .dz-status-panel { border:1px solid rgba(190,209,175,.15); border-radius:.4rem; background:#111813; margin-bottom:.85rem }
        .dz-status-panel summary { cursor:pointer; padding:.6rem .9rem; color:#cbd5c0; font-weight:700; font-size:.82rem; display:flex; align-items:center; gap:.5rem; list-style:none }
        .dz-status-panel summary::-webkit-details-marker { display:none }
        .dz-status-panel summary small { padding:.1rem .5rem; border-radius:999px; background:#1a241a; color:#aab6a4; font-size:.68rem; font-weight:800 }
        .dz-status-list { display:grid; gap:.4rem; padding:0 .9rem .75rem }
        .dz-status-row { display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; padding:.5rem .7rem; border-radius:.3rem; font-size:.78rem; line-height:1.45 }
        .dz-status-ok { border-left:3px solid #91c52b; background:rgba(145,197,43,.06); color:#c9d6bd }
        .dz-status-warning { border-left:3px solid #d97738; background:rgba(217,119,56,.07); color:#e0cdb9 }
        .dz-status-danger { border-left:3px solid #e0554a; background:rgba(224,85,74,.07); color:#eccac6 }
        .dz-status-row .dz-danger { flex:0 0 auto; font-size:.72rem; padding:.4rem .65rem }
        .dz-secondary { display:inline-flex; align-items:center; justify-content:center; padding:.65rem 1rem; border:1px solid rgba(182,233,79,.25); border-radius:.25rem; color:#b6e94f; background:#151e17; font-weight:700 }
        .dz-add-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; padding:1rem }
        .dz-control label { display:block; margin-bottom:.35rem; color:#cbd5c0; font-size:.85rem; font-weight:700 }
        .dz-control input,.dz-control select { width:100%; border:1px solid rgba(190,209,175,.2); border-radius:.3rem; padding:.65rem .75rem; color:#edf2e9; background:#090d0a }
        .dz-checks { display:flex; gap:.5rem; flex-wrap:wrap }
        .dz-check { display:flex; align-items:center; gap:.35rem; padding:.4rem .55rem; border:1px solid rgba(190,209,175,.14); border-radius:.25rem; color:#cbd5c0; background:#0d130f }
        .dz-gear-inventory-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .dz-gear-profile { display:flex; gap:1rem; flex-wrap:wrap; padding:1rem 1.1rem; border:1px solid rgba(182,233,79,.22); border-radius:.55rem; background:#111813; }
        .dz-gear-profile > div { flex:1 1 14rem; display:grid; gap:.2rem; }
        .dz-gear-profile strong { color:#eaffd2; font-size:1.05rem; overflow-wrap:anywhere }
        .dz-gear-slot { border:1px solid rgba(182,233,79,.22); border-radius:.55rem; overflow:hidden; background:linear-gradient(145deg,#172219,#0b110d); box-shadow:0 8px 22px rgba(0,0,0,.2) }
        .dz-gear-slot summary { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; color:#eaffd2; cursor:pointer; list-style:none; font-size:1rem }
        .dz-gear-slot summary::-webkit-details-marker { display:none }
        .dz-gear-slot summary::after { content:'+'; color:#b6e94f; font-size:1.4rem; font-weight:300 }
        .dz-gear-slot[open] summary::after { content:'−' }
        .dz-gear-slot summary span { color:#b6e94f; font:700 .78rem ui-monospace,monospace; text-align:right; overflow-wrap:anywhere }
        .dz-gear-slot-body { padding:1rem 1.1rem 1.1rem; border-top:1px solid rgba(182,233,79,.14); color:#cbd5c0; background:rgba(0,0,0,.12) }
        .dz-gear-slot-body .dz-control { display:block; margin:0; }
        .dz-gear-slot-body .dz-control span { display:block; margin-bottom:.35rem; color:#b6e94f; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em }
        .dz-gear-slot-body select { width:100%; min-height:2.55rem; border:1px solid rgba(182,233,79,.28); border-radius:.3rem; padding:.5rem .65rem; color:#edf2e9; background:#090d0a; font:inherit }
        .dz-gear-child { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-top:.45rem; padding:.55rem .65rem; border:1px solid rgba(190,209,175,.13); border-radius:.3rem; color:#edf2e9; background:#0d130f; font:700 .8rem ui-monospace,monospace; overflow-wrap:anywhere }
        .dz-gear-child select { min-width:0; flex:1; border-color:rgba(190,209,175,.2); font-size:.78rem }
        .dz-gear-child button { flex:0 0 auto; border:1px solid rgba(224,85,74,.35); border-radius:.25rem; padding:.28rem .45rem; color:#f2a49c; background:rgba(224,85,74,.1); font-size:.7rem; cursor:pointer }
        .dz-gear-slot-body .dz-secondary { margin-top:.8rem; width:100%; }
        .dz-gear-mode .dz-generic-json { display:none; }
        @media(max-width:900px){.dz-gear-inventory-grid{grid-template-columns:1fr}}
        @media(max-width:900px){
            .dz-editor-grid,.dz-fields,.dz-add-grid{grid-template-columns:1fr}
            .dz-editor-grid{gap:.75rem}
        }
        @media(max-width:640px){
            .fi-main{padding-inline:.65rem !important}
            .dz-heading-badge{margin-left:.2rem;padding:.2rem .4rem;font-size:.58rem}
            .dz-tabs{width:100%;gap:.35rem}
            .dz-tab{flex:1;padding:.6rem .45rem;font-size:.78rem;text-align:center}
            .dz-file-select{width:100%;min-width:0}
            .dz-file-buttons{width:100%;justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap;padding-bottom:.2rem}
            .dz-file-button{flex:0 0 auto;white-space:nowrap}
            .dz-list{max-height:50vh}
            .dz-fields,.dz-add-grid{padding:.65rem;gap:.65rem}
            .dz-field{padding:.75rem}
            .dz-field-top{grid-template-columns:minmax(0,1fr) 5.5rem;align-items:start;gap:.5rem}
            .dz-field input[type=number]{width:5.5rem}
            .dz-savebar{padding:.75rem;align-items:stretch}
            .dz-summary,.dz-action,.dz-secondary{width:100%;min-width:0}
            .dz-action,.dz-secondary{padding:.7rem .6rem}
        }
    </style>

    <div class="space-y-4">
        <section class="dz-editor-command">
            <div>
                <p class="dz-kicker">EDITOR KONFIGURACE</p>
                <h2>{{ $currentFilename }}</h2>
                <p>{{ $this->configurationDescription() }}</p>
            </div>
            <div class="dz-editor-command-meta">
                <span><small>REVIZE</small><strong>#{{ $revisionNumber }}</strong></span>
                <span><small>FORMÁT</small><strong>{{ strtoupper(pathinfo($currentFilename, PATHINFO_EXTENSION) ?: 'TEXT') }}</strong></span>
                <span><small>REŽIM</small><strong>{{ $mode === 'visual' ? 'VIZUÁLNÍ' : 'RAW DATA' }}</strong></span>
            </div>
        </section>
        @php
            $undeployedRevisionIds = $this->undeployedRevisionIds();
            $dzStatusRows = [];
            if ($this->getRecord()->platform !== 'steam' && $detectedPlatform === 'steam') {
                $dzStatusRows[] = ['tone' => 'danger', 'label' => 'PC-only prvky', 'text' => 'Projekt je nastavený jako '.ucfirst($this->getRecord()->platform).', ale konfigurace obsahuje: '.implode(', ', $platformReasons).'. Tyto části nemusí na konzoli fungovat.'];
            } elseif ($platformWarnings !== []) {
                $dzStatusRows[] = ['tone' => 'ok', 'label' => 'Konzolově kompatibilní', 'text' => 'Nenalezeny žádné PC-only prvky. Samotné XML ale nedokáže automaticky rozlišit PlayStation od Xboxu.'];
            }
            foreach ($dependencyWarnings as $warning) {
                $dzStatusRows[] = ['tone' => 'warning', 'label' => 'Chybějící vazba konfigurace', 'text' => $warning];
            }
            if (in_array((int) $revisionId, $undeployedRevisionIds, true)) {
                $dzStatusRows[] = ['tone' => 'warning', 'label' => 'Nestaženo na live server', 'text' => 'Tahle revize souboru '.$currentFilename.' ještě nebyla stažena tlačítkem "Stáhnout do počítače" — na herním serveru tedy pořád běží starší verze.'];
            }
            foreach ($this->relatedLogFindings() as $finding) {
                $dzStatusRows[] = [
                    'tone' => 'danger',
                    'label' => 'Z log analyzátoru',
                    'text' => $finding['title'],
                    'fix' => in_array($finding['kind'], ['type-does-not-exist', 'type-not-spawnable'], true) ? $finding['target'] : null,
                    'link' => $finding['kind'] === 'missing-event-definition' ? url('/admin/map-editor').'?'.http_build_query(['project' => $this->getRecord()->id, 'open_event' => $finding['target']]) : null,
                ];
            }
        @endphp
        @if (count($dzStatusRows))
            <details class="dz-status-panel" open>
                <summary>Stav souboru <small>{{ count($dzStatusRows) }}</small></summary>
                <div class="dz-status-list">
                    @foreach ($dzStatusRows as $row)
                        <div class="dz-status-row dz-status-{{ $row['tone'] }}">
                            <span><strong>{{ $row['label'] }}.</strong> {{ $row['text'] }}</span>
                            @if (!empty($row['fix']))
                                @php $dzStatusFixConfirm = "Bezpečná oprava: odebrat položku '{$row['fix']}' z types.xml. Hra ji stejně už teď nespawnuje, takže se chování serveru nezmění — jen zmizí tahle chyba z logu. Vytvoří se nová revize. Opravdu pokračovat?"; @endphp
                                <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($dzStatusFixConfirm)).then((ok) => { if (ok) $wire.removeTypeByName(@js($row['fix'])); })">Bezpečně odebrat</button>
                            @elseif (!empty($row['link']))
                                <a class="dz-secondary" href="{{ $row['link'] }}">Otevřít Mapový editor →</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        <div class="dz-editor-toolbar-new">
            <div class="dz-editor-file-choice">
                <span>Konfigurační soubor</span>
                <details class="dz-file-menu">
                    <summary>{{ $currentFilename ?: 'Vybrat konfiguraci' }} <small>revize #{{ $revisionNumber }}</small></summary>
                    <div class="dz-file-menu-list" aria-label="Výběr konfigurace">
                    @foreach ($this->editableFiles() as $id => $label)
                        @php
                            [$fileLabel, $revisionLabel] = array_pad(explode(' · ', $label, 2), 2, '');
                        @endphp
                        <button type="button" wire:click="selectRevision({{ $id }})" class="dz-file-option {{ (int) $revisionId === (int) $id ? 'active' : '' }}">
                            <span class="dz-file-option-title">
                                {{ $fileLabel }}
                                @if (in_array($id, $undeployedRevisionIds, true))
                                    <span class="dz-badge dz-file-option-undeployed" title="Ještě nestaženo na live server">nestaženo</span>
                                @endif
                            </span>
                            <span class="dz-file-option-revision">{{ $revisionLabel }}</span>
                            <span class="dz-file-option-description">{{ $this->descriptionForFilename($fileLabel) }}</span>
                        </button>
                    @endforeach
                    </div>
                </details>
            </div>
            <div class="dz-tabs" aria-label="Režim editoru">
                @if ($visualSupported)
                    <button type="button" wire:click="$set('mode', 'visual')" class="dz-tab {{ $mode === 'visual' ? 'active' : '' }}">
                        Vizuální editor
                    </button>
                @endif
                <button type="button" wire:click="$set('mode', 'raw')" class="dz-tab {{ $mode === 'raw' ? 'active' : '' }}">
                    Raw data
                </button>
            </div>
        </div>

        @if ($mode === 'visual' && $visualSupported)
            @if ($visualKind === 'types')
            <div class="dz-editor-grid">
                <section class="dz-panel">
                    <div class="dz-panel-head">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <strong>Položky types.xml</strong>
                                <p class="dz-muted text-sm mt-1">Rozbal sekci nebo použij hledání. Nejvýše 200 výsledků.</p>
                            </div>
                            <button type="button" wire:click="openAddForm" class="dz-secondary">+ Nová položka</button>
                        </div>
                        <input wire:model.live.debounce.250ms="search" class="dz-search mt-3" placeholder="Hledat například AKM…">
                    </div>
                    <div class="dz-list">
                        @foreach ($this->groupedTypes() as $category => $entries)
                            <details wire:key="types-category-{{ $category }}" wire:ignore.self class="dz-group" {{ $search !== '' ? 'open' : '' }}>
                                <summary>
                                    <span>{{ $category }}</span>
                                    <small>{{ count($entries) }} položek</small>
                                </summary>
                                <div class="dz-group-body">
                                    @foreach ($entries as $entry)
                                        <button type="button" wire:click="selectType(@js($entry['name']))" class="dz-item {{ $selectedType === $entry['name'] ? 'active' : '' }}">
                                            <span>{{ $entry['name'] }}</span>
                                            <span class="dz-item-meta">
                                                <span class="dz-badge {{ $entry['pc_only'] ? 'pc' : 'safe' }}">
                                                    {{ $entry['pc_only'] ? 'PC only' : 'PS · Xbox · PC' }}
                                                </span>
                                                <small>{{ $entry['nominal'] }} ks</small>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>

                <section class="dz-panel">
                    @if ($showAddForm)
                        <div class="dz-panel-head">
                            <strong>Nová položka types.xml</strong>
                            <p class="dz-muted text-sm mt-1">Název se kontroluje bez ohledu na velikost písmen. Duplicitní položku nelze uložit.</p>
                        </div>
                        <div class="dz-add-grid">
                            <div class="dz-control">
                                <label>Název třídy</label>
                                <div class="flex gap-2">
                                    <input wire:model="newTypeForm.name" placeholder="Například AKM">
                                    <button type="button" wire:click="openClassPicker" class="dz-secondary">Vybrat z katalogu</button>
                                </div>
                                <small class="dz-muted">Vyber existující třídu z katalogu, nebo zadej vlastní název z konfigurace.</small>
                                @error('newTypeForm.name') <div class="dz-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="dz-control">
                                <label>Kategorie</label>
                                <select wire:model="newTypeForm.category">
                                    @foreach (['weapons' => 'Zbraně', 'food' => 'Jídlo', 'medical' => 'Zdravotnictví', 'tools' => 'Nástroje', 'clothes' => 'Oblečení', 'containers' => 'Kontejnery', 'vehicles' => 'Vozidla', 'other' => 'Ostatní'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @foreach ([
                                'nominal' => 'Cílové množství',
                                'min' => 'Minimum',
                                'lifetime' => 'Životnost',
                                'restock' => 'Doplnění',
                                'quantmin' => 'Min. naplnění',
                                'quantmax' => 'Max. naplnění',
                                'cost' => 'Priorita',
                            ] as $field => $label)
                                <div class="dz-control">
                                    <label>{{ $label }}</label>
                                    <input type="number" wire:model="newTypeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}">
                                    @error("newTypeForm.$field") <div class="dz-error">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="px-4 pb-4">
                            <strong>Oblasti výskytu</strong>
                            <div class="dz-checks mt-2">
                                @foreach (['Military', 'Hunting', 'Police', 'Medic', 'Town', 'Village', 'Farm', 'Industrial', 'Coast'] as $usage)
                                    <label class="dz-check">
                                        <input type="checkbox" wire:model="newTypeForm.usages" value="{{ $usage }}">
                                        {{ $usage }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="dz-savebar">
                            <x-dz-confirm-button call="addType()" label="Zkontrolovat a přidat" saved-label="Přidáno" class="dz-action" />
                        </div>
                    @elseif ($selectedType)
                        <div class="dz-panel-head">
                            @php
                                $selectedEntry = collect($typeEntries)->firstWhere('name', $selectedType);
                            @endphp
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $selectedType }}</strong>
                                    <p class="dz-muted text-sm mt-1">
                                        Kategorie: {{ $selectedEntry['category'] ?? 'other' }}
                                        @if (!empty($selectedEntry['usages'])) · Výskyt: {{ implode(', ', $selectedEntry['usages']) }} @endif
                                    </p>
                                </div>
                                <span class="dz-badge {{ ($selectedEntry['pc_only'] ?? false) ? 'pc' : 'safe' }}">
                                    {{ ($selectedEntry['pc_only'] ?? false) ? 'Pouze PC' : 'PlayStation · Xbox · PC' }}
                                </span>
                            </div>
                        </div>
                        <div class="dz-fields">
                            @foreach ([
                                'nominal' => ['Cílové množství', 1000, 'Počet kusů, který ekonomika udržuje. Editor povoluje 0–1000; 0 = CE předmět běžně nedoplňuje. DayZ pevné maximum nepublikuje.'],
                                'min' => ['Minimální množství', 1000, 'Hranice doplnění. Editor povoluje 0–1000; hodnota má být nejvýše nominal. DayZ pevné maximum nepublikuje.'],
                                'lifetime' => ['Životnost (sekundy)', 3888000, 'Jak dlouho předmět zůstane ve světě. Editor povoluje 0–3 888 000 s; DayZ pevné maximum nepublikuje.'],
                                'restock' => ['Doba doplnění (sekundy)', 86400, 'Prodleva mezi doplněními. Editor povoluje 0–86 400 s; DayZ pevné maximum nepublikuje.'],
                                'quantmin' => ['Minimální naplnění (%)', 100, 'Rozsah −1 až 100 %. −1 = výchozí chování hry.'],
                                'quantmax' => ['Maximální naplnění (%)', 100, 'Rozsah −1 až 100 %. −1 = výchozí chování hry.'],
                                'cost' => ['Priorita ekonomiky', 1000, 'Relativní váha v ekonomice. Editor povoluje 0–1000; DayZ pevné maximum nepublikuje.'],
                            ] as $field => [$label, $max, $help])
                                @php
                                    $sliderMax = max($max, (int) ($typeForm[$field] ?? 0));
                                @endphp
                                <label class="dz-field" title="Raw: &lt;{{ $field }}&gt;{{ $typeForm[$field] ?? '' }}&lt;/{{ $field }}&gt;">
                                    <span class="dz-field-top">
                                        <span><strong>{{ $label }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                        <input type="number" wire:model="typeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}" max="{{ $max }}">
                                    </span>
                                    <input type="range" wire:model.live="typeForm.{{ $field }}" min="{{ str_starts_with($field, 'quant') ? -1 : 0 }}" max="{{ $sliderMax }}">
                                    @error("typeForm.$field") <div class="dz-error">{{ $message }}</div> @enderror
                                </label>
                            @endforeach
                        </div>
                        <div class="dz-types-advanced">
                            <details class="dz-group" open>
                                <summary><span>Počítání položky v ekonomice · flags</span><small>6 přepínačů</small></summary>
                                <div class="dz-types-flags">
                                    @foreach ([
                                        'count_in_cargo' => ['Počítat v nákladu', '1 = kusy uvnitř cargo kontejnerů se započítají do limitu CE.'],
                                        'count_in_hoarder' => ['Počítat ve skrýších', '1 = kusy v hoarder úložištích se započítají do limitu.'],
                                        'count_in_map' => ['Počítat na mapě', '1 = kusy ležící ve světě se započítají; pro běžný loot obvykle zapnuto.'],
                                        'count_in_player' => ['Počítat u hráčů', '1 = kusy v inventáři hráčů se započítají do limitu CE.'],
                                        'crafted' => ['Vyráběná položka', '1 = položka vzniká craftingem a CE s ní zachází jako s crafted typem.'],
                                        'deloot' => ['Dynamic Event Loot', '1 = položka patří do loot poolu dynamických eventů.'],
                                    ] as $flag => [$label, $help])
                                        <label><span><strong>{{ $label }}</strong><small>{{ $help }}</small></span><select wire:model="typeForm.{{ $flag }}"><option value="0">0 · vypnuto</option><option value="1">1 · zapnuto</option></select></label>
                                    @endforeach
                                </div>
                            </details>
                            <details class="dz-group">
                                <summary><span>Zařazení položky · category, usage, tag, value</span><small>seznamy CE</small></summary>
                                <div class="dz-types-taxonomy">
                                    <label><span><strong>Category</strong><small>Jedna hlavní kategorie CE, například weapons, clothes nebo food.</small></span><input type="text" wire:model="typeForm.category"></label>
                                    <label><span><strong>Usage</strong><small>Oblasti výskytu oddělené čárkou, například Military, Police, Town.</small></span><input type="text" wire:model="typeForm.usages_csv"></label>
                                    <label><span><strong>Tag</strong><small>Volitelné tag limitery oddělené čárkou. Prázdné = žádný tag.</small></span><input type="text" wire:model="typeForm.tags_csv"></label>
                                    <label><span><strong>Value</strong><small>Tier/value limitery oddělené čárkou, například Tier1, Tier2.</small></span><input type="text" wire:model="typeForm.values_csv"></label>
                                </div>
                            </details>
                        </div>
                        <div class="dz-savebar">
                            <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny (např. zvýšení lootů AKM)">
                            <x-dz-confirm-button call="saveType()" label="Uložit novou revizi" class="dz-action" />
                            @php $removeTypeConfirm = "Opravdu odebrat položku '{$selectedType}' z types.xml? Vytvoří se nová revize, originál zůstane zachovaný. Předměty tohoto typu se přestanou spawnovat."; @endphp
                            <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($removeTypeConfirm)).then((ok) => { if (ok) $wire.removeType(); })">Smazat položku</button>
                        </div>
                    @else
                        <div class="p-10 text-center">
                            <strong>Vyber položku vlevo</strong>
                            <p class="dz-muted mt-2">Pak se zobrazí posuvníky a přesné číselné hodnoty.</p>
                        </div>
                    @endif
                </section>
            </div>
            @elseif ($visualKind === 'events')
            <div class="dz-editor-grid">
                <section class="dz-panel">
                    <div class="dz-panel-head">
                        <strong>Eventy · events.xml</strong>
                        <p class="dz-muted text-sm mt-1">Vyhledej event a uprav jen jeho detail. Nejvýše 200 výsledků.</p>
                        <input wire:model.live.debounce.250ms="eventSearch" class="dz-search mt-3" placeholder="Hledat například StaticHeliCrash…">
                    </div>
                    <div class="dz-list">
                        @foreach ($this->filteredEvents() as $entry)
                            <button type="button" wire:click="selectEvent(@js($entry['name']))" class="dz-item {{ $selectedEvent === $entry['name'] ? 'active' : '' }}">
                                <span>{{ $entry['name'] }}</span>
                                <span class="dz-item-meta">
                                    <small>{{ $entry['nominal'] }} nominal · {{ $entry['children_count'] }} objektů</small>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="dz-panel">
                    @if ($selectedEvent)
                        <div class="dz-panel-head">
                            @php
                                $isAnimalEvent = str_starts_with($selectedEvent, 'Animal');
                                $childrenMin = collect($eventForm['children'] ?? [])->sum(fn ($child) => (int) ($child['min'] ?? 0));
                                $childrenMax = collect($eventForm['children'] ?? [])->sum(fn ($child) => (int) ($child['max'] ?? 0));
                                $nominal = (int) ($eventForm['nominal'] ?? 0);
                                $territoryInfo = $isAnimalEvent ? $this->animalTerritoryInfo($selectedEvent) : null;
                            @endphp
                            <strong>{{ $selectedEvent }}</strong>
                            @if ($isAnimalEvent)
                                <p class="dz-muted text-sm mt-2">
                                    Animal event · nominal {{ $nominal }} = cílový počet současných skupin.
                                    Přibližně {{ $nominal * $childrenMin }}–{{ $nominal * $childrenMax }} zvířat podle nastavení children.
                                    Teritoria pouze určují kandidátní oblasti, ne počet zvířat.
                                    @if ($territoryInfo)
                                        V mapě je nyní {{ $territoryInfo['count'] }} kandidátních oblastí v {{ $territoryInfo['file'] }}.
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="dz-fields">
                            @foreach ([
                                'nominal' => ['Cílový počet eventů', 1000, 'Kolik instancí eventu CE udržuje najednou. Nezáporné celé číslo.'],
                                'min' => ['Minimum současně', 1000, 'Pod touto hranicí CE doplňuje nové instance. Nezáporné celé číslo.'],
                                'max' => ['Maximum současně', 1000, 'Horní limit současně aktivních instancí. Nezáporné celé číslo.'],
                                'lifetime' => ['Životnost (s)', 3888000, 'Jak dlouho instance eventu zůstane ve světě.'],
                                'restock' => ['Doplnění (s)', 3888000, 'Prodleva mezi doplněními instancí eventu.'],
                                'saferadius' => ['Bezpečný poloměr (m)', 20000, 'Minimální bezpečná vzdálenost od hráče při vytvoření eventu.'],
                                'distanceradius' => ['Vzdálenost od dalších instancí (m)', 20000, 'Používá se při kontrole kolize s dalšími instancemi nebo hráči.'],
                                'cleanupradius' => ['Poloměr úklidu (m)', 20000, 'Poloměr, ve kterém se po expiraci uklidí objekty eventu.'],
                            ] as $field => [$label, $max, $help])
                                @php $sliderMax = max($max, (int) ($eventForm[$field] ?? 0)); @endphp
                                <label class="dz-field">
                                    <span class="dz-field-top">
                                        <span><strong>{{ $label }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                        <input type="number" wire:model="eventForm.{{ $field }}" min="0" max="{{ $max }}">
                                    </span>
                                    <input type="range" wire:model.live="eventForm.{{ $field }}" min="0" max="{{ $sliderMax }}">
                                    @error("eventForm.$field") <div class="dz-error">{{ $message }}</div> @enderror
                                </label>
                            @endforeach
                        </div>
                        <div class="dz-types-advanced">
                            <details class="dz-group" open>
                                <summary><span>Chování eventu · flags, position, limit</span><small>7 nastavení</small></summary>
                                <div class="dz-types-flags">
                                    @foreach ([
                                        'deletable' => 'Lze odstranit hráčem/dalším eventem.',
                                        'init_random' => 'Náhodně posune první spuštění eventu po startu serveru.',
                                        'remove_damaged' => 'Odstraní poškozené objekty místo jejich ponechání ve světě.',
                                    ] as $flag => $help)
                                        <label><span><strong>{{ str($flag)->headline() }}</strong><small>{{ $help }}</small></span><select wire:model="eventForm.{{ $flag }}"><option value="0">0 · vypnuto</option><option value="1">1 · zapnuto</option></select></label>
                                    @endforeach
                                    <label><span><strong>Position</strong><small>fixed = pevné pozice z cfgeventspawns.xml; player = kolem hráčů.</small></span>
                                        <select wire:model="eventForm.position"><option value="fixed">fixed</option><option value="player">player</option></select>
                                    </label>
                                    <label><span><strong>Limit</strong><small>Jak CE rozhoduje mezi kandidátními pozicemi.</small></span>
                                        <select wire:model="eventForm.limit">
                                            <option value="mixed">mixed</option>
                                            <option value="unlimited">unlimited</option>
                                            <option value="nearest">nearest</option>
                                            <option value="farthest">farthest</option>
                                        </select>
                                    </label>
                                    <label><span><strong>Active</strong><small>0 = event je v souboru, ale server ho nepoužívá.</small></span><select wire:model="eventForm.active"><option value="1">1 · aktivní</option><option value="0">0 · vypnuto</option></select></label>
                                </div>
                            </details>
                            <details class="dz-group" open>
                                <summary><span>Spawnované objekty · children</span><small>{{ count($eventForm['children'] ?? []) }}</small></summary>
                                <div class="dz-event-children">
                                    @foreach ($eventForm['children'] ?? [] as $index => $child)
                                        <article class="dz-event-child" wire:key="event-child-{{ $index }}">
                                            <header>
                                                @php
                                                    $childClass = (string) ($child['type'] ?? '');
                                                    $animalRole = $isAnimalEvent
                                                        ? (str_ends_with($childClass, 'F') ? 'Samice' : 'Samec')
                                                        : null;
                                                @endphp
                                                <span><small>{{ $isAnimalEvent ? 'ZVÍŘE' : 'OBJEKT' }} {{ $index + 1 }}</small>@if ($animalRole)<strong>{{ $animalRole }}</strong>@endif</span>
                                                <button type="button" wire:click="removeEventChild({{ $index }})" class="dz-danger">Odebrat</button>
                                            </header>
                                            <label><span>Classname</span><input type="text" wire:model="eventForm.children.{{ $index }}.type" placeholder="Např. Wreck_UH1Y"></label>
                                            <label><span>{{ $isAnimalEvent ? 'Minimum kusů v jedné skupině' : 'Min' }}</span><input type="number" min="0" wire:model="eventForm.children.{{ $index }}.min"></label>
                                            <label><span>{{ $isAnimalEvent ? 'Maximum kusů v jedné skupině' : 'Max' }}</span><input type="number" min="0" wire:model="eventForm.children.{{ $index }}.max"></label>
                                            @if (! $isAnimalEvent)
                                                <label><span>Loot min</span><input type="number" min="0" wire:model="eventForm.children.{{ $index }}.lootmin"></label>
                                                <label><span>Loot max</span><input type="number" min="0" wire:model="eventForm.children.{{ $index }}.lootmax"></label>
                                            @else
                                                <small class="dz-muted">U zvířat Min/Max znamená počet kusů v jedné skupině. Loot se zde nepoužívá.</small>
                                            @endif
                                        </article>
                                    @endforeach
                                    <button type="button" wire:click="addEventChild" class="dz-secondary">+ Přidat objekt</button>
                                </div>
                            </details>
                        </div>
                        <div class="dz-savebar">
                            <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny (např. snížení nominal)">
                            <x-dz-confirm-button call="saveEvent()" label="Uložit novou revizi" class="dz-action" />
                        </div>
                    @else
                        <div class="p-10 text-center">
                            <strong>Vyber event vlevo</strong>
                            <p class="dz-muted mt-2">Pak se zobrazí posuvníky, přepínače a spawnované objekty.</p>
                        </div>
                    @endif
                </section>
            </div>
            @elseif ($visualKind === 'environment')
            @php
                $environmentItemHelp = [
                    'globalCountMax' => 'Nejvyšší celkový počet jedinců druhu na serveru.',
                    'zoneCountMin' => 'Minimální počet jedinců v jedné zóně teritoria.',
                    'zoneCountMax' => 'Maximální počet jedinců v jedné zóně teritoria.',
                    'playerSpawnRadiusNear' => 'Bližší poloměr od hráče (m), který ovlivňuje spawn.',
                    'playerSpawnRadiusFar' => 'Vzdálenější poloměr od hráče (m), který ovlivňuje spawn.',
                    'herdsCount' => 'Kolik stád/skupin tohoto typu server drží najednou.',
                    'zoneTouchDisableEditPeriodSec' => 'Po vstupu hráče do zóny (s) se zóna dočasně nepřegeneruje.',
                    'countMin' => 'Minimální počet pro tohoto agenta (typicky u nakažených).',
                    'countMax' => 'Maximální počet pro tohoto agenta (typicky u nakažených).',
                ];
            @endphp
            <datalist id="dz-environment-item-names">
                @foreach (array_keys($environmentItemHelp) as $itemName)
                    <option value="{{ $itemName }}">
                @endforeach
            </datalist>
            <datalist id="dz-environment-behaviors">
                @foreach (['DZWolfGroupBeh', 'DZDeerGroupBeh', 'DZSheepGroupBeh', 'DZdomesticGroupBeh', 'BlissBearGroupBeh', 'DZAmbientLifeGroupBeh'] as $behavior)
                    <option value="{{ $behavior }}">
                @endforeach
            </datalist>
            <datalist id="dz-classname-catalog">
                @foreach (collect($environmentEntries)->flatMap(fn ($entry) => collect($entry['agents'] ?? [])->flatMap(fn ($agent) => collect($agent['spawns'] ?? [])->pluck('configName')))->filter()->unique()->sort() as $classname)
                    <option value="{{ $classname }}">
                @endforeach
            </datalist>
            <div class="dz-editor-grid">
                <section class="dz-panel">
                    <div class="dz-panel-head">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <strong>Zvířata a nakažení · cfgenvironment.xml</strong>
                                <p class="dz-muted text-sm mt-1">Kde se druh vyskytuje (teritoria), jaké třídy spawnuje a v jakém počtu.</p>
                            </div>
                            <button type="button" wire:click="openAddAnimalForm" class="dz-secondary">+ Přidat zvíře</button>
                        </div>
                        <input wire:model.live.debounce.250ms="environmentSearch" class="dz-search mt-3" placeholder="Hledat například Wolf…">
                    </div>
                    <div class="dz-list">
                        @foreach ($this->filteredEnvironmentEntries() as $entry)
                            <button type="button" wire:click="selectEnvironmentTerritory(@js($entry['name']))" class="dz-item {{ $selectedEnvironmentTerritory === $entry['name'] ? 'active' : '' }}">
                                <span>{{ $entry['name'] }}</span>
                                <span class="dz-item-meta">
                                    @if ($entry['is_infected'])<span class="dz-badge pc">Nakažení</span>@endif
                                    <small>{{ $entry['type'] }} · {{ $entry['file'] ?: 'bez souboru' }} · {{ $entry['agent_count'] }} agentů</small>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="dz-panel">
                    @if ($showAddAnimalForm)
                        <div class="dz-panel-head">
                            <strong>Nové zvíře v cfgenvironment.xml</strong>
                            <p class="dz-muted text-sm mt-1">Jméno se kontroluje bez ohledu na velikost písmen. Napojení na mapu (přidávání zón) uvidíš hned po uložení v Mapovém editoru.</p>
                        </div>
                        <div class="dz-add-grid">
                            <div class="dz-control">
                                <label>Jméno druhu</label>
                                <input wire:model="newAnimalForm.name" placeholder="Například Lynx">
                                @error('newAnimalForm.name') <div class="dz-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="dz-control">
                                <label>Typ</label>
                                <select wire:model="newAnimalForm.type">
                                    <option value="Herd">Herd · stádo</option>
                                    <option value="Ambient">Ambient · ambientní jedinci</option>
                                </select>
                            </div>
                            <div class="dz-control">
                                <label>Behavior</label>
                                <input wire:model="newAnimalForm.behavior" list="dz-environment-behaviors" placeholder="Například DZWolfGroupBeh">
                                @error('newAnimalForm.behavior') <div class="dz-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="px-4 pb-4">
                            <strong>Soubor teritorií</strong>
                            <p class="dz-muted text-sm mt-1">Zóny (Water/Rest/Graze/…) tohoto souboru pak upravíš na mapě.</p>
                            <div class="dz-checks mt-2">
                                <label class="dz-check"><input type="radio" wire:model.live="newAnimalForm.file_mode" value="existing"> Použít existující soubor</label>
                                <label class="dz-check"><input type="radio" wire:model.live="newAnimalForm.file_mode" value="new"> Založit nový prázdný soubor</label>
                            </div>
                            @if ($newAnimalForm['file_mode'] === 'new')
                                <input wire:model="newAnimalForm.new_file" class="mt-2" placeholder="Například lynx_territories">
                                <small class="dz-muted">Přípona _territories.xml se doplní automaticky.</small>
                                @error('newAnimalForm.new_file') <div class="dz-error">{{ $message }}</div> @enderror
                            @else
                                <select wire:model="newAnimalForm.file" class="mt-2">
                                    <option value="">— vyber soubor —</option>
                                    @foreach ($this->territoryFileOptions() as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('newAnimalForm.file') <div class="dz-error">{{ $message }}</div> @enderror
                            @endif
                        </div>
                        <div class="px-4 pb-4">
                            <details class="dz-group" open>
                                <summary><span>Agenti · třídy a šance spawnu</span><small>{{ count($newAnimalForm['agents'] ?? []) }}</small></summary>
                                @foreach ($newAnimalForm['agents'] ?? [] as $agentIndex => $agent)
                                    <article class="dz-event-child" wire:key="new-animal-agent-{{ $agentIndex }}">
                                        <header>
                                            <span><small>AGENT {{ $agentIndex + 1 }}</small></span>
                                            <button type="button" wire:click="removeEnvironmentAgent({{ $agentIndex }}, 'newAnimalForm')" class="dz-danger">Odebrat</button>
                                        </header>
                                        <label><span>Typ (Male/Female…)</span><input type="text" wire:model="newAnimalForm.agents.{{ $agentIndex }}.type"></label>
                                        <label><span>Šance agenta</span><input type="number" min="0" wire:model="newAnimalForm.agents.{{ $agentIndex }}.chance"></label>
                                        @foreach ($agent['spawns'] ?? [] as $spawnIndex => $spawn)
                                            <div class="flex gap-2 items-center">
                                                <input type="text" wire:model="newAnimalForm.agents.{{ $agentIndex }}.spawns.{{ $spawnIndex }}.configName" placeholder="Classname, např. Animal_CanisLupus">
                                                <input type="number" min="0" class="w-24" wire:model="newAnimalForm.agents.{{ $agentIndex }}.spawns.{{ $spawnIndex }}.chance" placeholder="Šance">
                                                <button type="button" wire:click="removeEnvironmentAgentSpawn({{ $agentIndex }}, {{ $spawnIndex }}, 'newAnimalForm')" class="dz-danger">×</button>
                                            </div>
                                        @endforeach
                                        <button type="button" wire:click="addEnvironmentAgentSpawn({{ $agentIndex }}, 'newAnimalForm')" class="dz-secondary">+ Třída</button>
                                    </article>
                                @endforeach
                                <button type="button" wire:click="addEnvironmentAgent('newAnimalForm')" class="dz-secondary">+ Agent</button>
                            </details>
                            <details class="dz-group" open>
                                <summary><span>Počty · item name/val</span><small>{{ count($newAnimalForm['items'] ?? []) }}</small></summary>
                                @foreach ($newAnimalForm['items'] ?? [] as $itemIndex => $item)
                                    <div class="flex gap-2 items-center mt-2">
                                        <input type="text" list="dz-environment-item-names" wire:model="newAnimalForm.items.{{ $itemIndex }}.name" placeholder="Např. globalCountMax">
                                        <input type="number" class="w-24" wire:model="newAnimalForm.items.{{ $itemIndex }}.val">
                                        <button type="button" wire:click="removeEnvironmentItem({{ $itemIndex }}, 'newAnimalForm')" class="dz-danger">×</button>
                                        @if (isset($environmentItemHelp[$item['name']])) <small class="dz-muted">{{ $environmentItemHelp[$item['name']] }}</small> @endif
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addEnvironmentItem('newAnimalForm')" class="dz-secondary mt-2">+ Hodnota</button>
                            </details>
                        </div>
                        <div class="dz-savebar">
                            <x-dz-confirm-button call="addAnimalTerritory()" label="Zkontrolovat a přidat" saved-label="Přidáno" class="dz-action" />
                        </div>
                    @elseif ($selectedEnvironmentTerritory)
                        <div class="dz-panel-head">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $selectedEnvironmentTerritory }}</strong>
                                    <p class="dz-muted text-sm mt-1">Napojený soubor teritorií: {{ $environmentForm['file'] ?: 'žádný' }}</p>
                                </div>
                                @if ($environmentForm['is_infected'] ?? false)<span class="dz-badge pc">Nakažení</span>@endif
                            </div>
                        </div>
                        <div class="dz-add-grid">
                            <div class="dz-control">
                                <label>Typ</label>
                                <select wire:model="environmentForm.type">
                                    <option value="Herd">Herd · stádo</option>
                                    <option value="Ambient">Ambient · ambientní jedinci</option>
                                </select>
                            </div>
                            <div class="dz-control">
                                <label>Behavior</label>
                                <input wire:model="environmentForm.behavior" list="dz-environment-behaviors">
                                @error('environmentForm.behavior') <div class="dz-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="dz-control">
                                <label>Soubor teritorií</label>
                                <select wire:model="environmentForm.file">
                                    <option value="">— žádný —</option>
                                    @foreach ($this->territoryFileOptions() as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('environmentForm.file') <div class="dz-error">{{ $message }}</div> @enderror
                                <small class="dz-muted">Přesné pozice zón uprav v <a href="{{ \App\Filament\Pages\MapEditor::getUrl(['project' => $this->getRecord()->id]) }}">Mapovém editoru</a>.</small>
                            </div>
                        </div>
                        <div class="px-4 pb-4">
                            <details class="dz-group" open>
                                <summary><span>Agenti · třídy a šance spawnu</span><small>{{ count($environmentForm['agents'] ?? []) }}</small></summary>
                                <p class="dz-muted px-2 pt-2">@if (($environmentForm['type'] ?? 'Herd') === 'Herd') U stád (Deer, Bear, Wolf…) je 0 agentů správně — zvířata a velikost stáda řídí events.xml. @else Ambient musí mít alespoň jednoho agenta a jeho spawnovanou classname. @endif</p>
                                @foreach ($environmentForm['agents'] ?? [] as $agentIndex => $agent)
                                    <article class="dz-event-child" wire:key="env-agent-{{ $agentIndex }}">
                                        <header>
                                            <span><small>AGENT {{ $agentIndex + 1 }}</small></span>
                                            <button type="button" wire:click="removeEnvironmentAgent({{ $agentIndex }})" class="dz-danger">Odebrat</button>
                                        </header>
                                        <label><span>Typ agenta</span><select wire:model="environmentForm.agents.{{ $agentIndex }}.type"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Jiný typ</option></select></label>
                                        <label><span>Šance agenta</span><input type="number" min="0" wire:model="environmentForm.agents.{{ $agentIndex }}.chance"></label>
                                        @foreach ($agent['spawns'] ?? [] as $spawnIndex => $spawn)
                                            <div class="flex gap-2 items-center">
                                                <input type="text" list="dz-classname-catalog" wire:model="environmentForm.agents.{{ $agentIndex }}.spawns.{{ $spawnIndex }}.configName" placeholder="Vyberte nebo napište classname">
                                                <input type="number" min="0" class="w-24" wire:model="environmentForm.agents.{{ $agentIndex }}.spawns.{{ $spawnIndex }}.chance" placeholder="Šance">
                                                <button type="button" wire:click="removeEnvironmentAgentSpawn({{ $agentIndex }}, {{ $spawnIndex }})" class="dz-danger">×</button>
                                            </div>
                                        @endforeach
                                        @foreach ($agent['items'] ?? [] as $agentItemIndex => $agentItem)
                                            <div class="flex gap-2 items-center mt-1">
                                                <small class="dz-muted">{{ $agentItem['name'] }}</small>
                                                <input type="number" class="w-24" wire:model="environmentForm.agents.{{ $agentIndex }}.items.{{ $agentItemIndex }}.val">
                                            </div>
                                        @endforeach
                                        <button type="button" wire:click="addEnvironmentAgentSpawn({{ $agentIndex }})" class="dz-secondary">+ Třída</button>
                                    </article>
                                @endforeach
                                <button type="button" wire:click="addEnvironmentAgent" class="dz-secondary">+ Agent</button>
                            </details>
                            <details class="dz-group" open>
                                <summary><span>Počty · item name/val</span><small>{{ count($environmentForm['items'] ?? []) }}</small></summary>
                                @foreach ($environmentForm['items'] ?? [] as $itemIndex => $item)
                                    <div class="flex gap-2 items-center mt-2">
                                        <input type="text" list="dz-environment-item-names" wire:model="environmentForm.items.{{ $itemIndex }}.name" placeholder="Např. globalCountMax">
                                        <input type="number" class="w-24" wire:model="environmentForm.items.{{ $itemIndex }}.val">
                                        <button type="button" wire:click="removeEnvironmentItem({{ $itemIndex }})" class="dz-danger">×</button>
                                        @if (isset($environmentItemHelp[$item['name']])) <small class="dz-muted">{{ $environmentItemHelp[$item['name']] }}</small> @endif
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addEnvironmentItem" class="dz-secondary mt-2">+ Hodnota</button>
                            </details>
                        </div>
                        <div class="dz-savebar">
                            <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny (např. zvýšení globalCountMax)">
                            <x-dz-confirm-button call="saveEnvironmentTerritory()" label="Uložit novou revizi" class="dz-action" />
                            @php $removeEnvironmentConfirm = "Opravdu odebrat zvíře '{$selectedEnvironmentTerritory}' z cfgenvironment.xml? Vytvoří se nová revize, originál zůstane zachovaný. Napojený soubor teritorií zůstane netknutý."; @endphp
                            <button type="button" class="dz-danger" x-on:click="dzConfirm(@js($removeEnvironmentConfirm)).then((ok) => { if (ok) $wire.removeEnvironmentTerritory(); })">Smazat zvíře</button>
                        </div>
                    @else
                        <div class="p-10 text-center">
                            <strong>Vyber zvíře vlevo</strong>
                            <p class="dz-muted mt-2">Nebo přidej nové tlačítkem „+ Přidat zvíře“.</p>
                        </div>
                    @endif
                </section>
            </div>
            @elseif ($visualKind === 'server')
                <section class="dz-editor-card"><h3>Serverová nastavení · serverDZ.cfg</h3><p class="dz-muted">Upravujte hodnoty serveru bez ručního psaní CFG syntaxe. Hesla jsou skrytá; prázdné pole zachová původní hodnotu.</p><div class="dz-fields-grid">
                    @foreach ($serverConfig as $key => $value)
                        @php
                            $secret = in_array(strtolower($key), ['password', 'passwordadmin'], true);
                        @endphp
                        <label class="dz-field"><span>{{ $key }}</span><span class="dz-secret-control"><input id="cfg-{{ $key }}" type="{{ $secret ? 'password' : 'text' }}" autocomplete="{{ $secret ? 'new-password' : 'off' }}" placeholder="{{ $secret ? 'Ponechte prázdné pro zachování' : '' }}" wire:model.defer="serverConfig.{{ $key }}">@if($secret)<button type="button" class="dz-reveal" onclick="const i=document.getElementById('cfg-{{ $key }}'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'Zobrazit':'Skrýt';">Zobrazit</button>@endif</span><small>{{ $this->serverFieldDescription($key) }}</small><small>Raw: {{ $key }} = {{ $secret ? '•••••••• (skryto)' : $value }};</small></label>
                    @endforeach
                </div><x-dz-confirm-button call="saveServerConfig()" label="Uložit serverDZ.cfg jako novou revizi" class="dz-save-button" /></section>
            @elseif ($visualKind === 'whitelist')
                <section class="dz-editor-card"><h3>Whitelist hráčů · whitelist.txt</h3><p class="dz-muted">Jeden řádek = jedno UID hráče, volitelně s popisem (server ho čte jako komentář za <code>//</code>, např. jméno hráče). Používejte přesné UID z platformy (Steam/Xbox/PlayStation). Duplicitní, prázdné a neplatné hodnoty se nepřidají. Editor ověřuje délku 3–64 znaků; zapnutí vyžaduje <code>enableWhitelist = 1</code> v serverDZ.cfg.</p>
                    <div class="dz-whitelist-add">
                        <input wire:model.defer="newWhitelistId" placeholder="UID hráče" autocomplete="off">
                        <input wire:model.defer="newWhitelistComment" placeholder="Popis (nepovinné, např. jméno hráče)" autocomplete="off">
                        <button type="button" wire:click="addWhitelistEntry" class="dz-save-button">Přidat hráče</button>
                    </div>
                    <div class="dz-whitelist-list">
                        @forelse($whitelistEntries as $index => $entry)
                            <div class="dz-whitelist-row">
                                <span class="dz-whitelist-row-id"><code>{{ $entry['id'] }}</code>@if($entry['comment'] !== '')<span class="dz-whitelist-comment">// {{ $entry['comment'] }}</span>@endif</span>
                                <button type="button" class="dz-danger" x-on:click="dzConfirm('Opravdu odebrat tento záznam z whitelist.txt?').then((ok) => { if (ok) $wire.removeWhitelistEntry({{ $index }}); })">Odebrat</button>
                            </div>
                        @empty
                            <p class="dz-muted">Whitelist je zatím prázdný.</p>
                        @endforelse
                    </div>
                    <x-dz-confirm-button call="saveWhitelist()" label="Uložit whitelist.txt jako novou revizi" class="dz-save-button" />
                </section>
            @elseif ($visualKind === 'ban')
                <section class="dz-editor-card"><h3>Banlist hráčů · ban.txt</h3><p class="dz-muted">Jeden řádek = jedno UID hráče, kterému server odmítne připojení, volitelně s popisem (server ho čte jako komentář za <code>//</code>, např. důvod banu). Editor kontroluje délku 3–64 znaků a duplicitní hodnoty.</p>
                    <div class="dz-whitelist-add">
                        <input wire:model.defer="newBanId" placeholder="UID hráče k zablokování" autocomplete="off">
                        <input wire:model.defer="newBanComment" placeholder="Popis (nepovinné, např. důvod banu)" autocomplete="off">
                        <button type="button" wire:click="addBanEntry" class="dz-save-button">Přidat zákaz</button>
                    </div>
                    <div class="dz-whitelist-list">
                        @forelse($banEntries as $index => $entry)
                            <div class="dz-whitelist-row">
                                <span class="dz-whitelist-row-id"><code>{{ $entry['id'] }}</code>@if($entry['comment'] !== '')<span class="dz-whitelist-comment">// {{ $entry['comment'] }}</span>@endif</span>
                                <button type="button" class="dz-danger" x-on:click="dzConfirm('Opravdu odebrat tento záznam z ban.txt?').then((ok) => { if (ok) $wire.removeBanEntry({{ $index }}); })">Odebrat</button>
                            </div>
                        @empty
                            <p class="dz-muted">Banlist je zatím prázdný.</p>
                        @endforelse
                    </div>
                    <x-dz-confirm-button call="saveBan()" label="Uložit ban.txt jako novou revizi" class="dz-save-button" />
                </section>
            @elseif ($visualKind === 'priority')
                <section class="dz-editor-card"><h3>Prioritní fronta · priority.txt</h3><p class="dz-muted">Jeden řádek = jedno UID hráče, volitelně s popisem (server ho čte jako komentář za <code>//</code>). Hráči v tomto seznamu dostanou přednost před běžnou přihlašovací frontou. Použití lze vypnout přes <code>disablePrioritylist</code> v serverDZ.cfg.</p>
                    <div class="dz-whitelist-add">
                        <input wire:model.defer="newPriorityId" placeholder="Steam/console UID" autocomplete="off">
                        <input wire:model.defer="newPriorityComment" placeholder="Popis (nepovinné)" autocomplete="off">
                        <button type="button" wire:click="addPriorityEntry" class="dz-save-button">Přidat hráče</button>
                    </div>
                    <div class="dz-whitelist-list">
                        @forelse($priorityEntries as $index => $entry)
                            <div class="dz-whitelist-row">
                                <span class="dz-whitelist-row-id"><code>{{ $entry['id'] }}</code>@if($entry['comment'] !== '')<span class="dz-whitelist-comment">// {{ $entry['comment'] }}</span>@endif</span>
                                <button type="button" class="dz-danger" x-on:click="dzConfirm('Opravdu odebrat tento záznam z priority.txt?').then((ok) => { if (ok) $wire.removePriorityEntry({{ $index }}); })">Odebrat</button>
                            </div>
                        @empty
                            <p class="dz-muted">Priority list je zatím prázdný.</p>
                        @endforelse
                    </div>
                    <x-dz-confirm-button call="savePriority()" label="Uložit priority.txt jako novou revizi" class="dz-save-button" />
                </section>
            @elseif ($visualKind === 'weather')
                @php
                    $weatherSections = [
                        'overcast' => ['Oblačnost', 0, 1, 0.01],
                        'fog' => ['Mlha', 0, 1, 0.01],
                        'rain' => ['Déšť', 0, 1, 0.01],
                        'windMagnitude' => ['Síla větru (m/s)', 0, 50, 0.1],
                        'windDirection' => ['Směr větru (radiány)', -3.14, 3.14, 0.01],
                        'snowfall' => ['Sněžení', 0, 1, 0.01],
                    ];
                @endphp
                <section class="dz-panel dz-server-settings {{ str_ends_with(strtolower($currentFilename), 'startovni-vybava.json') ? 'dz-gear-mode' : '' }}">
                    <div class="dz-panel-head">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <strong>Serverová nastavení · cfgweather.xml</strong>
                                <p class="dz-muted text-sm mt-1">Hodnoty intenzity jsou v rozsahu 0–1 (0 = jev vypnutý, 1 = maximum). Časy zadáváte v minutách; editor je při uložení převede do raw XML v sekundách.</p>
                            </div>
                            <span class="dz-badge dz-server-badge">PS · Xbox · PC</span>
                            <div class="dz-checks">
                                <label class="dz-check"><input type="checkbox" wire:model="weatherForm.enable"> Aktivovat soubor</label>
                                <label class="dz-check"><input type="checkbox" wire:model="weatherForm.reset"> Reset počasí po restartu</label>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 dz-generic-json">
                        @foreach ($weatherSections as $section => [$label, $rangeMin, $rangeMax, $step])
                            <details class="dz-group" open>
                                <summary>
                                    <span>{{ $label }}</span>
                                    <span class="dz-badge safe">PS · Xbox · PC</span>
                                </summary>
                                <div class="dz-fields">
                                    @foreach ([
                                        'current_actual' => ['Aktuální intenzita', $rangeMin, $rangeMax, $step, "Aktuální síla {$label}; rozsah {$rangeMin}–{$rangeMax}. 0 = vypnuto, maximum = {$rangeMax}."],
                                        'current_time' => ['Čas přechodu (min)', 0, 1440, 1, 'Čas do dosažení cílové hodnoty. Editor povoluje 0–1 440 min a do XML ukládá sekundy; DayZ pevné maximum nepublikuje.'],
                                        'current_duration' => ['Doba trvání (min)', 0, 1440, 1, 'Jak dlouho cílový stav zůstane. Editor povoluje 0–1 440 min a do XML ukládá sekundy; DayZ pevné maximum nepublikuje.'],
                                        'limits_min' => ['Minimální intenzita', $rangeMin, $rangeMax, $step, "Nejnižší náhodná hodnota; rozsah {$rangeMin}–{$rangeMax}."],
                                        'limits_max' => ['Maximální intenzita', $rangeMin, $rangeMax, $step, "Nejvyšší náhodná hodnota; rozsah {$rangeMin}–{$rangeMax}."],
                                        'timelimits_min' => ['Min. čas změny (min)', 0, 1440, 1, 'Nejkratší náhodná doba změny. Editor povoluje 0–1 440 min a do XML ukládá sekundy; musí být ≤ maximu.'],
                                        'timelimits_max' => ['Max. čas změny (min)', 0, 1440, 1, 'Nejdelší náhodná doba změny. Editor povoluje 0–1 440 min a do XML ukládá sekundy; DayZ pevné maximum nepublikuje.'],
                                        'changelimits_min' => ['Minimální změna', $rangeMin, $rangeMax, $step, "Nejmenší náhodná změna; rozsah {$rangeMin}–{$rangeMax}."],
                                        'changelimits_max' => ['Maximální změna', $rangeMin, $rangeMax, $step, "Největší náhodná změna; rozsah {$rangeMin}–{$rangeMax}."],
                                    ] as $suffix => [$fieldLabel, $min, $max, $fieldStep, $help])
                                        @php
                                            $weatherKey = $section . '_' . $suffix;
                                        @endphp
                                        <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
                                            <span class="dz-field-top">
                                                <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }} (pro {{ strtolower($label) }}).</small></span>
                                                <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                            </span>
                                            <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                            @error("weatherForm.$weatherKey") <div class="dz-error">{{ $message }}</div> @enderror
                                        </label>
                                    @endforeach

                                    @if (in_array($section, ['rain', 'snowfall'], true))
                                        @foreach ([
                                            'thresholds_min' => ['Min. oblačnost pro srážky', 0, 1, 0.01, 'Rozsah 0–1; pod touto oblačností se déšť nebo sníh nespustí.'],
                                            'thresholds_max' => ['Max. oblačnost pro srážky', 0, 1, 0.01, 'Rozsah 0–1; nad touto oblačností se srážky mohou objevit.'],
                                            'thresholds_end' => ['Čas ukončení srážek (min)', 0, 1440, 1, 'Doba zastavení srážek po opuštění rozsahu oblačnosti. Editor povoluje 0–1 440 min a do XML ukládá sekundy.'],
                                        ] as $suffix => [$fieldLabel, $min, $max, $fieldStep, $help])
                                            @php
                                                $weatherKey = $section . '_' . $suffix;
                                            @endphp
                                            <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
                                                <span class="dz-field-top">
                                                    <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                                    <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                                </span>
                                                <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </details>
                        @endforeach

                        <details class="dz-group" open>
                            <summary><span>Bouřka a blesky</span><span class="dz-badge safe">PS · Xbox · PC</span></summary>
                            <div class="dz-fields">
                                @foreach ([
                                    'storm_density' => ['Hustota blesků', 0, 1, 0.01, 'Pravděpodobnost/hustota blesků; rozsah 0–1. 0 = žádné blesky.'],
                                    'storm_threshold' => ['Práh oblačnosti', 0, 1, 0.01, 'Minimální oblačnost pro bouřku; rozsah 0–1.'],
                                    'storm_timeout' => ['Prodleva mezi blesky (min)', 0, 60, 0.1, 'Čas mezi údery blesku. Editor povoluje 0–60 min a do XML ukládá sekundy; DayZ pevné maximum nepublikuje.'],
                                ] as $weatherKey => [$fieldLabel, $min, $max, $fieldStep, $help])
                                    <label class="dz-field" data-tooltip="Raw: {{ $weatherKey }} = {{ $weatherForm[$weatherKey] ?? '' }}">
                                        <span class="dz-field-top">
                                            <span><strong>{{ $fieldLabel }}</strong><br><small class="dz-muted">{{ $help }}</small></span>
                                            <input type="number" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model="weatherForm.{{ $weatherKey }}">
                                        </span>
                                        <input type="range" step="{{ $fieldStep }}" min="{{ $min }}" max="{{ $max }}" wire:model.live="weatherForm.{{ $weatherKey }}">
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    <div class="dz-savebar">
                        <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny počasí">
                        <x-dz-confirm-button call="saveWeather()" label="Uložit počasí jako revizi" class="dz-action" />
                    </div>
                </section>
            @elseif ($visualKind === 'json')
                @php
                    $jsonDescriptions = [
                        'sprintStaminaModifierErc' => 'Násobič spotřeby staminy při běhu vpřed; 1 = výchozí, <1 menší spotřeba, >1 větší spotřeba. Doporučený rozsah 0–5.',
                        'sprintStaminaModifierCro' => 'Násobič spotřeby staminy při běhu skrčmo; 1 = výchozí, <1 menší spotřeba, >1 větší spotřeba. Doporučený rozsah 0–5.',
                        'staminaMax' => 'Maximální zásoba staminy; doporučený rozsah 1–100.',
                        'staminaMinCap' => 'Minimální zůstatek staminy; rozsah 0–100.',
                        'staminaWeightLimitThreshold' => 'Hmotnost v gramech, od které se uplatní postih staminy; rozsah 0–50 000 g.',
                        'staminaKgToStaminaPercentPenalty' => 'Postih staminy za nadlimitní kilogram; kladné desetinné číslo.',
                        'sprintSwimmingStaminaModifier' => 'Násobič spotřeby při plavání; 1 = výchozí, doporučený rozsah 0–5.',
                        'sprintLadderStaminaModifier' => 'Násobič spotřeby při lezení po žebříku; 1 = výchozí, rozsah 0–5.',
                        'meleeStaminaModifier' => 'Násobič spotřeby při útoku zblízka; 1 = výchozí, rozsah 0–5.',
                        'obstacleTraversalStaminaModifier' => 'Násobič spotřeby při překonávání překážek; 1 = výchozí, rozsah 0–5.',
                        'holdBreathStaminaModifier' => 'Násobič spotřeby při zadržení dechu; 1 = výchozí, rozsah 0–5.',
                        'disableBaseDamage' => 'Ničení základen: Zapnuto (true) = ničení je zakázáno; Vypnuto (false) = ničení je povoleno.',
                        'disableContainerDamage' => 'Ničení kontejnerů: Zapnuto (true) = poškození je zakázáno; Vypnuto (false) = poškození je povoleno.',
                        'disableRespawnDialog' => 'Dialog respawnu: Zapnuto (true) = dialog je skrytý; Vypnuto (false) = dialog se zobrazuje.',
                        'disableRespawnInUnconsciousness' => 'Respawn v bezvědomí: Zapnuto (true) = respawn je zakázán; Vypnuto (false) = respawn je povolen.',
                        'disablePersonalLight' => 'Osobní světlo hráče: Zapnuto (true) = hráči nemohou používat osobní světlo; Vypnuto (false) = osobní světlo je povoleno.',
                        'allowRefillSpeedModifier' => 'Modifikátor doplňování šoku: Zapnuto (true) = použije nastavené rychlosti doplnění; Vypnuto (false) = použije výchozí rychlost hry.',
                        'allowStaminaAffectInertia' => 'Vliv staminy na setrvačnost: Zapnuto (true) = nízká stamina zpomaluje změny pohybu; Vypnuto (false) = setrvačnost není staminou ovlivněna.',
                        'disableIsCollidingBBoxCheck' => 'Kontrola kolize obdélníku: Zapnuto (true) = kontrola se vypne; Vypnuto (false) = hra brání koliznímu umístění.',
                        'disableIsCollidingPlayerCheck' => 'Kontrola kolize s hráčem: Zapnuto (true) = kontrola se vypne; Vypnuto (false) = hráč brání umístění.',
                        'disableIsClippingRoofCheck' => 'Kontrola střechy: Zapnuto (true) = lze umisťovat přes střechu; Vypnuto (false) = hra střechu kontroluje.',
                        'disableIsBaseViableCheck' => 'Kontrola platnosti základny: Zapnuto (true) = kontrola se vypne; Vypnuto (false) = vyžaduje se platné místo.',
                        'disableIsPlacementPermittedCheck' => 'Kontrola povoleného umístění: Zapnuto (true) = omezení se vypne; Vypnuto (false) = platí pravidla umístění.',
                        'disableHeightPlacementCheck' => 'Kontrola výšky: Zapnuto (true) = výška se nekontroluje; Vypnuto (false) = umístění musí být ve vhodné výšce.',
                        'disablePerformRoofCheck' => 'Kontrola střechy při stavbě: Zapnuto (true) = kontrola vypnuta; Vypnuto (false) = střecha se kontroluje.',
                        'disableIsCollidingCheck' => 'Kontrola kolize při stavbě: Zapnuto (true) = kolize se ignoruje; Vypnuto (false) = kolize brání stavbě.',
                        'disableDistanceCheck' => 'Kontrola vzdálenosti při stavbě: Zapnuto (true) = vzdálenost se ignoruje; Vypnuto (false) = platí limit vzdálenosti.',
                        'hitDirectionOverrideEnabled' => 'Směr zásahu: Zapnuto (true) = použije vlastní nastavení indikátoru; Vypnuto (false) = výchozí chování hry.',
                        'hitIndicationPostProcessEnabled' => 'Post-processing zásahu: Zapnuto (true) = zobrazí obrazový efekt; Vypnuto (false) = efekt je vypnutý.',
                        'ignoreMapOwnership' => 'Vlastnictví mapy: Zapnuto (true) = vlastnictví se ignoruje; Vypnuto (false) = vlastnictví se kontroluje.',
                        'ignoreNavItemsOwnership' => 'Vlastnictví navigačních položek: Zapnuto (true) = ignorovat; Vypnuto (false) = kontrolovat.',
                        'lightingConfig' => 'Profil osvětlení světa; 0 = standardní profil, další hodnoty závisí na verzi/mapě.',
                        'use3DMap' => '3D mapa v rozhraní: true = zapnuto, false = vypnuto.',
                        'displayPlayerPosition' => 'Zobrazení pozice hráče na mapě: true = zapnuto, false = vypnuto.',
                        'displayNavInfo' => 'Navigační informace na mapě: true = zapnuto, false = vypnuto.',
                        'objectSpawnersArr' => 'Seznam JSON souborů Object Spawneru v mission složce, např. ["spawnerData.json"]. Vyžaduje enableCfgGameplayFile = 1.',
                        'spawnGearPresetFiles' => 'Seznam JSON presetů startovní výbavy, např. ["survivalist.json"]. Vyšší spawnWeight znamená častější výběr presetu.',
                        'Objects' => 'Objekty vytvořené při startu mise. Každý záznam obsahuje name, pos [X,Y,Z], ypr [yaw,pitch,roll], scale a enableCEPersistency.',
                        'Areas' => 'Seznam statických efektových nebo kontaminovaných oblastí. Pozice používá [X,Y,Z], Radius je v metrech.',
                        'Triggers' => 'Spouštěče podzemních prostor; určují oblast aktivace, typ triggeru a chování prostředí.',
                        'Breadcrumbs' => 'Pomocné body podzemní trasy používané pro přechody a orientaci systému podzemních oblastí.',
                        'pos' => 'Souřadnice objektu [X,Y,Z] v herním světě.',
                        'Pos' => 'Souřadnice středu oblasti [X,Y,Z] v herním světě.',
                        'ypr' => 'Orientace [yaw,pitch,roll] ve stupních.',
                        'Radius' => 'Poloměr oblasti v metrech. Hodnota musí být kladná; u kontaminace ovlivňuje počet částic a výkon.',
                        'scale' => 'Násobek původní velikosti objektu; 1 = původní velikost.',
                        'enableCEPersistency' => 'true = po interakci může objekt přejít do persistence Central Economy; false = bez CE persistence.',
                        'spawnWeight' => 'Relativní váha výběru presetu; minimum 1, vyšší číslo znamená častější výběr.',
                        'characterTypes' => 'Seznam tříd postav použitelných pro tento spawn preset.',
                        'attachmentSlotItemSets' => 'Výbava přiřazená do slotů postavy, včetně variant a jejich vah.',
                        'discreteUnsortedItemSets' => 'Položky vložené do inventáře postavy bez pevného attachment slotu.',
                    ];
                @endphp
                <section class="dz-panel dz-server-settings">
                    <div class="dz-panel-head"><strong>{{ $currentFilename }} · vizuální editor</strong><p class="dz-muted text-sm mt-1">Nastavení je rozdělené podle sekcí JSON. Pole jsou odvozena přímo z importovaného souboru.</p></div>
                    @if (str_ends_with(strtolower($currentFilename), 'startovni-vybava.json'))
                        <div class="dz-gear-profile m-3">
                            <div><span class="dz-muted text-sm">Název profilu výbavy</span><strong>{{ data_get($jsonValues, 'name', 'Bez názvu') }}</strong></div>
                            <div><span class="dz-muted text-sm">Povolené postavy</span><strong>{{ count((array) data_get($jsonValues, 'characterTypes', [])) }}</strong></div>
                        </div>
                        <div class="dz-info m-3">
                            <strong>Jak sestavit výbavu postavy</strong>
                            <p class="dz-muted text-sm mt-1">Nejdřív vyber vybavení ve slotech postavy: hlava, tělo, batoh, kalhoty, rukavice, zbraň a další. U každého vybraného kontejneru se jeho obsah zobrazí jako „Obsah tohoto kontejneru“ — tam přidej povolené itemy, například zásobníky, náboje nebo zdravotní předměty.</p>
                            <p class="dz-muted text-sm mt-1">Kapacitu batohu, vesty a oblečení určuje hra podle jejich reálné konfigurace. Editor proto nepovolí slíbit více prostoru, než skutečný item nabízí; pokud obsah překročí kapacitu, DayZ část obsahu při spawnu odmítne.</p>
                        </div>
                        <div class="dz-gear-inventory-grid m-3">
                            @foreach ($this->gearInventoryTree() as $gearSlot)
                                <details class="dz-gear-slot" wire:key="gear-slot-{{ $gearSlot['slot'] }}">
                                    <summary><strong>{{ $gearSlot['label'] }}</strong><span>{{ $gearSlot['item'] ?: 'Prázdný slot' }}</span></summary>
                                    <div class="dz-gear-slot-body">
                                        @if ($gearSlot['slot'] !== 'freeInventory')<label class="dz-control"><span>Vybavení ve slotu</span><select wire:model="jsonValues.{{ $gearSlot['path'] }}.itemType">
                                            <option value="">Vyber vybavení…</option>
                                            @foreach ($this->gearClassOptions() as $gearClass)<option value="{{ $gearClass }}" @selected($gearSlot['item'] === $gearClass)>{{ $gearClass }}</option>@endforeach
                                        </select></label>@endif
                                        @if ($gearSlot['slot'] === 'freeInventory')
                                            <strong>Položky přímo v inventáři hráče</strong>
                                            @foreach ($gearSlot['children'] as $child)
                                                <div class="dz-gear-child"><select wire:model="jsonValues.{{ $child['path'] }}.itemType">
                                                    <option value="">Vyber item…</option>
                                                    @foreach ($this->gearClassOptions() as $gearClass)<option value="{{ $gearClass }}" @selected($child['item'] === $gearClass)>{{ $gearClass }}</option>@endforeach
                                                </select><button type="button" wire:click="removeGearChild('discreteUnsortedItemSets.0.complexChildrenTypes', {{ $loop->index }})">Odebrat</button></div>
                                            @endforeach
                                            <button type="button" class="dz-secondary mt-2" wire:click="addGearChild('discreteUnsortedItemSets.0.complexChildrenTypes')">+ Přidat item do volného inventáře</button>
                                        @elseif ($gearSlot['container'])
                                            <strong>Obsah kontejneru</strong>
                                            @foreach ($gearSlot['children'] as $child)
                                                <div class="dz-gear-child"><select wire:model="jsonValues.{{ $child['path'] }}.itemType">
                                                    <option value="">Vyber item…</option>
                                                    @foreach ($this->gearClassOptions() as $gearClass)<option value="{{ $gearClass }}" @selected($child['item'] === $gearClass)>{{ $gearClass }}</option>@endforeach
                                                </select><button type="button" wire:click="removeGearChild('{{ $gearSlot['path'] }}.complexChildrenTypes', {{ $loop->index }})">Odebrat</button></div>
                                            @endforeach
                                            <button type="button" class="dz-secondary mt-2" wire:click="addGearChild('{{ $gearSlot['path'] }}.complexChildrenTypes')">+ Přidat item do {{ $gearSlot['label'] }}</button>
                                        @else
                                            <p class="dz-muted text-sm">Tento slot zatím nemá vnořený obsah.</p>
                                        @endif
                                        @if (! $gearSlot['container'] && $gearSlot['slot'] !== 'freeInventory')
                                            <p class="dz-muted text-sm">Tento slot není kontejner — rukavice, boty a podobné vybavení nemají vlastní inventář.</p>
                                        @endif
                                        </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                    <div class="p-3">
                        @if (! str_ends_with(strtolower($currentFilename), 'startovni-vybava.json'))
                        @foreach (collect($jsonFields)->groupBy(fn ($field) => $field['group'] ?? $field['section']) as $section => $fields)
                            <details class="dz-group" @if ($loop->first) open @endif>
                                <summary><span>{{ $this->jsonGroupLabel($section) }}</span><span class="dz-badge dz-server-badge">{{ count($fields) }} nastavení</span></summary>
                                <div class="dz-fields">
                                    @foreach ($fields as $field)
                                        <label class="dz-field" data-tooltip="Raw JSON: {{ $field['path'] }}">
                                            <span class="dz-field-top"><span><strong>{{ $this->jsonFieldLabel($field) }}</strong><br><small class="dz-muted">{{ $this->jsonFieldDescription($field) }}</small></span>
                                                @if ($field['type'] === 'boolean')
                                                    <label class="dz-switch-control" x-data="{ enabled: @js((bool) data_get($jsonValues, $field['path'], false)) }"><input type="checkbox" wire:model.live="jsonValues.{{ $field['path'] }}" x-model="enabled"><span class="dz-toggle-button" :class="enabled ? 'on' : 'off'" x-text="enabled ? 'Zapnuto' : 'Vypnuto'"></span></label>
                                                @elseif ($field['type'] === 'number')
                                                    <input type="number" wire:model="jsonValues.{{ $field['path'] }}">
                                                @elseif ($field['type'] === 'json')
                                                    <textarea rows="8" wire:model="jsonValues.{{ $field['path'] }}" spellcheck="false"></textarea>
                                                @elseif (str_ends_with(strtolower($currentFilename), 'startovni-vybava.json') && $field['label'] === 'Item Type')
                                                    <select wire:model="jsonValues.{{ $field['path'] }}">
                                                        <option value="">Vyber classname z types.xml…</option>
                                                        @foreach ($this->gearClassOptions() as $gearClass)
                                                            <option value="{{ $gearClass }}">{{ $gearClass }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif (str_ends_with(strtolower($currentFilename), 'startovni-vybava.json') && $field['label'] === 'Slot Name')
                                                    <select wire:model="jsonValues.{{ $field['path'] }}">
                                                        @foreach (config('dayz_inventory_ps.slots', []) as $gearSlot => $gearSlotLabel)
                                                            <option value="{{ $gearSlot }}">{{ $gearSlotLabel }} ({{ $gearSlot }})</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="text" wire:model="jsonValues.{{ $field['path'] }}">
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                        @endif
                    </div>
                    <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny JSON konfigurace"><x-dz-confirm-button call="saveJson()" label="Validovat a uložit JSON revizi" class="dz-action" /></div>
                </section>
            @elseif ($visualKind === 'messages')
            @php
                $dzMessageTypes = [
                    'broadcast' => ['label' => 'Opakovaná zpráva', 'description' => 'Zpráva se pravidelně zobrazuje všem hráčům každých X minut, dokud server běží. Použij pro pravidla, reklamu, upozornění.'],
                    'onconnect' => ['label' => 'Zpráva při připojení', 'description' => 'Zpráva se zobrazí jednorázově každému hráči hned po připojení na server. Použij pro uvítání nebo důležité info pro nově přihlášené.'],
                    'shutdown' => ['label' => 'Odpočet do vypnutí serveru', 'description' => 'Zpráva odpočítává čas do vypnutí a server po doběhnutí odpočtu skutečně vypne. Použij text s tokenem #tmin, aby hráči viděli zbývající minuty.'],
                ];
            @endphp
            <section class="dz-panel dz-server-settings">
                <div class="dz-panel-head">
                    <strong>messages.xml · vizuální editor</strong>
                    <p class="dz-muted text-sm mt-1">Každá zpráva je jeden ze tří typů níže — vyber typ a zobrazí se jen pole, která pro něj dávají smysl.</p>
                    <button type="button" wire:click="addMessage" class="dz-save-button">+ Přidat zprávu</button>
                </div>
                <div class="p-3">
                    @forelse ($messagesEntries as $index => $message)
                        @php $dzType = $message['type'] ?? 'broadcast'; @endphp
                        <details class="dz-group" open>
                            <summary>
                                <span>Zpráva #{{ $index + 1 }} <span class="dz-badge dz-server-badge">{{ $dzMessageTypes[$dzType]['label'] ?? $dzType }}</span></span>
                                <span><button type="button" x-on:click="dzConfirm('Opravdu odebrat tuto zprávu? Vytvoří se nová revize.').then((ok) => { if (ok) $wire.removeMessage({{ $index }}); })" class="dz-danger">Odebrat</button></span>
                            </summary>
                            <div class="dz-fields">
                                <div class="dz-field dz-field-wide dz-message-type">
                                    <strong>Typ zprávy</strong>
                                    <select wire:model="messagesEntries.{{ $index }}.type" class="dz-message-type-select">
                                        @foreach ($dzMessageTypes as $value => $meta)
                                            <option value="{{ $value }}">{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <small class="dz-muted">{{ $dzMessageTypes[$dzType]['description'] ?? '' }}</small>
                                </div>

                                @if ($dzType === 'broadcast')
                                    <label class="dz-field" data-tooltip="Interval opakování zprávy v minutách."><span class="dz-field-top"><span><strong>Opakování (min)</strong><br><small class="dz-muted">Jak často (v minutách) se zpráva znovu zobrazí. Prázdné = zobrazí se jen jednou.</small></span><input type="number" min="0" wire:model="messagesEntries.{{ $index }}.repeat"></span></label>
                                    <label class="dz-field" data-tooltip="Zpoždění po startu serveru."><span class="dz-field-top"><span><strong>Zpoždění po startu (min)</strong><br><small class="dz-muted">Kolik minut po startu serveru se má zpráva začít zobrazovat. Prázdné = hned od startu.</small></span><input type="number" min="0" wire:model="messagesEntries.{{ $index }}.delay"></span></label>
                                @elseif ($dzType === 'shutdown')
                                    <label class="dz-field" data-tooltip="Kdy odpočet začne, v minutách před vypnutím."><span class="dz-field-top"><span><strong>Odpočet do vypnutí (min)</strong><br><small class="dz-muted">Kolik minut před vypnutím se má odpočet a zpráva začít zobrazovat. Server se vypne, až odpočet doběhne na nulu.</small></span><input type="number" min="0" wire:model="messagesEntries.{{ $index }}.deadline"></span></label>
                                    <label class="dz-field" data-tooltip="Jak často se zpráva během odpočtu opakuje."><span class="dz-field-top"><span><strong>Opakování odpočtu (min)</strong><br><small class="dz-muted">Jak často (v minutách) se má zpráva s aktuálním zbývajícím časem znovu zobrazit, např. každých 5 minut.</small></span><input type="number" min="0" wire:model="messagesEntries.{{ $index }}.repeat"></span></label>
                                @else
                                    <div class="dz-field dz-field-wide"><span class="dz-muted text-sm">Zpráva při připojení nepoužívá opakování ani zpoždění — zobrazí se hráči jen jednou, ihned po připojení.</span></div>
                                @endif

                                <label class="dz-field dz-field-wide" data-tooltip="Text zprávy podporuje tokeny jako #name a #tmin."><span class="dz-field-top"><span><strong>Text zprávy</strong><br><small class="dz-muted">Text, který se zobrazí hráčům. Podporované tokeny: #name (jméno hráče), #tmin (zbývající minuty do vypnutí, jen u typu „Odpočet do vypnutí“), #pos (pozice hráče).</small></span><textarea wire:model="messagesEntries.{{ $index }}.text" rows="3"></textarea></span></label>
                            </div>
                        </details>
                    @empty
                        <div class="dz-empty-state"><strong>Soubor neobsahuje žádné aktivní zprávy.</strong><p class="dz-muted">Výchozí messages.xml často obsahuje pouze komentované příklady. Použijte tlačítko „Přidat zprávu“.</p></div>
                    @endforelse
                </div>
                <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny messages.xml"><x-dz-confirm-button call="saveMessages()" label="Validovat a uložit messages.xml" class="dz-action" /></div>
            </section>
        @elseif ($visualKind === 'event-spawns')
            <section class="dz-panel dz-event-spawns-editor">
                <div class="dz-panel-head">
                    <strong>Umístění eventů · cfgeventspawns.xml</strong>
                    <p class="dz-muted text-sm mt-1">Každá sekce představuje jeden event a obsahuje jeho kandidátní světové pozice. <b>Název propojuje soubor s events.xml</b>; X/Z jsou souřadnice Chernarus a A je natočení. U velkých souborů se zde kvůli výkonu zobrazí nejvýše prvních 200 pozic; ostatní zůstanou v XML zachované. Pro práci se všemi body použijte Mapový editor.</p>
                    <input wire:model.live.debounce.250ms="eventSpawnSearch" class="dz-search mt-3" placeholder="Hledat event, například StaticHeliCrash… (nejvýše 200 výsledků)">
                </div>
                <div class="dz-event-spawn-guide">
                    <article><b>NÁZEV EVENTU</b><span>Identifikátor, například <code>StaticSantaCrash</code>. Měňte jej jen společně s odpovídajícím názvem v <code>events.xml</code>.</span></article>
                    <article><b>X / Z</b><span>Světové souřadnice kandidátní pozice v rozsahu 0–15 360 metrů. Event si při aktivaci vybírá jednu z těchto pozic.</span></article>
                    <article><b>NATOČENÍ A</b><span>Orientace objektu na místě: 0° = sever, 90° = východ, 180° = jih, 270° = západ.</span></article>
                    <article><b>CO ZDE NENÍ</b><span>Počet, lifetime, restock a vzdálenosti řídí <code>events.xml</code>; složení konvojů a vlaků řídí <code>cfgeventgroups.xml</code>.</span></article>
                </div>
                <div class="dz-event-spawn-list">
                    @forelse ($this->filteredEventSpawns() as $eventIndex => $event)
                        @php $isOpen = $expandedEventSpawn === $event['name_path']; @endphp
                        <details class="dz-event-spawn" @if ($isOpen) open @endif>
                            <summary wire:click.prevent="toggleEventSpawn(@js($event['name_path']))">
                                <span><small>EVENT {{ $eventIndex + 1 }}</small><strong>{{ $xmlValues[$event['name_path']] ?? $event['name'] }}</strong></span>
                                <span class="dz-badge dz-server-badge">{{ count($event['positions']) }} pozic</span>
                            </summary>
                            @if ($isOpen)
                                <div class="dz-event-spawn-body">
                                    <label class="dz-event-name-field">
                                        <span><strong>Název eventu</strong><small>Musí přesně odpovídat eventu v events.xml. Povolená jsou písmena, čísla, tečka, pomlčka a podtržítko.</small></span>
                                        <input type="text" wire:model.blur="xmlValues.{{ $event['name_path'] }}">
                                    </label>
                                    <div class="dz-event-spawn-positions">
                                        @foreach ($event['positions'] as $positionIndex => $position)
                                            <article>
                                                <header><strong>Pozice {{ $positionIndex + 1 }}</strong><code>&lt;pos x="…" z="…" a="…"/&gt;</code></header>
                                                <label><span>X <small>0–15 360 m</small></span><input type="number" min="0" max="15360" step="0.001" wire:model.blur="xmlValues.{{ $position['x_path'] }}"></label>
                                                <label><span>Z <small>0–15 360 m</small></span><input type="number" min="0" max="15360" step="0.001" wire:model.blur="xmlValues.{{ $position['z_path'] }}"></label>
                                                <label><span>Natočení A <small>0 až &lt;360°</small></span><input type="number" min="0" max="359.999" step="0.001" wire:model.blur="xmlValues.{{ $position['a_path'] }}"></label>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </details>
                    @empty
                        <div class="dz-empty-state"><strong>Nebyly nalezeny žádné eventy ani pozice.</strong><span>Ověřte kořenový element eventposdef a strukturu event/pos.</span></div>
                    @endforelse
                </div>
                @error('xmlValues') <div class="dz-error">{{ $message }}</div> @enderror
                <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Např. přesunuty pozice heli crashů"><x-dz-confirm-button call="saveEventSpawns()" label="Validovat a uložit novou revizi" class="dz-action" /></div>
            </section>
        @elseif ($visualKind === 'event-groups')
            <section class="dz-panel dz-event-groups-editor">
                <div class="dz-panel-head">
                    <strong>Skupinové eventy · cfgeventgroups.xml</strong>
                    <p class="dz-muted text-sm mt-1">Soubor určuje, <b>z jakých objektů se skládá jeden vlak, konvoj nebo jiný složený event</b>. Jeho světovou pozici neurčuje tento soubor, ale odpovídající bod v <code>cfgeventspawns.xml</code>.</p>
                </div>
                <div class="dz-event-groups-intro">
                    <article><b>GROUP</b><span>Pojmenovaná šablona celé sestavy. Stejné jméno používá event/spawn, který ji vyvolá.</span></article>
                    <article><b>CHILD</b><span>Jeden objekt sestavy — lokomotiva, vagón, vozidlo nebo dekorace.</span></article>
                    <article><b>X / Z / Y</b><span>Relativní posun objektu vůči kotvě skupiny, nikoli světové souřadnice mapy. Hodnoty jsou v metrech.</span></article>
                    <article><b>A</b><span>Relativní natočení objektu ve stupních 0–360.</span></article>
                    <article><b>LOOTMIN / LOOTMAX</b><span>Minimální a maximální počet loot pozic, které CE v tomto objektu použije. Minimum nesmí být vyšší než maximum.</span></article>
                    <article><b>DELOOT</b><span><code>1</code> = objekt může používat Dynamic Event Loot, <code>0</code> = běžný režim bez DE loot flagu.</span></article>
                </div>
                <div class="dz-event-groups-list">
                    @forelse ($eventGroups as $groupIndex => $group)
                        <details class="dz-event-group" @if ($loop->first) open @endif>
                            <summary>
                                <span>
                                    <small>SKUPINA {{ $groupIndex + 1 }}</small>
                                    <strong>{{ $xmlValues[$group['name_path']] ?? $group['name'] }}</strong>
                                </span>
                                <span class="dz-badge dz-server-badge">{{ count($group['children']) }} objektů</span>
                            </summary>
                            <div class="dz-event-group-body">
                                <label class="dz-event-group-name">
                                    <span><strong>Název skupiny</strong><small>Identifikátor používaný eventem. Změňte jej pouze tehdy, když upravíte i všechny odkazy v souvisejících souborech.</small></span>
                                    <input type="text" wire:model.blur="xmlValues.{{ $group['name_path'] }}">
                                    <code>Raw: &lt;group name="…"&gt;</code>
                                </label>
                                <div class="dz-event-add-child">
                                    <span><strong>Přidat objekt do této skupiny</strong><small>Zadejte přesný DayZ classname. Nový objekt se vloží s nulovým posunem, natočením a loot limity; potom jej můžete upravit níže.</small></span>
                                    <input type="text" wire:model="newEventChildTypes.{{ $groupIndex }}" placeholder="Např. Land_Train_Wagon_Box_DE">
                                    <x-dz-confirm-button call="addEventGroupChild({{ $groupIndex }})" label="+ Přidat a vytvořit revizi" saved-label="Přidáno" />
                                    @error('newEventChildTypes.'.$groupIndex) <small class="dz-error">{{ $message }}</small> @enderror
                                </div>
                                <div class="dz-event-children">
                                    @foreach ($group['children'] as $childIndex => $child)
                                        <article class="dz-event-child">
                                            <header>
                                                <span><small>OBJEKT {{ $childIndex + 1 }}</small><strong>{{ $xmlValues[$child['type']['path']] ?? $child['type']['value'] }}</strong></span>
                                                <span class="dz-event-child-actions"><code>&lt;child … /&gt;</code><button type="button" x-on:click="dzConfirm('Opravdu odebrat tento objekt ze skupiny? Vytvoří se nová revize.').then((ok) => { if (ok) $wire.removeEventGroupChild({{ $groupIndex }}, {{ $childIndex }}); })">Odebrat</button></span>
                                            </header>
                                            <label class="wide">
                                                <span><strong>Třída objektu</strong><small>Přesný DayZ classname objektu. Neexistující třída způsobí, že se daná část eventu nevytvoří.</small></span>
                                                <input type="text" wire:model.blur="xmlValues.{{ $child['type']['path'] }}">
                                            </label>
                                            <div class="dz-event-position-grid">
                                                @foreach ([['x','X (m)','Posun v ose X od kotvy skupiny.'],['z','Z (m)','Posun v ose Z od kotvy skupiny.'],['y','Y (m)','Výškový posun od kotvy skupiny.'],['a','Natočení A (°)','Relativní natočení 0–360 stupňů.']] as [$key, $label, $help])
                                                    <label>
                                                        <span><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
                                                        <input type="number" step="0.001" @if ($key === 'a') min="0" max="360" @else min="-10000" max="10000" @endif wire:model.blur="xmlValues.{{ $child[$key]['path'] }}">
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="dz-event-loot-grid">
                                                <label>
                                                    <span><strong>Loot minimum</strong><small>Nejnižší počet aktivních loot pozic tohoto objektu, 0–1000.</small></span>
                                                    <input type="number" min="0" max="1000" step="1" wire:model.blur="xmlValues.{{ $child['lootmin']['path'] }}">
                                                </label>
                                                <label>
                                                    <span><strong>Loot maximum</strong><small>Nejvyšší počet aktivních loot pozic; musí být alespoň jako minimum.</small></span>
                                                    <input type="number" min="0" max="1000" step="1" wire:model.blur="xmlValues.{{ $child['lootmax']['path'] }}">
                                                </label>
                                                <label>
                                                    <span><strong>Dynamic Event Loot</strong><small>Zapněte jen pro objekty, které mají používat DE loot ekonomiku.</small></span>
                                                    <select wire:model="xmlValues.{{ $child['deloot']['path'] }}">
                                                        <option value="0">Vypnuto · deloot="0"</option>
                                                        <option value="1">Zapnuto · deloot="1"</option>
                                                    </select>
                                                </label>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="dz-empty-state"><strong>V souboru nebyla nalezena žádná skupina.</strong><span>Ověřte, že jde o platný cfgeventgroups.xml s elementy &lt;group&gt; a &lt;child&gt;.</span></div>
                    @endforelse
                </div>
                @error('xmlValues') <div class="dz-error">{{ $message }}</div> @enderror
                <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Např. upraveno pořadí a natočení vagónů"><x-dz-confirm-button call="saveEventGroups()" label="Validovat a uložit novou revizi" class="dz-action" /></div>
            </section>
        @elseif ($visualKind === 'map-file')
            <section class="dz-panel dz-map-file-panel">
                <div class="dz-panel-head">
                    <strong>{{ $currentFilename }} · mapový datový soubor</strong>
                    <p class="dz-muted text-sm mt-1">{{ $this->configurationDescription() }}</p>
                </div>
                <div class="dz-map-file-guide">
                    <div>
                        <strong>Proč zde není tabulka tisíců polí?</strong>
                        <p>Jde o generovaný mapový export s velkým množstvím souřadnic a prototypů. Vykreslení každého atributu jako samostatného formuláře by zablokovalo prohlížeč, zejména na mobilu.</p>
                    </div>
                    <div>
                        <strong>Jak soubor bezpečně upravit</strong>
                        <p>Pro souřadnice a body použijte Mapový editor. Přesný XML obsah zůstává dostupný v režimu Raw data a při uložení se vždy vytvoří nová revize.</p>
                    </div>
                    <a class="dz-action" href="{{ url('/admin/map-editor?project='.$this->getRecord()->id) }}">Otevřít mapový editor</a>
                    <button type="button" wire:click="$set('mode', 'raw')" class="dz-secondary">Zobrazit raw XML</button>
                </div>
            </section>
        @elseif ($visualKind === 'xml')
            <section class="dz-panel dz-server-settings">
                <div class="dz-panel-head"><strong>{{ $currentFilename }} · vizuální editor</strong><p class="dz-muted text-sm mt-1">Opakované záznamy jsou seskupené podle konkrétního eventu, typu nebo skupiny. U každého pole je uveden přesný raw XPath zápis.</p></div>
                <div class="p-3">
                    @foreach (collect($xmlFields)->groupBy(fn ($field) => $field['group'] ?? $field['section']) as $section => $fields)
                        <details class="dz-group" @if ($loop->first) open @endif><summary><span>{{ $section }}</span><span class="dz-badge dz-server-badge">{{ count($fields) }} parametrů</span></summary>
                            <div class="dz-fields">
                                @foreach ($fields as $field)
                                    <label class="dz-field" data-tooltip="Raw XML: {{ $field['raw'] }}">
                                        <span class="dz-field-top"><span><strong>{{ $field['label'] }}</strong><br><small class="dz-muted">{{ $this->xmlFieldDescription($field) }}</small><small class="dz-muted">Raw: {{ $field['raw'] }}</small></span>
                                            @if ($field['type'] === 'boolean')
                                                <select wire:model="xmlValues.{{ $field['path'] }}"><option value="false">false · vypnuto</option><option value="true">true · zapnuto</option></select>
                                            @else
                                                <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" wire:model="xmlValues.{{ $field['path'] }}">
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
                <div class="dz-savebar"><input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny XML konfigurace"><x-dz-confirm-button call="saveXml()" label="Validovat a uložit XML revizi" class="dz-action" /></div>
            </section>
            @endif
        @else
            <section class="dz-panel">
                <div class="dz-panel-head">
                    <div class="dz-raw-heading">
                        <div>
                            <strong>Raw data · {{ $currentFilename ?: 'Konfigurace serveru' }}</strong>
                            <p class="dz-muted text-sm mt-1">Přesný obsah aktuální revize bez převodu do formulářů. Změny se uloží až tlačítkem dole.</p>
                        </div>
                        <button type="button" class="dz-secondary dz-copy-raw" data-copy-target="dz-raw-content">Kopírovat do schránky</button>
                    </div>
                    <span class="dz-badge dz-server-badge mt-2">{{ strtoupper(pathinfo($currentFilename, PATHINFO_EXTENSION) ?: 'TEXT') }} · revize #{{ $revisionNumber }}</span>
                </div>
                <div class="p-4">
                    <textarea id="dz-raw-content" wire:model="rawContent" class="dz-raw" spellcheck="false" autocomplete="off" aria-label="Raw obsah souboru {{ $currentFilename }}"></textarea>
                    @error('rawContent') <div class="dz-error">{{ $message }}</div> @enderror
                </div>
                <div class="dz-savebar">
                    <input wire:model="changeSummary" class="dz-summary" placeholder="Popis změny">
                    <x-dz-confirm-button call="saveRaw()" label="Validovat a uložit revizi" class="dz-action" />
                </div>
            </section>
        @endif
    </div>
    @if ($showClassPicker)
        <div class="dz-modal-backdrop" wire:click.self="closeClassPicker">
            <section class="dz-modal" role="dialog" aria-modal="true" aria-label="Výběr položky">
                <div class="dz-modal-head">
                    <div><strong>Vyber položku z katalogu</strong><p class="dz-muted text-sm mt-1">Katalog obsahuje položky načtené z aktuálního types.xml.</p></div>
                    <button type="button" wire:click="closeClassPicker" class="dz-secondary">Zavřít</button>
                </div>
                <div class="dz-modal-body" wire:loading.class="opacity-60" wire:target="classPickerSearch, classPickerCategory, loadMoreCatalog, chooseClass">
                    <div wire:loading wire:target="classPickerSearch, classPickerCategory, loadMoreCatalog, chooseClass" class="dz-picker-loading">Načítám katalog…</div>
                    <input wire:model.live.debounce.200ms="classPickerSearch" class="dz-picker-search" placeholder="Hledat v celém katalogu, například PlateCarrier nebo AKM…">
                    <div class="dz-picker-categories">
                        @foreach ($this->pickerCategories() as $category)
                            <button type="button" wire:click="$set('classPickerCategory', @js($category))" class="dz-picker-category {{ $classPickerCategory === $category ? 'active' : '' }}">{{ strtoupper($category) }}</button>
                        @endforeach
                    </div>
                    <div class="dz-picker-items">
                        @forelse ($this->pickerEntries() as $entry)
                            <button type="button" wire:click="chooseClass(@js($entry['name']), @js($entry['category']))" class="dz-picker-item"><strong>{{ $entry['name'] }}</strong><br><small>{{ $entry['nominal'] }} ks · {{ $entry['category'] }}</small></button>
                        @empty
                            <p class="dz-muted">V této kategorii nejsou v aktuálním souboru žádné položky.</p>
                        @endforelse
                    </div>
                    @if (count($this->pickerEntries()) >= $classPickerLimit)
                        <button type="button" wire:click="loadMoreCatalog" class="dz-picker-more">Načíst dalších 60 položek</button>
                    @endif
                </div>
            </section>
        </div>
    @endif
    <script>
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-copy-target]');
            if (!button) return;
            const target = document.getElementById(button.dataset.copyTarget);
            if (!target) return;
            try {
                await navigator.clipboard.writeText(target.value);
            } catch (error) {
                target.select();
                document.execCommand('copy');
            }
            const original = button.textContent;
            button.textContent = 'Zkopírováno ✓';
            window.setTimeout(() => button.textContent = original, 1800);
        });
    </script>
</x-filament-panels::page>
