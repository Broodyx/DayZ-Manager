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

    /* User menu now lives at the bottom of the sidebar, so the topbar is just
       dead space on wide screens. Desktop hides it; mobile keeps it for the
       sidebar-open button, since there's no other trigger for that. */
    @media (min-width: 1024px) {
        .fi-topbar {
            display: none;
        }
    }

    .dz-sidebar-user {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.85rem 1rem;
        border-top: 1px solid var(--dayz-border);
        background: #0e1410;
    }

    .dz-sidebar-user-name {
        overflow: hidden;
        color: #c8d1c3;
        font-size: 0.78rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
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
    .dz-map-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.65rem; margin-bottom:.85rem; }
    .dz-eyebrow { color:#a9df42; font-size:.7rem; letter-spacing:.16em; font-weight:700; margin:0; }
    .dz-muted { color:#9aa99b; font-size:.9rem; }
    .dz-map-select { appearance:none; -webkit-appearance:none; background:#111a14; border:1px solid #506d2b; border-radius:.5rem; color:#e7f7d2; padding:.6rem 2.2rem .6rem 1rem; background-image:linear-gradient(45deg,transparent 50%,#b8ed55 50%),linear-gradient(135deg,#b8ed55 50%,transparent 50%); background-position:calc(100% - 14px) 50%,calc(100% - 9px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
    .dz-map-server-picker { display:flex; align-items:center; gap:.5rem; color:#d8f57b; font-size:.78rem; font-weight:800; }
    .dz-file-button { display:inline-flex; align-items:center; cursor:pointer; background:#273725; color:#e7f7d2; border:1px solid #5b7f35; border-radius:.45rem; padding:.6rem .8rem; font-weight:700; }
    .dz-file-button input { display:none; }
    .dz-map-upload-button { display:inline-flex; align-items:center; background:#b8ed55; color:#14200f; border:0; border-radius:.45rem; padding:.6rem .8rem; font-weight:700; text-decoration:none; }
    .dz-map-help { margin-left:auto; font-size:.78rem; }
    .dz-map-help summary { display:inline-flex; align-items:center; gap:.3rem; cursor:pointer; color:#9fc4ff; list-style:none; font-weight:700; }
    .dz-map-help summary::-webkit-details-marker { display:none; }
    .dz-map-help summary::before { content:'ⓘ'; }
    .dz-map-help[open] summary { margin-bottom:.5rem; }
    .dz-map-help > div { max-width:28rem; padding:.75rem .9rem; border-left:3px solid #80b8ff; background:rgba(128,184,255,.08); color:#c9d7c5; font-size:.8rem; line-height:1.5; border-radius:0 .3rem .3rem 0; }
    .dz-map-help > div code { color:#b8ed55; }
    @media (max-width:760px) { .dz-map-help { margin-left:0; width:100%; } .dz-map-help > div { max-width:none; } }
    .dz-map-alert { margin:0 0 1rem; padding:.85rem 1rem; border-left:3px solid #d97738; background:rgba(217,119,56,.09); color:#e8c8b3; font-size:.85rem; line-height:1.55; }
    .dz-map-alert strong { color:#f3d9c4; }
    .dz-map-alert ul { margin:.5rem 0 0; padding-left:1.1rem; display:grid; gap:.4rem; }
    .dz-map-alert li::marker { color:#d97738; }
    .dz-map-alert-row { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; }
    .dz-map-alert-actions { display:flex; flex-wrap:wrap; gap:.5rem; }
    .dz-add-event-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; margin:1rem 0; }
    .dz-add-event-grid label { display:grid; gap:.35rem; min-width:0; color:#d8f57b; font-weight:700; font-size:.82rem; }
    .dz-add-event-grid label small { color:#aab6a4; font-weight:400; line-height:1.4; }
    .dz-add-event-grid input, .dz-add-event-grid select { width:100%; min-height:2.7rem; border:1px solid #496b31; border-radius:.45rem; background:#0b120d; color:#e7f7d2; padding:.6rem .7rem; color-scheme:dark; }
    .dz-add-event-grid select { appearance:none; -webkit-appearance:none; padding-right:2.2rem; cursor:pointer; background-image:linear-gradient(45deg,transparent 50%,#b8ed55 50%),linear-gradient(135deg,#b8ed55 50%,transparent 50%); background-position:calc(100% - 14px) 50%,calc(100% - 9px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
    .dz-add-event-span2 { grid-column:span 3; }
    @media (max-width:760px) { .dz-add-event-grid { grid-template-columns:1fr; } .dz-add-event-span2 { grid-column:1; } }
    .dz-map-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(300px,360px); gap:1rem; align-items:start; }
    .dz-map-canvas { height:min(72vh,760px); min-height:560px; position:relative; overflow:hidden; border:1px solid #3e5c28; border-radius:.7rem; background:#1c2b20; }
    #dayz-leaflet-map { width:100%; height:100%; background:#1c2b20; }
    .leaflet-container { background:#1c2b20; font-family:inherit; }
    .dz-coordinate-control { background:#111a14e8; border:1px solid #6c9337; border-radius:.35rem; color:#d8f57b; padding:.4rem .6rem; font:700 .78rem/1.2 ui-monospace,monospace; }
    .dz-grid-label { color:#f0f8e8; background:#0b120dc9; border-radius:.2rem; padding:1px 3px; font:700 9px/1.2 ui-monospace,monospace; white-space:nowrap; }
    .dz-coordinate-axes { position:absolute; inset:0; z-index:650; overflow:hidden; border:1px solid #d8f57b78; border-radius:inherit; pointer-events:none; }
    .dz-coordinate-axis { position:absolute; color:#d8f57b; font:800 9px/1 ui-monospace,monospace; text-shadow:0 1px 2px #000; }
    .dz-coordinate-axis span { position:absolute; display:block; padding:4px 5px; border:1px solid #668f328f; color:#e5f6cb; background:#071009e8; white-space:nowrap; }
    .dz-coordinate-axis-x { top:0; right:0; left:0; height:22px; border-bottom:1px solid #d8f57b55; background:linear-gradient(#071009dc,#0710099c); }
    .dz-coordinate-axis-x span { top:0; transform:translateX(-50%); border-top:0; border-radius:0 0 .25rem .25rem; }
    .dz-coordinate-axis-z { top:0; bottom:0; left:0; width:58px; border-right:1px solid #d8f57b55; background:linear-gradient(90deg,#071009dc,#0710099c); }
    .dz-coordinate-axis-z span { left:0; transform:translateY(-50%); border-left:0; border-radius:0 .25rem .25rem 0; }
    .dz-wizard-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
    .dz-import-page { max-width:820px; } .dz-import-page h2 { color:#d8f57b; margin:.2rem 0; } .dz-import-form { display:grid; gap:1rem; margin-top:1.5rem; padding:1.25rem; border:1px solid #3e5c28; border-radius:.7rem; background:#111a14; } .dz-import-form label { display:grid; gap:.4rem; color:#d8f57b; font-weight:700; } .dz-import-form label span { display:flex; align-items:center; gap:.5rem; } .dz-import-form label span b { display:grid; place-items:center; width:1.45rem; height:1.45rem; border-radius:50%; background:#b8ed55; color:#14200f; font-size:.72rem; } .dz-import-form label small { color:#9aa99b; font-weight:400; } .dz-import-form select,.dz-import-form input[type=file] { width:100%; min-height:2.8rem; padding:.7rem; border:1px solid #486a2c; border-radius:.45rem; background:#0b110d; color:#e7f7d2; } .dz-import-submit { border:0; border-radius:.45rem; background:#b8ed55; color:#14200f; padding:.9rem 1rem; font-weight:800; cursor:pointer; }
    .dz-wizard-intro { grid-column:1/-1; padding:1rem 0; } .dz-wizard-intro h2 { color:#d8f57b; margin:.2rem 0; }
    .dz-steps { display:flex; flex-wrap:wrap; gap:.55rem; margin:1rem 0 0; padding:0; list-style:none; } .dz-steps li { display:flex; align-items:center; gap:.4rem; padding:.4rem .65rem; border:1px solid #34472d; border-radius:999px; color:#c5d2bf; font-size:.75rem; } .dz-steps b,.dz-card-number { display:grid; place-items:center; width:1.3rem; height:1.3rem; border-radius:50%; background:#b8ed55; color:#14200f; font-size:.65rem; }
    .dz-wizard-card { position:relative; display:flex; flex-direction:column; gap:.45rem; min-height:168px; padding:1.1rem; border:1px solid #3e5c28; border-radius:.7rem; background:#111a14; color:#e7f7d2; text-decoration:none; }
    .dz-card-number { position:absolute; top:.8rem; right:.8rem; }
    .dz-wizard-card:hover { border-color:#b8ed55; transform:translateY(-2px); } .dz-wizard-card strong { color:#d8f57b; font-size:1.05rem; } .dz-wizard-card span { color:#9aa99b; font-size:.8rem; } .dz-wizard-card small { color:#c4d6b8; font-size:.76rem; line-height:1.35; } .dz-wizard-card em { margin-top:auto; color:#b8ed55; font-style:normal; font-weight:700; font-size:.8rem; } .dz-wizard-icon { width:1.5rem; color:#b8ed55; }
    .dz-wizard-server-picker { display:grid; gap:.35rem; width:min(520px,100%); margin:1rem 0; color:#d8f57b; font-weight:800; }
    .dz-wizard-server-picker select { min-height:3rem; border:1px solid #496b31; border-radius:.55rem; background:#0b120d; color:#e7f7d2; padding:.65rem .8rem; color-scheme:dark; }
    .dz-wizard-card.has-configuration { border-color:#5d8733; background:linear-gradient(145deg,#152116,#101713); }
    .dz-wizard-card .dz-wizard-file-state { margin-top:.25rem; padding:.45rem .55rem; border-radius:.35rem; background:#1c2c17; color:#cfff8c; font-weight:700; }
    .dz-wizard-card .dz-wizard-file-state.missing { background:#291a12; color:#ffc28f; }
    .dz-wizard-empty { margin:1rem 0; padding:.8rem 1rem; border:1px solid #995c2f; border-radius:.55rem; background:#291a12; color:#ffd2ad; }
    @media (max-width:760px) { .dz-wizard-grid { grid-template-columns:1fr; } }
    .dz-map-grid { position:absolute; inset:0; opacity:.12; background-image:linear-gradient(#d8f57b 1px,transparent 1px),linear-gradient(90deg,#d8f57b 1px,transparent 1px); background-size:48px 48px; }
    .dz-map-land { display:none; }
    .dz-map-marker { position:absolute; transform:translate(-50%,-50%); border:0; background:#172218dd; color:#f0f7e8; border-radius:999px; padding:.35rem .55rem; font-size:.72rem; display:flex; gap:.35rem; align-items:center; cursor:pointer; }
    .dz-map-marker span { width:.55rem; height:.55rem; border-radius:50%; background:#b8ed55; box-shadow:0 0 0 4px #b8ed5533; }
    .dz-map-marker-heli span { background:#f1b44c; } .dz-map-marker-convoy span { background:#e96a5f; } .dz-map-marker-event span { background:#80b8ff; }
    .dz-map-empty { position:absolute; inset:45% 10% auto; text-align:center; color:#9aa99b; }
    .dz-point-modal { position:fixed; inset:0; z-index:2000; display:grid; place-items:center; background:#050906bb; padding:1rem; }
    .dz-point-modal[hidden] { display:none; }
    #dz-system-dialog { z-index:2100; }
    .dz-point-modal-card { position:relative; width:min(620px,100%); max-height:calc(100dvh - 2rem); overflow-y:auto; overscroll-behavior:contain; border:1px solid #668f32; border-radius:.8rem; background:#111a14; padding:1.4rem; box-shadow:0 20px 70px #000b; scrollbar-color:#668f32 #111a14; }
    #dz-point-modal .dz-point-modal-card { width:min(1120px,calc(100vw - 2rem)); }
    #dz-edit-point-modal .dz-point-modal-card { width:min(1120px,calc(100vw - 2rem)); }
    .dz-point-modal-card h3 { color:#d8f57b; margin:0; font-size:1.2rem; }
    .dz-system-dialog-card { width:min(520px,100%); overflow:hidden; }
    .dz-system-dialog-message { margin:.7rem 0 1.1rem; color:#bac7b6; line-height:1.55; }
    .dz-system-dialog-actions { display:flex; justify-content:flex-end; gap:.6rem; }
    .dz-system-dialog-actions button { min-height:2.65rem; padding:.6rem .9rem; border-radius:.45rem; font-size:.72rem; font-weight:850; cursor:pointer; }
    .dz-dialog-cancel { border:1px solid #435247; color:#d6e0d2; background:#111914; }
    .dz-dialog-cancel:hover { border-color:#819080; background:#1a241c; }
    .dz-dialog-confirm { border:1px solid var(--dz-lime); color:#10160d; background:var(--dz-lime); }
    .dz-dialog-confirm:hover { background:#ccf66f; }.dz-dialog-confirm:focus { outline:2px solid #fff; outline-offset:2px; }
    .dz-dialog-confirm.danger { border-color:#ef776d; color:#fff1ef; background:#9e3932; }
    .dz-dialog-confirm.danger:hover { background:#b9473e; }
    .dz-point-close { position:absolute; top:.7rem; right:.8rem; border:0; background:transparent; color:#c4d3bd; font-size:1.6rem; cursor:pointer; }
    .dz-point-options { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.55rem; margin-top:1rem; }
    .dz-point-options button { min-height:3rem; text-align:left; border:1px solid #3e5c28; border-radius:.5rem; background:#18251a; color:#e7f7d2; padding:.65rem; cursor:pointer; }
    .dz-point-options button:hover { border-color:#b8ed55; background:#263b20; color:#d8f57b; }
    .dz-point-options button.selected { border-color:#b8ed55; background:#b8ed55; color:#0d160d; box-shadow:0 0 0 2px rgba(184,237,85,.25); }
    .dz-event-catalog { margin-top:1rem; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem .8rem; }
    .dz-event-catalog[hidden] { display:none; }
    .dz-event-catalog label { color:#d8f57b; font-weight:700; }
    .dz-event-catalog select, .dz-event-catalog input { width:100%; min-height:2.8rem; border:1px solid #496b31; border-radius:.45rem; background:#0b120d; color:#e7f7d2; padding:.65rem .75rem; color-scheme:dark; }
    .dz-event-catalog select { appearance:none; -webkit-appearance:none; padding-right:2.2rem; cursor:pointer; background-image:linear-gradient(45deg,transparent 50%,#b8ed55 50%),linear-gradient(135deg,#b8ed55 50%,transparent 50%); background-position:calc(100% - 14px) 50%,calc(100% - 9px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
    .dz-event-catalog select:focus, .dz-event-catalog input:focus { outline:2px solid #b8ed55; outline-offset:1px; }
    .dz-event-catalog select option { background:#111a14; color:#e7f7d2; }
    .dz-event-catalog small { color:#aab6a4; line-height:1.35; }
    .dz-event-catalog > label:first-child,
    .dz-event-catalog > .dz-point-fields,
    .dz-event-catalog > .dz-point-help,
    .dz-event-catalog > .dz-event-settings,
    .dz-event-catalog > .dz-related-files,
    .dz-event-catalog > .dz-point-target-status,
    .dz-event-catalog > .dz-map-feedback,
    .dz-event-catalog > .dz-point-confirm { grid-column:1 / -1; }
    .dz-point-fields { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
    .dz-point-fields:empty { display:none; }
    .dz-point-fields label { display:grid; gap:.35rem; min-width:0; }
    .dz-point-fields label small { font-weight:400; }
    .dz-point-field-section { grid-column:1/-1; min-width:0; margin:0; padding:.9rem; border:1px solid #385326; border-radius:.6rem; background:#0e1710; }
    .dz-point-field-section legend { padding:0 .45rem; color:#b8ed55; font-size:.82rem; font-weight:900; letter-spacing:.035em; text-transform:uppercase; }
    .dz-point-field-section-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
    .dz-edit-point-fields { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; margin:1rem 0; }
    .dz-edit-point-fields:empty { display:none; }
    .dz-edit-point-fields label { display:grid; gap:.35rem; min-width:0; color:#d8f57b; font-weight:700; }
    .dz-edit-point-fields label small { color:#aab6a4; font-weight:400; line-height:1.4; }
    .dz-edit-point-fields input, .dz-edit-point-fields select { width:100%; min-height:2.8rem; border:1px solid #496b31; border-radius:.45rem; background:#0b120d; color:#e7f7d2; padding:.65rem .75rem; color-scheme:dark; }
    .dz-edit-point-fields select { appearance:none; -webkit-appearance:none; padding-right:2.2rem; cursor:pointer; background-image:linear-gradient(45deg,transparent 50%,#b8ed55 50%),linear-gradient(135deg,#b8ed55 50%,transparent 50%); background-position:calc(100% - 14px) 50%,calc(100% - 9px) 50%; background-size:5px 5px,5px 5px; background-repeat:no-repeat; }
    .dz-edit-point-fields :disabled { opacity:.7; cursor:not-allowed; }
    .dz-event-settings, .dz-related-files { border:1px solid #385326; border-radius:.55rem; background:#111a13; padding:.9rem; }
    .dz-event-settings > p, .dz-related-files > small { display:block; margin:.35rem 0 .75rem; color:#aab6a4; }
    .dz-event-setting-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.45rem; }
    .dz-event-setting-grid span { display:grid; gap:.12rem; padding:.45rem .55rem; border:1px solid #263b20; border-radius:.35rem; }
    .dz-event-setting-grid b { color:#d8f57b; }
    .dz-event-settings details { margin-top:.75rem; }
    .dz-event-settings ul, .dz-related-files ul { margin:.55rem 0 0; padding-left:1.25rem; }
    .dz-related-files li + li { margin-top:.3rem; }
    .dz-event-spawn-guide { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.65rem; padding:1rem; border-bottom:1px solid rgba(133,218,222,.2); background:rgba(6,45,49,.25); }
    .dz-event-spawn-guide article { display:grid; gap:.35rem; padding:.8rem; border:1px solid rgba(133,218,222,.18); border-radius:.5rem; background:#0d1712; }
    .dz-event-spawn-guide b { color:#b8ed55; font-size:.74rem; letter-spacing:.05em; }
    .dz-event-spawn-guide span { color:#aab6a4; font-size:.78rem; line-height:1.45; }
    .dz-event-spawn-list { display:grid; gap:.65rem; padding:1rem; }
    .dz-event-spawn { overflow:hidden; border:1px solid #304d2d; border-radius:.6rem; background:#0c1510; }
    .dz-event-spawn > summary { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem 1rem; cursor:pointer; list-style:none; }
    .dz-event-spawn > summary span:first-child { display:grid; gap:.15rem; }
    .dz-event-spawn > summary small { color:#708073; font-size:.66rem; letter-spacing:.08em; }
    .dz-event-spawn > summary strong { color:#edf7e5; }
    .dz-event-spawn-body { display:grid; gap:.8rem; padding:0 1rem 1rem; border-top:1px solid rgba(190,209,175,.1); }
    .dz-event-name-field { display:grid; grid-template-columns:minmax(240px,1fr) minmax(260px,.7fr); gap:1rem; align-items:center; padding:.8rem; border:1px solid #263b20; border-radius:.5rem; margin-top:.8rem; }
    .dz-event-name-field span { display:grid; gap:.2rem; }.dz-event-name-field small { color:#95a493; line-height:1.4; }
    .dz-event-name-field input, .dz-event-spawn-positions input { width:100%; min-height:2.7rem; border:1px solid #496b31; border-radius:.4rem; background:#09100b; color:#e7f7d2; padding:.6rem .7rem; }
    .dz-event-spawn-positions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; }
    .dz-event-spawn-positions article { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; padding:.7rem; border:1px solid #263b20; border-radius:.5rem; background:#101a12; }
    .dz-event-spawn-positions header { grid-column:1/-1; display:flex; justify-content:space-between; gap:.5rem; color:#d8f57b; }
    .dz-event-spawn-positions header code { color:#748477; font-size:.68rem; }
    .dz-event-spawn-positions label { display:grid; gap:.25rem; color:#dce8d5; font-size:.78rem; font-weight:700; }
    .dz-event-spawn-positions label small { display:block; color:#819080; font-weight:400; }
    .dz-types-advanced { display:grid; gap:.65rem; padding:0 1rem 1rem; }
    .dz-map-file-guide { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; padding:1rem; }
    .dz-map-file-guide > div { padding:1rem; border:1px solid var(--dz-border); border-radius:.75rem; background:rgba(7,17,11,.65); }
    .dz-map-file-guide p { margin:.35rem 0 0; color:var(--dz-muted); line-height:1.55; }
    .dz-map-file-guide > a,.dz-map-file-guide > button { align-self:center; justify-content:center; text-align:center; }
    .dz-types-flags, .dz-types-taxonomy { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; padding:.8rem; }
    .dz-types-flags label, .dz-types-taxonomy label { display:grid; grid-template-columns:minmax(0,1fr) minmax(150px,.45fr); align-items:center; gap:.8rem; padding:.7rem; border:1px solid #263b20; border-radius:.45rem; background:#0d1510; }
    .dz-types-flags label span, .dz-types-taxonomy label span { display:grid; gap:.2rem; }
    .dz-types-flags small, .dz-types-taxonomy small { color:#8f9e8d; line-height:1.4; }
    .dz-types-flags select, .dz-types-taxonomy input { width:100%; min-height:2.65rem; border:1px solid #496b31; border-radius:.4rem; background:#09100b; color:#e7f7d2; padding:.55rem .65rem; }
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
    .dz-map-source { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; padding:.55rem; border:1px solid rgba(190,209,175,.14); border-radius:.4rem; color:#cbd5c0; text-decoration:none; }
    .dz-map-source.uploaded { display:grid; grid-template-columns:minmax(0,1fr); }
    .dz-map-source:hover { border-color:#b8ed55; background:rgba(182,233,79,.08); }
    .dz-map-source span { min-width:0; display:grid; gap:.15rem; }
    .dz-map-source small { color:#879987; }
    .dz-map-source em { color:#68786b; font-size:.6rem; font-style:normal; }
    .dz-map-source b { flex:0 0 auto; max-width:7rem; color:#b8ed55; font-size:.68rem; text-align:right; overflow-wrap:anywhere; }
    .dz-map-source-actions { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.3rem; width:100%; margin-top:.35rem; padding-top:.45rem; border-top:1px solid rgba(190,209,175,.12); }
    .dz-map-source-actions a,.dz-map-source-actions button { min-height:2rem; padding:.38rem .48rem; border:1px solid #42583a; border-radius:.35rem; color:#cddac8; background:#111914; font-size:.58rem; font-weight:800; line-height:1.1; text-decoration:none; cursor:pointer; white-space:nowrap; }
    .dz-map-source-actions a,.dz-map-source-actions button { display:grid; place-items:center; text-align:center; }
    .dz-map-source-actions a:hover,.dz-map-source-actions button:hover { border-color:var(--dz-lime); color:#11180e; background:var(--dz-lime); }
    .dz-map-source.missing { border-color:rgba(217,119,56,.35); }
    .dz-map-source.missing b { color:#ffc08f; }
    .dz-map-raw-card { width:min(1100px,calc(100vw - 2rem)); height:min(82vh,850px); display:grid; grid-template-rows:auto auto minmax(0,1fr); overflow:hidden; }
    .dz-map-raw-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding-right:2rem; }
    .dz-map-raw-heading h3 { overflow-wrap:anywhere; }.dz-map-raw-heading .dz-eyebrow { margin:0 0 .25rem; }
    .dz-map-raw-copy { min-height:2.5rem; padding:.55rem .75rem; border:1px solid var(--dz-lime); border-radius:.4rem; color:#11180e; background:var(--dz-lime); font-size:.68rem; font-weight:850; cursor:pointer; white-space:nowrap; }
    .dz-map-raw-copy:hover { background:#cef672; }.dz-map-raw-copy:disabled { opacity:.55; cursor:wait; }
    .dz-map-raw-status { min-height:1.2rem; margin:.5rem 0; font-size:.68rem; }
    .dz-map-raw-status.error { color:#ffaaa3; }
    .dz-map-raw-content { min-width:0; min-height:0; margin:0; overflow:auto; border:1px solid #314333; border-radius:.5rem; padding:1rem; color:#dbe8d6; background:#060a07; font:12px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace; tab-size:4; white-space:pre; }
    .dz-map-legend input { accent-color:#b8ed55; margin-right:.5rem; }
    .dz-map-layers { display:grid; gap:.55rem; }
    .dz-map-layer-card { display:grid; gap:.45rem; padding:.65rem; border:1px solid #2c3d2e; border-radius:.55rem; background:#0c130e; }
    .dz-map-layers label { display:flex; align-items:flex-start; gap:.4rem; color:#c5d2bf; font-size:.75rem; cursor:pointer; } .dz-map-layers label span { display:grid; gap:.05rem; overflow-wrap:anywhere; } .dz-map-layers label b { color:#dce6d8; } .dz-map-layers label small { color:#8c9b8c; font-size:.65rem; }
    .dz-map-layer-card > p { margin:0; color:#879587; font-size:.65rem; line-height:1.45; }
    .dz-layer-cleanup-open { width:100%; padding:.5rem .6rem; border:1px solid #9b463f; border-radius:.4rem; color:#ffd4cf; background:#2b1715; font-size:.63rem; font-weight:850; cursor:pointer; }
    .dz-layer-cleanup-open:hover { border-color:#ef776d; background:#4a211e; }
    .dz-loot-legend summary { cursor:pointer; color:#9fc4ff; font-size:.63rem; font-weight:700; list-style:none; }
    .dz-loot-legend summary::-webkit-details-marker { display:none; }
    .dz-loot-legend summary::before { content:'ⓘ '; }
    .dz-loot-legend-list { display:grid; gap:.35rem; margin-top:.5rem; }
    .dz-loot-legend-item { display:flex; align-items:center; gap:.4rem; color:#cbd5c0; font-size:.68rem; }
    .dz-loot-legend-item i { width:.6rem; height:.6rem; border-radius:50%; flex:0 0 auto; }
    .dz-loot-legend-item small { color:#7d8c7e; margin-left:auto; }
    .dz-cleanup-list { display:grid; gap:.4rem; max-height:45vh; overflow:auto; margin:1rem 0; padding-right:.25rem; }
    .dz-cleanup-option { display:flex; align-items:center; gap:.55rem; padding:.6rem .7rem; border:1px solid #2c3d2e; border-radius:.4rem; background:#0c130e; color:#dce6d8; font-size:.8rem; cursor:pointer; }
    .dz-cleanup-option:hover { border-color:#496b31; }
    .dz-cleanup-option input { accent-color:#b8ed55; flex:0 0 auto; }
    .dz-cleanup-option-all { border-style:dashed; color:#e8c8b3; }
    .dz-layer-actions { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.3rem; width:100%; margin-top:.15rem; padding-top:.4rem; border-top:1px solid rgba(190,209,175,.12); }
    .dz-layer-actions a,.dz-layer-actions button { min-height:2rem; padding:.38rem .48rem; border:1px solid #42583a; border-radius:.35rem; color:#cddac8; background:#111914; font-size:.58rem; font-weight:800; line-height:1.1; text-decoration:none; cursor:pointer; white-space:nowrap; display:grid; place-items:center; text-align:center; }
    .dz-layer-actions a:hover,.dz-layer-actions button:hover { border-color:var(--dz-lime); color:#11180e; background:var(--dz-lime); }
    .dz-layer-label-toggle { grid-column:1/-1; display:flex; align-items:center; gap:.4rem; margin-top:.1rem; color:#aab6a4; font-size:.66rem; font-weight:700; cursor:pointer; }
    .dz-layer-label-toggle input { accent-color:#b8ed55; cursor:pointer; }
    .dz-map-label { border:0 !important; box-shadow:none !important; background:transparent !important; padding:0 !important; color:#f0f8e8; font:800 .68rem/1.15 ui-monospace,monospace; text-shadow:0 1px 2px #000,0 0 4px #000; white-space:nowrap; }
    .dz-map-label::before { display:none !important; }
    .dz-load-dense { display:flex; align-items:flex-start; gap:.45rem; margin:.35rem 0; padding:.55rem; border:1px dashed #506d2b; border-radius:.4rem; color:#d8f57b; text-decoration:none; font-size:.75rem; } .dz-load-dense span { display:grid; gap:.05rem; } .dz-load-dense small { color:#879987; }
    .dz-load-dense-loading { pointer-events:none; border-style:solid; border-color:#8fc52b; background:rgba(145,197,43,.08); animation:dz-load-dense-pulse 1.1s ease-in-out infinite; }
    .dz-load-dense-loading .dz-layer-dot { animation:dz-load-dense-spin 900ms linear infinite; }
    @keyframes dz-load-dense-pulse { 0%, 100% { opacity:1; } 50% { opacity:.55; } }
    @keyframes dz-load-dense-spin { to { transform:rotate(360deg); } }
    .dz-layer-dot { width:.65rem; height:.65rem; border-radius:50%; display:inline-block; }
    .layer-0 { background:#b8ed55; } .layer-1 { background:#80b8ff; } .layer-2 { background:#f1b44c; } .layer-3 { background:#e96a5f; } .layer-4 { background:#d58cff; } .layer-5 { background:#55e0c1; } .layer-6 { background:#ff82b2; } .layer-7 { background:#f6d365; }
    .dz-raw-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; } .dz-copy-raw { white-space:nowrap; }
    /* 2026 workspace redesign: one visual language across dashboard, setup and editors. */
    :root {
        --dz-bg: #080c09;
        --dz-panel: #101712;
        --dz-panel-2: #151e17;
        --dz-line: #2b392d;
        --dz-line-strong: #47612f;
        --dz-lime: #b7ef4a;
        --dz-lime-soft: #d9ff8f;
        --dz-orange: #e68b45;
        --dz-red: #f2776b;
        --dz-white: #f2f6ef;
        --dz-copy: #a9b6a7;
        --dz-radius: .8rem;
    }
    .fi-main { max-width:1720px; width:100%; }
    .fi-main::before { width:5rem; border-radius:999px; }
    .fi-header { align-items:flex-end; padding-bottom:.25rem; }
    .fi-header-heading { letter-spacing:-.035em; }
    .fi-sidebar { background:#0b110d !important; }
    .fi-sidebar-nav { padding-inline:.8rem !important; }
    .fi-sidebar-group-label { color:#69786c !important; font-size:.65rem !important; font-weight:800 !important; letter-spacing:.14em; text-transform:uppercase; }
    .fi-sidebar-item > a { min-height:2.65rem; border-radius:.55rem !important; }
    .fi-sidebar-item.fi-active > a { border-left:0; box-shadow:inset 0 0 0 1px rgba(183,239,74,.28); }
    .fi-section,.fi-ta-ctn,.fi-wi-stats-overview-stat,.fi-wi-account > div { border-radius:var(--dz-radius) !important; box-shadow:0 18px 48px rgba(0,0,0,.18) !important; }
    .dz-kicker { margin:0 0 .5rem; color:var(--dz-lime); font-size:.66rem; font-weight:900; letter-spacing:.19em; }

    .dz-workspace-nav { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:1.25rem; align-items:center; margin:0 0 1.35rem; padding:.65rem .75rem; border:1px solid var(--dz-line); border-radius:var(--dz-radius); background:rgba(15,22,17,.92); box-shadow:0 18px 50px rgba(0,0,0,.18); }
    .dz-workspace-identity { display:flex; align-items:center; gap:.65rem; min-width:0; padding-right:1rem; border-right:1px solid var(--dz-line); }
    .dz-server-pulse { width:.65rem; height:.65rem; flex:0 0 auto; border-radius:50%; background:var(--dz-lime); box-shadow:0 0 0 5px rgba(183,239,74,.1),0 0 18px rgba(183,239,74,.4); }
    .dz-workspace-identity > span:nth-child(2) { display:grid; min-width:0; }
    .dz-workspace-identity small { color:#77867a; font-size:.58rem; font-weight:900; letter-spacing:.14em; }
    .dz-workspace-identity strong { max-width:16rem; overflow:hidden; color:var(--dz-white); text-overflow:ellipsis; white-space:nowrap; }
    .dz-workspace-platform { padding:.25rem .45rem; border:1px solid #40552e; border-radius:999px; color:var(--dz-lime-soft); font-size:.58rem; font-weight:900; }
    .dz-workspace-nav nav { display:flex; gap:.3rem; overflow-x:auto; scrollbar-width:none; }
    .dz-workspace-nav nav a { display:flex; align-items:center; gap:.4rem; flex:0 0 auto; padding:.55rem .65rem; border-radius:.45rem; color:#9dab9b; font-size:.72rem; font-weight:750; text-decoration:none; }
    .dz-workspace-nav nav a svg { width:1rem; }
    .dz-workspace-nav nav a:hover,.dz-workspace-nav nav a.active { color:#10160d; background:var(--dz-lime); }
    .dz-workspace-meta { display:flex; gap:.6rem; color:#77867a; font-size:.65rem; white-space:nowrap; }

    .dz-command-center { position:relative; display:grid; grid-template-columns:minmax(0,1.4fr) minmax(300px,.8fr); gap:2rem; overflow:hidden; padding:clamp(1.3rem,3vw,2.25rem); border:1px solid var(--dz-line-strong); border-radius:1rem; background:linear-gradient(120deg,rgba(183,239,74,.1),transparent 42%),linear-gradient(160deg,#131d15,#0d130f); box-shadow:0 24px 70px rgba(0,0,0,.26); }
    .dz-command-center::after { position:absolute; right:-7rem; bottom:-9rem; width:24rem; height:24rem; border:1px solid rgba(183,239,74,.12); border-radius:50%; content:""; box-shadow:0 0 0 4rem rgba(183,239,74,.025),0 0 0 8rem rgba(183,239,74,.018); }
    .dz-command-copy,.dz-command-steps { position:relative; z-index:1; }
    .dz-command-copy h2 { max-width:740px; margin:.2rem 0 .65rem; color:var(--dz-white); font-size:clamp(1.55rem,3vw,2.5rem); line-height:1.05; letter-spacing:-.045em; }
    .dz-command-copy > p:not(.dz-kicker) { max-width:750px; margin:0; color:var(--dz-copy); line-height:1.6; }
    .dz-command-actions { display:flex; flex-wrap:wrap; gap:.6rem; margin-top:1.25rem; }
    .dz-command-actions a { padding:.7rem .9rem; border:1px solid var(--dz-line-strong); border-radius:.5rem; color:var(--dz-white); font-size:.76rem; font-weight:850; text-decoration:none; }
    .dz-command-actions a.primary { border-color:var(--dz-lime); color:#10160d; background:var(--dz-lime); }
    .dz-command-steps { display:grid; align-content:center; gap:.6rem; }
    .dz-command-steps > div { display:flex; align-items:center; gap:.75rem; padding:.75rem; border:1px solid var(--dz-line); border-radius:.65rem; background:rgba(7,11,8,.48); }
    .dz-command-steps b { display:grid; place-items:center; width:2rem; height:2rem; flex:0 0 auto; border:1px solid #405044; border-radius:50%; color:#718075; font-size:.65rem; }
    .dz-command-steps span { display:grid; }.dz-command-steps strong { color:#cbd5c7; font-size:.78rem; }.dz-command-steps small { color:#718075; font-size:.65rem; }
    .dz-command-steps .done b,.dz-command-steps .current b { border-color:var(--dz-lime); color:#11180e; background:var(--dz-lime); }
    .dz-command-steps .current { border-color:#5a7639; }

    .dz-setup { display:grid; gap:1rem; }
    .dz-setup-hero { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr); gap:2rem; padding:clamp(1.2rem,3vw,2rem); border:1px solid var(--dz-line); border-radius:1rem; background:linear-gradient(125deg,rgba(183,239,74,.08),transparent 44%),var(--dz-panel); }
    .dz-setup-hero h2 { max-width:760px; margin:.15rem 0 .6rem; color:var(--dz-white); font-size:clamp(1.45rem,3vw,2.35rem); line-height:1.08; letter-spacing:-.04em; }
    .dz-setup-hero > div:first-child > p:last-child { max-width:780px; color:var(--dz-copy); line-height:1.6; }
    .dz-setup-server label { display:grid; gap:.4rem; color:var(--dz-lime-soft); font-size:.72rem; font-weight:850; }
    .dz-setup-server select { width:100%; min-height:3rem; padding:.7rem .8rem; border:1px solid var(--dz-line-strong); border-radius:.55rem; color:var(--dz-white); background:#080d09; color-scheme:dark; }
    .dz-setup-progress { display:grid; gap:.4rem; margin-top:.8rem; color:#bac7b6; font-size:.72rem; }
    .dz-setup-progress span { display:flex; justify-content:space-between; }.dz-setup-progress span b { color:var(--dz-lime); }
    .dz-setup-progress i { height:.35rem; overflow:hidden; border-radius:999px; background:#263027; }.dz-setup-progress i b { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--dz-lime),#e6f78a); }
    .dz-setup-progress small { color:#718075; line-height:1.4; }
    .dz-setup-shortcuts { display:flex; gap:.45rem; overflow-x:auto; padding:.15rem 0 .35rem; scrollbar-width:thin; }
    .dz-setup-shortcuts a { display:flex; align-items:center; gap:.55rem; min-width:max-content; padding:.55rem .65rem; border:1px solid var(--dz-line); border-radius:.55rem; color:#b7c2b4; background:var(--dz-panel); text-decoration:none; }
    .dz-setup-shortcuts a:hover { border-color:var(--dz-lime); color:var(--dz-lime-soft); }.dz-setup-shortcuts svg { width:1rem; color:var(--dz-lime); }.dz-setup-shortcuts span { display:flex; gap:.45rem; align-items:center; font-size:.68rem; font-weight:800; }.dz-setup-shortcuts small { display:grid; place-items:center; min-width:1.4rem; height:1.4rem; border-radius:999px; color:#11180e; background:var(--dz-lime); font-size:.58rem; }
    .dz-setup-areas { display:grid; gap:.75rem; }
    .dz-setup-area { scroll-margin-top:6rem; overflow:hidden; border:1px solid var(--dz-line); border-radius:var(--dz-radius); background:var(--dz-panel); }
    .dz-setup-area[open] { border-color:#3f5531; }
    .dz-setup-area summary { display:grid; grid-template-columns:auto minmax(0,1fr) auto auto; gap:.8rem; align-items:center; padding:1rem; cursor:pointer; list-style:none; }
    .dz-setup-area summary::-webkit-details-marker { display:none; }
    .dz-setup-area-icon { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.55rem; color:var(--dz-lime); background:#1b291a; }.dz-setup-area-icon svg { width:1.2rem; }
    .dz-setup-area-title { display:grid; gap:.15rem; }.dz-setup-area-title strong { color:var(--dz-white); }.dz-setup-area-title small { color:#7e8e7f; font-size:.7rem; }
    .dz-setup-area-count { padding:.3rem .5rem; border-radius:999px; color:var(--dz-lime-soft); background:#1e2d19; font-size:.64rem; font-weight:900; }
    .dz-setup-chevron { color:#718075; font-size:1.2rem; transition:transform .2s; }.dz-setup-area[open] .dz-setup-chevron { transform:rotate(180deg); }
    .dz-setup-files { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; padding:.25rem .75rem .75rem; border-top:1px solid var(--dz-line); }
    .dz-setup-file { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:.7rem; align-items:center; min-width:0; padding:.8rem; border:1px solid #263229; border-radius:.65rem; background:#0c120e; }
    .dz-setup-file.is-ready { border-color:#3d582b; background:linear-gradient(120deg,rgba(183,239,74,.045),transparent 55%),#0c120e; }
    .dz-file-status-icon { display:grid; place-items:center; width:1.75rem; height:1.75rem; border-radius:50%; color:#8c9a8c; background:#202a21; }.dz-file-status-icon svg { width:.9rem; }.is-ready .dz-file-status-icon { color:#11180e; background:var(--dz-lime); }
    .dz-file-copy { min-width:0; }.dz-file-copy strong { color:var(--dz-white); }.dz-file-copy code { color:inherit; font-size:.78rem; overflow-wrap:anywhere; }.dz-file-copy p { margin:.2rem 0; color:#9caa9a; font-size:.7rem; line-height:1.45; }.dz-file-copy small { color:#657468; font-size:.62rem; }
    .dz-file-action { display:flex; align-items:center; gap:.35rem; padding:.5rem .6rem; border:1px solid var(--dz-line-strong); border-radius:.45rem; color:#dbe5d6; font-size:.67rem; font-weight:850; text-decoration:none; white-space:nowrap; }.is-ready .dz-file-action { border-color:#638936; color:var(--dz-lime-soft); }.dz-file-action:hover { color:#10160d; background:var(--dz-lime); }
    .dz-setup-area-footer { display:flex; justify-content:space-between; gap:1rem; align-items:center; padding:.75rem 1rem; border-top:1px solid var(--dz-line); color:#8d9b8b; font-size:.7rem; background:#0c120e; }.dz-setup-area-footer a { color:var(--dz-lime); font-weight:850; text-decoration:none; white-space:nowrap; }
    .dz-empty-state { display:grid; gap:.35rem; padding:1rem; border:1px dashed #596b50; border-radius:.65rem; color:#aeb9aa; }.dz-empty-state strong { color:var(--dz-white); }.dz-empty-state a { width:max-content; margin-top:.3rem; color:#10160d; background:var(--dz-lime); padding:.55rem .7rem; border-radius:.4rem; font-weight:850; text-decoration:none; }
    .dz-map-page-empty { min-height:22rem; place-content:center; justify-items:center; padding:2rem; text-align:center; }.dz-map-page-empty svg { width:2.5rem; color:var(--dz-lime); }

    .dz-import-shell { display:grid; gap:1rem; }
    .dz-import-hero { display:flex; justify-content:space-between; gap:2rem; align-items:flex-end; padding:1.2rem 0 .35rem; }
    .dz-import-hero h2 { margin:.15rem 0 .45rem; color:var(--dz-white); font-size:clamp(1.45rem,3vw,2.25rem); letter-spacing:-.04em; }.dz-import-hero p:last-child { max-width:760px; color:var(--dz-copy); }.dz-import-hero > a { flex:0 0 auto; padding:.55rem .7rem; border:1px solid var(--dz-line); border-radius:.45rem; color:#c4cfc0; font-size:.7rem; font-weight:800; text-decoration:none; }
    .dz-import-layout { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(320px,.65fr); gap:1rem; align-items:start; }
    .dz-import-form-new { margin:0; padding:0; gap:0; overflow:hidden; border-color:var(--dz-line); border-radius:var(--dz-radius); }
    .dz-form-section { display:grid; grid-template-columns:auto minmax(0,1fr); gap:.8rem; padding:1rem; border-bottom:1px solid var(--dz-line); }
    .dz-form-step { display:grid !important; place-items:center; width:2rem; height:2rem; border:1px solid #425046; border-radius:50%; color:#839085; font-size:.62rem; font-weight:900; }
    .dz-form-section:focus-within .dz-form-step { border-color:var(--dz-lime); color:#11180e; background:var(--dz-lime); }
    .dz-form-section label { gap:.35rem; }.dz-form-section label strong { color:var(--dz-white); }.dz-form-section label small { color:#758278; line-height:1.4; }
    .dz-area-field { display:grid; gap:.35rem; min-width:0; }
    .dz-area-field > strong { color:var(--dz-white); }
    .dz-area-field > small { color:#758278; line-height:1.4; }
    .dz-area-picker { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; margin-top:.5rem; }
    .dz-area-card { display:grid; gap:.25rem; padding:.65rem .75rem; border:1px solid var(--dz-line); border-radius:.45rem; background:#0d130f; text-decoration:none; min-width:0; }
    .dz-area-card strong { color:#cbd5c0; font-size:.8rem; overflow-wrap:anywhere; }
    .dz-area-card small { color:#7d8c7e; font-size:.66rem; line-height:1.4; overflow-wrap:anywhere; }
    .dz-area-card:hover { border-color:var(--dz-lime); }
    .dz-area-card.active { border-color:var(--dz-lime); background:rgba(182,233,79,.08); }
    .dz-area-card.active strong { color:var(--dz-lime); }
    @media (max-width:640px) { .dz-area-picker { grid-template-columns:1fr; } }
    .dz-import-form-new select { appearance:none; background-image:linear-gradient(45deg,transparent 50%,var(--dz-lime) 50%),linear-gradient(135deg,var(--dz-lime) 50%,transparent 50%); background-position:calc(100% - 15px) 50%,calc(100% - 10px) 50%; background-size:5px 5px; background-repeat:no-repeat; }
    .dz-upload-drop { padding:.9rem; border:1px dashed #526348; border-radius:.6rem; background:#0a100c; }.dz-upload-drop input { padding:0 !important; border:0 !important; min-height:auto !important; }.dz-upload-drop > span { color:#748178 !important; font-size:.67rem; }.dz-upload-drop b { width:max-content; padding:.35rem .45rem; border-radius:.35rem; color:#ffe0c2; background:#362113; font-size:.66rem; }
    .dz-import-form-new .dz-import-submit { display:flex; justify-content:center; gap:.5rem; align-items:center; margin:1rem; border-radius:.5rem; }
    .dz-import-guide { position:sticky; top:5.5rem; overflow:hidden; border:1px solid var(--dz-line); border-radius:var(--dz-radius); background:var(--dz-panel); }.dz-import-guide > .dz-kicker,.dz-import-guide > h3 { margin-inline:1rem; }.dz-import-guide > .dz-kicker { margin-top:1rem; }.dz-import-guide h3 { margin-top:0; color:var(--dz-white); }
    .dz-import-guide > div { display:grid; max-height:62vh; overflow:auto; border-top:1px solid var(--dz-line); }.dz-import-guide article { padding:.75rem 1rem; border-bottom:1px solid #222d25; }.dz-import-guide article.expected { border-left:3px solid var(--dz-orange); background:rgba(230,139,69,.07); }.dz-import-guide code { color:var(--dz-lime-soft); font-size:.72rem; font-weight:800; }.dz-import-guide article p { margin:.25rem 0 0; color:#8f9d8e; font-size:.68rem; line-height:1.45; }
    .dz-import-guide footer { display:flex; gap:.65rem; padding:.85rem 1rem; background:#0b110d; }.dz-import-guide footer svg { width:1.2rem; flex:0 0 auto; color:var(--dz-lime); }.dz-import-guide footer span { display:grid; }.dz-import-guide footer strong { color:#d7e0d3; font-size:.72rem; }.dz-import-guide footer small { color:#758278; font-size:.64rem; line-height:1.4; }

    .dz-editor-command { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1.25rem; align-items:end; padding:1.15rem 1.25rem; border:1px solid var(--dz-line); border-radius:var(--dz-radius); background:linear-gradient(120deg,rgba(128,184,255,.07),transparent 45%),var(--dz-panel); }
    .dz-editor-command h2 { margin:.1rem 0 .3rem; color:var(--dz-white); font-size:clamp(1.35rem,2.5vw,2rem); letter-spacing:-.035em; }.dz-editor-command > div:first-child > p:last-child { max-width:800px; margin:0; color:var(--dz-copy); font-size:.78rem; line-height:1.5; }
    .dz-editor-command-meta { display:flex; gap:.5rem; }.dz-editor-command-meta > span { display:grid; min-width:5.2rem; padding:.55rem .65rem; border:1px solid var(--dz-line); border-radius:.5rem; background:#0a100c; }.dz-editor-command-meta small { color:#68766b; font-size:.55rem; font-weight:900; letter-spacing:.12em; }.dz-editor-command-meta strong { color:var(--dz-lime-soft); font-size:.72rem; }
    .dz-editor-toolbar-new { display:flex; justify-content:space-between; gap:1rem; align-items:end; padding:.8rem; border:1px solid var(--dz-line); border-radius:var(--dz-radius); background:var(--dz-panel); }
    .dz-editor-file-choice { display:grid; gap:.3rem; min-width:0; }.dz-editor-file-choice > span { color:#718075; font-size:.58rem; font-weight:900; letter-spacing:.12em; text-transform:uppercase; }.dz-editor-file-choice .dz-file-menu { width:min(34rem,70vw); }.dz-editor-file-choice .dz-file-menu summary { min-height:2.75rem; border-radius:.5rem; }.dz-editor-file-choice .dz-file-menu summary small { margin-left:auto; color:var(--dz-lime); font-size:.65rem; }
    .dz-editor-toolbar-new .dz-tabs { padding:.25rem; border:1px solid var(--dz-line); border-radius:.55rem; background:#090e0b; }.dz-editor-toolbar-new .dz-tab { min-height:2.3rem; border:0; border-radius:.4rem; padding:.55rem .8rem; font-size:.7rem; }.dz-editor-toolbar-new .dz-tab.active { color:#11180e; background:var(--dz-lime); }

    .dz-event-groups-intro { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; padding:1rem; border-bottom:1px solid var(--dz-line); background:#0a100c; }
    .dz-event-groups-intro article { display:grid; gap:.2rem; padding:.7rem; border:1px solid #28372b; border-radius:.55rem; background:#101813; }
    .dz-event-groups-intro b { color:var(--dz-lime); font:900 .66rem/1.2 ui-monospace,monospace; letter-spacing:.08em; }
    .dz-event-groups-intro span { color:#91a08f; font-size:.67rem; line-height:1.45; }
    .dz-event-groups-list { display:grid; gap:.7rem; padding:1rem; }
    .dz-event-group { overflow:hidden; border:1px solid var(--dz-line); border-radius:.7rem; background:#0b110d; }
    .dz-event-group[open] { border-color:#496434; }
    .dz-event-group > summary { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1rem; cursor:pointer; list-style:none; background:#111914; }
    .dz-event-group > summary::-webkit-details-marker { display:none; }
    .dz-event-group > summary > span:first-child { display:grid; gap:.15rem; min-width:0; }
    .dz-event-group > summary small,.dz-event-child header small { color:#6f8072; font-size:.55rem; font-weight:900; letter-spacing:.13em; }
    .dz-event-group > summary strong { overflow:hidden; color:var(--dz-white); text-overflow:ellipsis; white-space:nowrap; }
    .dz-event-group-body { display:grid; gap:.8rem; padding:.8rem; border-top:1px solid var(--dz-line); }
    .dz-event-group-name { display:grid; grid-template-columns:minmax(0,1fr) minmax(220px,.45fr); gap:.8rem; align-items:center; padding:.8rem; border:1px solid #314333; border-radius:.6rem; background:#111914; }
    .dz-event-group-name > span,.dz-event-child label > span { display:grid; gap:.15rem; }
    .dz-event-group-name strong,.dz-event-child label strong { color:var(--dz-lime-soft); font-size:.74rem; }
    .dz-event-group-name small,.dz-event-child label small { color:#7d8c7e; font-size:.63rem; line-height:1.4; }
    .dz-event-group-name input,.dz-event-child input,.dz-event-child select { width:100%; min-height:2.65rem; min-width:0; padding:.6rem .7rem; border:1px solid #405a30; border-radius:.45rem; color:var(--dz-white); background:#080d09; color-scheme:dark; }
    .dz-event-group-name input:focus,.dz-event-child input:focus,.dz-event-child select:focus { outline:2px solid var(--dz-lime); outline-offset:1px; }
    .dz-event-group-name > code { grid-column:1/-1; color:#68776a; font-size:.62rem; }
    .dz-event-add-child { display:grid; grid-template-columns:minmax(0,1fr) minmax(220px,.55fr) auto; gap:.65rem; align-items:center; padding:.75rem; border:1px dashed #4b6339; border-radius:.6rem; background:rgba(183,239,74,.035); }
    .dz-event-add-child > span { display:grid; gap:.12rem; }.dz-event-add-child strong { color:var(--dz-lime-soft); font-size:.72rem; }.dz-event-add-child small { color:#7d8c7e; font-size:.62rem; line-height:1.4; }
    .dz-event-add-child input { width:100%; min-height:2.55rem; padding:.55rem .65rem; border:1px solid #405a30; border-radius:.45rem; color:var(--dz-white); background:#080d09; }
    .dz-event-add-child button { min-height:2.55rem; padding:.55rem .7rem; border:1px solid var(--dz-lime); border-radius:.45rem; color:#10160d; background:var(--dz-lime); font-size:.67rem; font-weight:850; cursor:pointer; }
    .dz-event-add-child > .dz-error { grid-column:1/-1; color:#fecaca; }
    .dz-event-children { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; }
    .dz-event-child { display:grid; gap:.7rem; min-width:0; padding:.8rem; border:1px solid #27362a; border-radius:.65rem; background:#101713; }
    .dz-event-child header { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; padding-bottom:.65rem; border-bottom:1px solid #263129; }
    .dz-event-child header span { display:grid; min-width:0; }
    .dz-event-child header strong { overflow:hidden; color:var(--dz-white); font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
    .dz-event-child header code { flex:0 0 auto; color:#718075; font-size:.6rem; }
    .dz-event-child-actions { display:flex; align-items:center; gap:.45rem; }.dz-event-child-actions button { padding:.3rem .45rem; border:1px solid #713d38; border-radius:.35rem; color:#ffb1aa; background:#251311; font-size:.58rem; font-weight:800; cursor:pointer; }
    .dz-event-child label { display:grid; gap:.35rem; min-width:0; }
    .dz-event-position-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.45rem; }
    .dz-event-loot-grid { display:grid; grid-template-columns:1fr 1fr minmax(180px,1.25fr); gap:.45rem; }

    [x-cloak] { display:none !important; }
    .dz-confirm-button[disabled] { opacity:.75; cursor:wait; }
    .dz-confirm-button.dz-confirm-saved { background:#8fc52b !important; color:#0d1509 !important; border-color:#8fc52b !important; }
    .dz-confirm-button.dz-confirm-error { background:#e0554a !important; color:#fff !important; border-color:#e0554a !important; }
    .dz-action { display:inline-flex; align-items:center; justify-content:center; padding:.7rem 1.15rem; border-radius:.25rem; color:#17210d; background:#b6e94f; font-weight:800; text-transform:uppercase; letter-spacing:.03em; border:0; cursor:pointer; }
    .dz-secondary { display:inline-flex; align-items:center; justify-content:center; padding:.65rem 1rem; border:1px solid rgba(182,233,79,.25); border-radius:.25rem; color:#b6e94f; background:#151e17; font-weight:700; text-decoration:none; cursor:pointer; }
    .dz-info { padding:.85rem 1rem; border-left:3px solid #91c52b; color:#dbeacb; background:rgba(145,197,43,.07); }
    .dz-log-page { display:grid; gap:1.25rem; }
    .dz-log-server-picker { display:grid; gap:.35rem; max-width:24rem; color:#d8f57b; font-size:.78rem; font-weight:800; }
    .dz-log-input { display:grid; gap:.6rem; }
    .dz-log-input label { color:#d8f57b; font-weight:700; font-size:.85rem; }
    .dz-log-input textarea { width:100%; border:1px solid #486a2c; border-radius:.5rem; background:#0b110d; color:#e7f7d2; padding:.85rem 1rem; font:.8rem/1.5 ui-monospace,SFMono-Regular,monospace; resize:vertical; }
    .dz-log-input textarea:focus { outline:2px solid #b8ed55; outline-offset:1px; }
    .dz-log-actions { display:flex; gap:.6rem; flex-wrap:wrap; }
    .dz-log-summary { display:flex; gap:1.25rem; flex-wrap:wrap; padding:.85rem 1rem; border:1px solid #2c3d2e; border-radius:.55rem; background:#0c130e; color:#aab6a4; font-size:.85rem; }
    .dz-log-summary b { color:#e7f7d2; }
    .dz-log-group h3 { display:flex; align-items:center; gap:.5rem; margin:0 0 .7rem; font-size:.95rem; letter-spacing:.03em; }
    .dz-log-group h3 small { padding:.1rem .5rem; border-radius:999px; font-size:.7rem; font-weight:800; background:#1a241a; color:#aab6a4; }
    .dz-log-group { display:grid; gap:.7rem; }
    .dz-log-finding { display:grid; gap:.4rem; padding:.9rem 1rem; border-radius:.55rem; border-left:3px solid #506d2b; background:#0c130e; }
    .dz-log-group-critical .dz-log-finding { border-left-color:#e0554a; background:rgba(224,85,74,.07); }
    .dz-log-group-warning .dz-log-finding { border-left-color:#d97738; background:rgba(217,119,56,.07); }
    .dz-log-group-info .dz-log-finding { border-left-color:#80b8ff; background:rgba(128,184,255,.06); }
    .dz-log-finding-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .dz-log-finding-head strong { color:#eef4ea; font-size:.9rem; }
    .dz-log-count { flex:0 0 auto; padding:.1rem .5rem; border-radius:999px; background:#1a241a; color:#d8f57b; font-size:.72rem; font-weight:800; }
    .dz-log-finding p { margin:0; color:#b8c4b3; font-size:.82rem; line-height:1.5; }
    .dz-log-finding-action { color:#e7f7d2 !important; }
    .dz-log-finding > a.dz-secondary { justify-self:start; }
    .dz-log-example { color:#8c9b8c; font-size:.75rem; }
    .dz-log-example summary { cursor:pointer; color:#aab6a4; }
    .dz-log-example code { display:block; margin-top:.4rem; padding:.5rem .65rem; border-radius:.35rem; background:#080d09; color:#cddac8; overflow-wrap:anywhere; font-size:.72rem; }

    @media (max-width:1100px) {
        .dz-workspace-nav { grid-template-columns:1fr; gap:.6rem; }.dz-workspace-identity { border-right:0; padding-right:0; }.dz-workspace-meta { display:none; }
        .dz-command-center,.dz-setup-hero { grid-template-columns:1fr; }
        .dz-setup-files { grid-template-columns:1fr; }
        .dz-import-layout { grid-template-columns:1fr; }.dz-import-guide { position:static; }.dz-import-guide > div { max-height:none; }
        .dz-editor-command { grid-template-columns:1fr; }.dz-editor-command-meta { flex-wrap:wrap; }
        .dz-event-groups-intro { grid-template-columns:repeat(2,minmax(0,1fr)); }.dz-event-add-child { grid-template-columns:1fr; }
        .dz-event-children { grid-template-columns:1fr; }
    }
    @media (max-width: 980px) { .dz-map-layout { grid-template-columns:1fr; } .dz-map-legend { position:static; max-height:none; } }
    @media (max-width: 620px) { .dz-map-source { flex-direction:column; }.dz-map-source-actions { width:100%; grid-template-columns:repeat(3,1fr); }.dz-map-source-actions a,.dz-map-source-actions button { display:grid; place-items:center; }.dz-map-raw-card { width:calc(100vw - .8rem); height:calc(100dvh - .8rem); padding:1rem; }.dz-map-raw-heading { flex-direction:column; padding-right:1.8rem; }.dz-map-raw-copy { width:100%; } }
    @media (max-width: 760px) { .dz-map-file-guide { grid-template-columns:1fr; } .dz-map-canvas { min-height:420px; height:65vh; } .dz-map-land { font-size:1.2rem; } .dz-map-marker { font-size:.62rem; } .dz-point-modal { padding:.4rem; align-items:start; } #dz-point-modal .dz-point-modal-card, #dz-edit-point-modal .dz-point-modal-card { width:calc(100vw - .8rem); max-height:calc(100dvh - .8rem); padding:1rem; } .dz-point-options { grid-template-columns:1fr 1fr; } .dz-event-catalog, .dz-point-fields, .dz-event-setting-grid, .dz-edit-point-fields, .dz-point-field-section-grid { grid-template-columns:1fr; } .dz-event-catalog > * { grid-column:1 !important; } .dz-edit-coordinate-grid { grid-template-columns:1fr; } .dz-edit-actions { flex-direction:column-reverse; } .dz-raw-heading { flex-direction:column; } .dz-copy-raw { width:100%; } .dz-setup-file { grid-template-columns:auto minmax(0,1fr); }.dz-file-action { grid-column:1/-1; justify-content:center; }.dz-setup-area-title small { display:none; }.dz-setup-area-footer { align-items:flex-start; flex-direction:column; }.dz-command-center { padding:1rem; }.dz-command-steps { gap:.4rem; }.dz-import-hero { align-items:flex-start; flex-direction:column; }.dz-form-section { grid-template-columns:1fr; }.dz-form-step { width:1.65rem; height:1.65rem; }.dz-editor-command-meta { display:grid; grid-template-columns:repeat(3,1fr); }.dz-editor-command-meta > span { min-width:0; }.dz-editor-toolbar-new { align-items:stretch; flex-direction:column; }.dz-editor-file-choice .dz-file-menu { width:100%; }.dz-editor-toolbar-new .dz-tabs { display:grid; grid-template-columns:1fr 1fr; }.dz-event-groups-intro { grid-template-columns:1fr; }.dz-event-group-name { grid-template-columns:1fr; }.dz-event-position-grid { grid-template-columns:1fr 1fr; }.dz-event-loot-grid { grid-template-columns:1fr; } }
    @media (max-width: 900px) { .dz-event-spawn-guide, .dz-event-spawn-positions, .dz-types-flags, .dz-types-taxonomy { grid-template-columns:1fr; }.dz-event-name-field, .dz-types-flags label, .dz-types-taxonomy label { grid-template-columns:1fr; }.dz-event-spawn-positions article { grid-template-columns:1fr; }.dz-event-spawn-positions header { grid-column:auto; } }
</style>
