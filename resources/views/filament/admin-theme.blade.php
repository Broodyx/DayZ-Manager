<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
<link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">
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

    .dz-map-toolbar { display:flex; justify-content:space-between; gap:1rem; align-items:flex-end; margin-bottom:1rem; }
    .dz-map-toolbar h2 { margin:.15rem 0; color:#d8f57b; font-size:1.5rem; }
    .dz-eyebrow { color:#a9df42; font-size:.7rem; letter-spacing:.16em; font-weight:700; margin:0; }
    .dz-muted { color:#9aa99b; font-size:.9rem; }
    .dz-map-select { background:#111a14; border:1px solid #506d2b; border-radius:.5rem; color:#e7f7d2; padding:.7rem 1rem; }
    .dz-map-upload { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
    .dz-map-upload input { max-width:180px; color:#b8c8b4; font-size:.75rem; }
    .dz-file-button { display:inline-flex; align-items:center; cursor:pointer; background:#273725; color:#e7f7d2; border:1px solid #5b7f35; border-radius:.45rem; padding:.65rem .8rem; font-weight:700; }
    .dz-file-button input { display:none; }
    .dz-map-upload button { background:#b8ed55; color:#14200f; border:0; border-radius:.45rem; padding:.65rem .8rem; font-weight:700; }
    .dz-map-upload small { width:100%; color:#879987; }
    .dz-map-layout { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:1rem; }
    .dz-map-canvas { height:min(72vh,760px); min-height:560px; position:relative; overflow:hidden; border:1px solid #3e5c28; border-radius:.7rem; background:#1c2b20; }
    #dayz-leaflet-map { width:100%; height:100%; background:#1c2b20; }
    .leaflet-container { background:#1c2b20; font-family:inherit; }
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
    .dz-map-legend { border:1px solid #34472d; border-radius:.7rem; background:#111a14; padding:1rem; }
    .dz-map-legend h3 { color:#d8f57b; margin-top:0; } .dz-map-legend label { display:block; padding:.55rem 0; color:#c5d2bf; }
    .dz-map-legend input { accent-color:#b8ed55; margin-right:.5rem; }
    @media (max-width: 760px) { .dz-map-toolbar { align-items:stretch; flex-direction:column; } .dz-map-layout { grid-template-columns:1fr; } .dz-map-canvas { min-height:420px; } .dz-map-land { font-size:1.2rem; } .dz-map-marker { font-size:.62rem; } .dz-point-options { grid-template-columns:1fr 1fr; } }
</style>
