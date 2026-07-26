<link rel="icon" type="image/svg+xml" href="{{ secure_asset('favicon.svg') }}?v=3">
<link rel="shortcut icon" href="{{ secure_asset('favicon.svg') }}?v=3">
<style>
    :root {
        color-scheme: dark;
        --dayz-background: #090d0a;
        --dayz-surface: #121914;
        --dayz-surface-raised: #19221b;
        --dayz-border: rgba(190, 209, 175, 0.14);
        --dayz-accent: #b6e94f;
        --dayz-accent-strong: #91c52b;
        --dayz-warning: #d97738;
        --dayz-text: #edf2e9;
        --dayz-muted: #aab6a4;
    }

    ::selection {
        color: #17210d;
        background: var(--dayz-accent);
    }

    .fi-body {
        color: var(--dayz-text) !important;
        background-color: var(--dayz-background) !important;
        background-image:
            radial-gradient(circle at 82% -10%, rgba(145, 197, 43, 0.12), transparent 34rem),
            radial-gradient(circle at 15% 105%, rgba(217, 119, 56, 0.07), transparent 28rem),
            repeating-linear-gradient(115deg, transparent 0, transparent 80px, rgba(255, 255, 255, 0.012) 81px);
        background-attachment: fixed;
    }

    .fi-layout {
        background: transparent !important;
    }

    .fi-simple-layout {
        position: relative;
        overflow: hidden;
        background: transparent !important;
    }

    .fi-simple-layout::before {
        position: fixed;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(90deg, rgba(9, 13, 10, 0.95), rgba(9, 13, 10, 0.6)),
            repeating-linear-gradient(-45deg, transparent 0 14px, rgba(182, 233, 79, 0.018) 14px 15px);
        content: "";
    }

    .fi-simple-main {
        position: relative;
        border: 1px solid rgba(182, 233, 79, 0.2);
        border-top: 3px solid var(--dayz-accent-strong);
        border-radius: 0.35rem;
        background: rgba(18, 25, 20, 0.97) !important;
        box-shadow:
            0 28px 80px rgba(0, 0, 0, 0.52),
            0 0 0 1px rgba(255, 255, 255, 0.02) inset;
        backdrop-filter: blur(18px);
    }

    .fi-simple-main::after {
        position: absolute;
        top: -3px;
        right: 1.5rem;
        width: 3.5rem;
        height: 3px;
        background: var(--dayz-warning);
        content: "";
    }

    .fi-logo {
        color: var(--dayz-accent) !important;
        font-weight: 800;
        letter-spacing: -0.045em;
        text-transform: uppercase;
    }

    .fi-simple-header .fi-logo::before {
        display: inline-block;
        width: 0.55rem;
        height: 0.55rem;
        margin-right: 0.65rem;
        border-radius: 9999px;
        background: var(--dayz-accent);
        box-shadow: 0 0 18px rgba(163, 230, 53, 0.8);
        content: "";
        vertical-align: 0.08em;
    }

    .fi-sidebar {
        border-right: 1px solid var(--dayz-border);
        background:
            linear-gradient(180deg, rgba(145, 197, 43, 0.035), transparent 15rem),
            #0e1410 !important;
    }

    .fi-sidebar-header {
        border-bottom: 1px solid var(--dayz-border);
        background: #101712 !important;
        box-shadow: none !important;
    }

    .fi-topbar nav {
        border-bottom: 1px solid var(--dayz-border);
        background: rgba(12, 17, 13, 0.94) !important;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(14px);
    }

    .fi-main {
        position: relative;
    }

    .fi-main::before {
        display: block;
        width: 4rem;
        height: 3px;
        margin-bottom: 1rem;
        background: linear-gradient(90deg, var(--dayz-accent-strong) 75%, var(--dayz-warning) 75%);
        content: "";
    }

    .fi-header-heading,
    .fi-simple-header-heading,
    .fi-section-header-heading,
    .fi-ta-header-heading,
    .fi-wi-account .text-gray-950 {
        color: var(--dayz-text) !important;
    }

    .fi-header-subheading,
    .fi-simple-header-subheading,
    .fi-wi-account .text-gray-500 {
        color: var(--dayz-muted) !important;
    }

    .fi-sidebar-item.fi-active > a {
        border-left: 3px solid var(--dayz-accent);
        border-radius: 0.25rem;
        color: var(--dayz-accent) !important;
        background: rgba(145, 197, 43, 0.14) !important;
    }

    .fi-sidebar-item > a:hover {
        background: rgba(182, 233, 79, 0.07) !important;
    }

    .fi-sidebar-item-label {
        color: #c8d1c3;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active svg {
        color: var(--dayz-accent) !important;
    }

    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-wi-account > div {
        border-color: var(--dayz-border) !important;
        border-radius: 0.35rem !important;
        background:
            linear-gradient(135deg, rgba(182, 233, 79, 0.035), transparent 45%),
            var(--dayz-surface) !important;
        box-shadow:
            0 16px 45px rgba(0, 0, 0, 0.24),
            inset 3px 0 0 rgba(145, 197, 43, 0.45);
    }

    .fi-input-wrp {
        border-color: rgba(190, 209, 175, 0.2);
        border-radius: 0.3rem !important;
        background: rgba(7, 12, 8, 0.7) !important;
    }

    .fi-input-wrp:focus-within {
        border-color: var(--dayz-accent-strong);
        box-shadow: 0 0 0 1px var(--dayz-accent-strong);
    }

    .fi-btn.fi-color-primary {
        color: #17210d;
        border-radius: 0.25rem;
        font-weight: 700;
        letter-spacing: 0.025em;
        text-transform: uppercase;
        box-shadow: 0 8px 24px rgba(145, 197, 43, 0.2);
    }

    .fi-btn.fi-color-primary:hover {
        box-shadow: 0 10px 30px rgba(163, 230, 53, 0.28);
    }

    .fi-btn.fi-color-gray {
        border-radius: 0.25rem;
        color: var(--dayz-text) !important;
        background: var(--dayz-surface-raised) !important;
    }

    .fi-dropdown-panel,
    .fi-modal-window {
        border: 1px solid var(--dayz-border);
        border-radius: 0.35rem !important;
        background: #111813 !important;
    }

    .fi-ta-header,
    .fi-ta-content,
    .fi-ta-footer {
        border-color: var(--dayz-border) !important;
        background: transparent !important;
    }

    .fi-ta-ctn {
        overflow-x: auto;
    }

    .fi-ta-table {
        min-width: 42rem;
    }

    @media (max-width: 640px) {
        .fi-main {
            padding-inline: .75rem;
        }

        .fi-header {
            gap: .75rem;
        }

        .fi-header-heading {
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .fi-header-actions-ctn {
            width: 100%;
        }

        .fi-header-actions-ctn .fi-btn {
            flex: 1 1 auto;
        }

        .fi-wi-stats-overview {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .fi-wi-stats-overview-stat {
            min-width: 0;
        }
    }

    .dz-fields-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; width:100%; max-width:100%; }
    .dz-field { min-width:0; display:flex; flex-direction:column; gap:.4rem; padding:.85rem; border:1px solid #34472d; border-radius:.55rem; background:#111a14; }
    .dz-field span { color:#d8f57b; font-weight:700; overflow-wrap:anywhere; }
    .dz-field input { width:100%; min-width:0; box-sizing:border-box; background:#0b110d; color:#e7f7d2; border:1px solid #486a2c; border-radius:.4rem; padding:.6rem .7rem; }
    .dz-field input:focus { outline:2px solid #b8ed55; outline-offset:1px; }
    .dz-secret-control { display:flex; gap:.4rem; min-width:0; }
    .dz-secret-control input { flex:1 1 auto; min-width:0; }
    .dz-reveal { flex:0 0 auto; border:1px solid #486a2c; border-radius:.4rem; background:#18251a; color:#d8f57b; padding:.45rem .6rem; cursor:pointer; }
    .dz-field small { color:#9aa99b; font:.75rem/1.35 ui-monospace,SFMono-Regular,monospace; overflow-wrap:anywhere; }
    .dz-save-button { margin-top:1rem; border:0; border-radius:.45rem; background:#b8ed55; color:#14200f; padding:.75rem 1rem; font-weight:800; cursor:pointer; }
    .dz-whitelist-add { display:flex; gap:.75rem; flex-wrap:wrap; margin:1rem 0; }
    .dz-whitelist-add input { flex:1 1 18rem; min-width:0; background:#0b110d; color:#e7f7d2; border:1px solid #486a2c; border-radius:.4rem; padding:.65rem .75rem; }
    .dz-whitelist-list { display:grid; gap:.5rem; margin-bottom:1rem; }
    .dz-whitelist-row { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.7rem .85rem; border:1px solid #34472d; border-radius:.45rem; background:#111a14; }
    .dz-whitelist-row code { color:#d8f57b; overflow-wrap:anywhere; }
    .dz-danger { border:1px solid #8b4b43; border-radius:.35rem; background:#281614; color:#ffb3a8; padding:.4rem .6rem; cursor:pointer; }
    .dz-map-toolbar { display:grid; grid-template-columns:minmax(260px,1fr) minmax(220px,280px) minmax(260px,auto); gap:1rem; align-items:end; margin-bottom:1rem; }
    .dz-map-toolbar h2 { margin:.15rem 0; color:#d8f57b; font-size:1.5rem; }
    .dz-eyebrow { color:#a9df42; font-size:.7rem; letter-spacing:.16em; font-weight:700; margin:0; }
    .dz-muted { color:#9aa99b; font-size:.9rem; }
    .dz-map-select { appearance:none; -webkit-appearance:none; background:#111a14; border:1px solid #506d2b; border-radius:.5rem; color:#e7f7d2; padding:.7rem 2.2rem .7rem 1rem; background-image:linear-gradient(45deg,transparent 50%,#b8ed55 50%),linear-gradient(135deg,#b8ed55 50%,transparent 50%); background-position:calc(100% - 14px) 50%,calc(100% - 9px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
    .dz-map-server-picker { display:grid; gap:.35rem; color:#d8f57b; font-size:.78rem; font-weight:800; }
    .dz-map-upload { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
    .dz-map-upload input { max-width:180px; color:#b8c8b4; font-size:.75rem; }
    .dz-file-button { display:inline-flex; align-items:center; cursor:pointer; background:#273725; color:#e7f7d2; border:1px solid #5b7f35; border-radius:.45rem; padding:.65rem .8rem; font-weight:700; }
    .dz-file-button input { display:none; }
    .dz-map-upload-button { display:inline-flex; align-items:center; background:#b8ed55; color:#14200f; border:0; border-radius:.45rem; padding:.65rem .8rem; font-weight:700; text-decoration:none; }
    .dz-map-upload small { width:100%; color:#879987; }
    .dz-map-info { margin:0 0 1rem; padding:.8rem 1rem; border-left:3px solid #80b8ff; background:rgba(128,184,255,.08); color:#c9d7c5; font-size:.82rem; line-height:1.5; }
    .dz-map-info strong { color:#e7f7d2; } .dz-map-info code { color:#b8ed55; }
    .dz-map-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(300px,360px); gap:1rem; align-items:start; }
    .dz-map-canvas { height:min(72vh,760px); min-height:560px; position:relative; overflow:hidden; border:1px solid #3e5c28; border-radius:.7rem; background:#1c2b20; }
    #dayz-leaflet-map { width:100%; height:100%; background:#1c2b20; }
    .leaflet-container { background:#1c2b20; font-family:inherit; }
    .dz-coordinate-control { background:#111a14e8; border:1px solid #6c9337; border-radius:.35rem; color:#d8f57b; padding:.4rem .6rem; font:700 .78rem/1.2 ui-monospace,monospace; }
    .dz-grid-label { color:#f0f8e8; background:#0b120dc9; border-radius:.2rem; padding:1px 3px; font:700 9px/1.2 ui-monospace,monospace; white-space:nowrap; }
    .dz-wizard-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
    .dz-import-page { max-width:820px; } .dz-import-page h2 { color:#d8f57b; margin:.2rem 0; } .dz-import-form { display:grid; gap:1rem; margin-top:1.5rem; padding:1.25rem; border:1px solid #3e5c28; border-radius:.7rem; background:#111a14; } .dz-import-form label { display:grid; gap:.4rem; color:#d8f57b; font-weight:700; } .dz-import-form label span { display:flex; align-items:center; gap:.5rem; } .dz-import-form label span b { display:grid; place-items:center; width:1.45rem; height:1.45rem; border-radius:50%; background:#b8ed55; color:#14200f; font-size:.72rem; } .dz-import-form label small { color:#9aa99b; font-weight:400; } .dz-import-form select,.dz-import-form input[type=file] { width:100%; min-height:2.8rem; padding:.7rem; border:1px solid #486a2c; border-radius:.45rem; background:#0b110d; color:#e7f7d2; } .dz-import-submit { border:0; border-radius:.45rem; background:#b8ed55; color:#14200f; padding:.9rem 1rem; font-weight:800; cursor:pointer; }
    .dz-wizard-intro { grid-column:1/-1; padding:1rem 0; } .dz-wizard-intro h2 { color:#d8f57b; margin:.2rem 0; }
    .dz-steps { display:flex; flex-wrap:wrap; gap:.55rem; margin:1rem 0 0; padding:0; list-style:none; } .dz-steps li { display:flex; align-items:center; gap:.4rem; padding:.4rem .65rem; border:1px solid #34472d; border-radius:999px; color:#c5d2bf; font-size:.75rem; } .dz-steps b,.dz-card-number { display:grid; place-items:center; width:1.3rem; height:1.3rem; border-radius:50%; background:#b8ed55; color:#14200f; font-size:.65rem; }
    .dz-wizard-card { position:relative; display:flex; flex-direction:column; gap:.45rem; min-height:168px; padding:1.1rem; border:1px solid #3e5c28; border-radius:.7rem; background:#111a14; color:#e7f7d2; text-decoration:none; }
    .dz-card-number { position:absolute; top:.8rem; right:.8rem; }
    .dz-wizard-card:hover { border-color:#b8ed55; transform:translateY(-2px); } .dz-wizard-card strong { color:#d8f57b; font-size:1.05rem; } .dz-wizard-card span { color:#9aa99b; font-size:.8rem; } .dz-wizard-card small { color:#c4d6b8; font-size:.76rem; line-height:1.35; } .dz-wizard-card em { margin-top:auto; color:#b8ed55; font-style:normal; font-weight:700; font-size:.8rem; } .dz-wizard-icon { width:1.5rem; color:#b8ed55; }
    @media (max-width:760px) { .dz-wizard-grid { grid-template-columns:1fr; } }
    .dz-map-grid { position:absolute; inset:0; opacity:.12; background-image:linear-gradient(#d8f57b 1px,transparent 1px),linear-gradient(90deg,#d8f57b 1px,transparent 1px); background-size:48px 48px; }
    .dz-map-land { display:none; }
    .dz-map-marker { position:absolute; transform:translate(-50%,-50%); border:0; background:#172218dd; color:#f0f7e8; border-radius:999px; padding:.35rem .55rem; font-size:.72rem; display:flex; gap:.35rem; align-items:center; cursor:pointer; }
    .dz-map-marker span { width:.55rem; height:.55rem; border-radius:50%; background:#b8ed55; box-shadow:0 0 0 4px #b8ed5533; }
    .dz-map-marker-heli span { background:#f1b44c; } .dz-map-marker-convoy span { background:#e96a5f; } .dz-map-marker-event span { background:#80b8ff; }
    .dz-map-empty { position:absolute; inset:45% 10% auto; text-align:center; color:#9aa99b; }
    .dz-point-modal { position:fixed; inset:0; z-index:2000; display:grid; place-items:center; background:#050906bb; padding:1rem; }
    .dz-point-modal[hidden] { display:none; }
    .dz-point-modal-card { position:relative; width:min(620px,100%); border:1px solid #668f32; border-radius:.8rem; background:#111a14; padding:1.4rem; box-shadow:0 20px 70px #000b; }
    .dz-point-modal-card h3 { color:#d8f57b; margin:0; font-size:1.2rem; }
    .dz-point-close { position:absolute; top:.7rem; right:.8rem; border:0; background:transparent; color:#c4d3bd; font-size:1.6rem; cursor:pointer; }
    .dz-point-options { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; margin-top:1rem; }
    .dz-point-options button { min-height:3rem; text-align:left; border:1px solid #3e5c28; border-radius:.5rem; background:#18251a; color:#e7f7d2; padding:.65rem; cursor:pointer; }
    .dz-point-options button:hover { border-color:#b8ed55; background:#263b20; color:#d8f57b; }
    .dz-point-options button.selected { border-color:#b8ed55; background:#b8ed55; color:#0d160d; box-shadow:0 0 0 2px rgba(184,237,85,.25); }
    .dz-event-catalog { margin-top:1rem; display:grid; gap:.55rem; }
    .dz-event-catalog[hidden] { display:none; }
    .dz-event-catalog label { color:#d8f57b; font-weight:700; }
    .dz-event-catalog select, .dz-event-catalog input { width:100%; min-height:2.8rem; border:1px solid #496b31; border-radius:.45rem; background:#0b120d; color:#e7f7d2; padding:.65rem .75rem; color-scheme:dark; }
    .dz-event-catalog select:focus, .dz-event-catalog input:focus { outline:2px solid #b8ed55; outline-offset:1px; }
    .dz-event-catalog select option { background:#111a14; color:#e7f7d2; }
    .dz-event-catalog small { color:#aab6a4; line-height:1.35; }
    .dz-point-target-status { display:grid; gap:.25rem; padding:.8rem .9rem; border:1px solid; border-radius:.5rem; font-size:.88rem; }
    .dz-point-target-status strong, .dz-point-target-status span, .dz-point-target-status a { display:block; }
    .dz-point-target-status.ready { color:#dfffb1; border-color:#4f762d; background:#172512; }
    .dz-point-target-status.missing { color:#ffd5af; border-color:#a55f2c; background:#2a180f; }
    .dz-point-target-status a { width:max-content; margin-top:.2rem; color:#b8ed55; font-weight:800; text-decoration:underline; }
    .dz-point-target-status code { color:inherit; }
    .dz-map-feedback { margin:.25rem 0; padding:.7rem .85rem; border:1px solid rgba(184,237,85,.35); border-radius:.65rem; background:rgba(184,237,85,.08); color:#dff7ad; font-size:.85rem; line-height:1.45; }
    .dz-map-feedback.error { border-color:rgba(248,113,113,.5); background:rgba(127,29,29,.22); color:#fecaca; }
    .dz-point-confirm { min-height:2.8rem; border:1px solid #9bd447; border-radius:.45rem; background:#b8ed55; color:#0d160d; font-weight:800; cursor:pointer; padding:.65rem 1rem; }
    .dz-point-confirm:disabled { cursor:not-allowed; filter:saturate(.25); opacity:.55; box-shadow:none; }
    .dz-edit-coordinate-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin:1rem 0; } .dz-edit-coordinate-grid label { display:grid; gap:.35rem; color:#d8f57b; font-weight:700; } .dz-edit-coordinate-grid input { width:100%; min-height:2.8rem; border:1px solid #496b31; border-radius:.45rem; background:#0b120d; color:#e7f7d2; padding:.65rem .75rem; } .dz-edit-actions { display:flex; justify-content:space-between; gap:.75rem; margin-top:1rem; } .dz-edit-actions button { flex:1; }
    @media (max-width:800px) { .dz-fields-grid { grid-template-columns:1fr; } }
    .dz-map-legend { position:sticky; top:5.5rem; max-height:calc(100vh - 7rem); overflow:auto; border:1px solid #34472d; border-radius:.7rem; background:#111a14; padding:1rem; }
    .dz-map-legend h3 { color:#d8f57b; margin-top:0; } .dz-map-legend label { display:block; padding:.55rem 0; color:#c5d2bf; }
    .dz-map-source-list { display:grid; gap:.35rem; margin-top:.75rem; color:#aab6a4; font-size:.78rem; line-height:1.35; }
    .dz-map-source-list strong { color:#d8f57b; font-size:.85rem; }
    .dz-map-source-list code { color:#dce8d5; }
    .dz-map-source-list small { margin-top:.35rem; color:#879987; }
    .dz-map-source { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; padding:.45rem .5rem; border:1px solid rgba(190,209,175,.14); border-radius:.35rem; color:#cbd5c0; text-decoration:none; }
    .dz-map-source:hover { border-color:#b8ed55; background:rgba(182,233,79,.08); }
    .dz-map-source span { min-width:0; display:grid; gap:.15rem; }
    .dz-map-source small { color:#879987; }
    .dz-map-source b { flex:0 0 auto; max-width:7rem; color:#b8ed55; font-size:.68rem; text-align:right; overflow-wrap:anywhere; }
    .dz-map-source.missing { border-color:rgba(217,119,56,.35); }
    .dz-map-source.missing b { color:#ffc08f; }
    .dz-map-legend input { accent-color:#b8ed55; margin-right:.5rem; }
    .dz-map-layers label { display:flex; align-items:flex-start; gap:.35rem; padding:.4rem 0; color:#c5d2bf; font-size:.75rem; } .dz-map-layers label span { display:grid; gap:.05rem; overflow-wrap:anywhere; } .dz-map-layers label small { color:#7f9182; font-size:.65rem; }
    .dz-load-dense { display:flex; align-items:flex-start; gap:.45rem; margin:.35rem 0; padding:.55rem; border:1px dashed #506d2b; border-radius:.4rem; color:#d8f57b; text-decoration:none; font-size:.75rem; } .dz-load-dense span { display:grid; gap:.05rem; } .dz-load-dense small { color:#879987; }
    .dz-layer-dot { width:.65rem; height:.65rem; border-radius:50%; display:inline-block; }
    .layer-0 { background:#b8ed55; } .layer-1 { background:#80b8ff; } .layer-2 { background:#f1b44c; } .layer-3 { background:#e96a5f; } .layer-4 { background:#d58cff; } .layer-5 { background:#55e0c1; } .layer-6 { background:#ff82b2; } .layer-7 { background:#f6d365; }
    .dz-raw-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; } .dz-copy-raw { white-space:nowrap; }
    @media (max-width: 980px) { .dz-map-toolbar { grid-template-columns:1fr 1fr; } .dz-map-toolbar > div:first-child { grid-column:1/-1; } .dz-map-layout { grid-template-columns:1fr; } .dz-map-legend { position:static; max-height:none; } }
    @media (max-width: 760px) { .dz-map-toolbar { grid-template-columns:1fr; } .dz-map-toolbar > div:first-child { grid-column:auto; } .dz-map-canvas { min-height:420px; height:65vh; } .dz-map-land { font-size:1.2rem; } .dz-map-marker { font-size:.62rem; } .dz-point-options { grid-template-columns:1fr 1fr; } .dz-edit-coordinate-grid { grid-template-columns:1fr; } .dz-edit-actions { flex-direction:column-reverse; } .dz-raw-heading { flex-direction:column; } .dz-copy-raw { width:100%; } }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!new URLSearchParams(window.location.search).has('open')) return;
        const openImport = () => {
            const button = [...document.querySelectorAll('button')].find((item) => item.textContent.includes('Nahrát konfiguraci'));
            if (button) { button.click(); return true; }
            return false;
        };
        if (!openImport()) {
            const timer = window.setInterval(() => { if (openImport()) window.clearInterval(timer); }, 250);
            window.setTimeout(() => window.clearInterval(timer), 10000);
        }
    });
</script>
