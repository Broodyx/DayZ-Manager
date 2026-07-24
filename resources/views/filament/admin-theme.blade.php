<style>
    :root {
        color-scheme: dark;
        --dayz-background: #0b120d;
        --dayz-surface: #111b13;
        --dayz-surface-raised: #162219;
        --dayz-border: rgba(163, 230, 53, 0.16);
        --dayz-accent: #a3e635;
        --dayz-accent-strong: #84cc16;
        --dayz-text: #f4f7f2;
        --dayz-muted: #cbd5c0;
    }

    ::selection {
        color: #17210d;
        background: var(--dayz-accent);
    }

    .fi-body {
        color: var(--dayz-text);
        background-color: var(--dayz-background);
        background-image:
            radial-gradient(circle at 15% 10%, rgba(132, 204, 22, 0.1), transparent 28rem),
            linear-gradient(rgba(255, 255, 255, 0.012) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.012) 1px, transparent 1px);
        background-size: auto, 32px 32px, 32px 32px;
        background-attachment: fixed;
    }

    .fi-simple-layout {
        background: transparent;
    }

    .fi-simple-main {
        border: 1px solid var(--dayz-border);
        border-radius: 1rem;
        background: rgba(17, 27, 19, 0.94);
        box-shadow:
            0 24px 70px rgba(0, 0, 0, 0.42),
            0 0 0 1px rgba(255, 255, 255, 0.02) inset;
        backdrop-filter: blur(14px);
    }

    .fi-logo {
        color: var(--dayz-accent) !important;
        font-weight: 800;
        letter-spacing: -0.035em;
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
        background: rgba(13, 22, 15, 0.98) !important;
    }

    .fi-topbar nav {
        border-bottom: 1px solid var(--dayz-border);
        background: rgba(11, 18, 13, 0.88) !important;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(14px);
    }

    .fi-sidebar-item.fi-active > a {
        color: var(--dayz-accent);
        background: rgba(132, 204, 22, 0.14);
    }

    .fi-sidebar-item > a:hover {
        background: rgba(163, 230, 53, 0.08);
    }

    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat {
        border-color: var(--dayz-border) !important;
        background: rgba(17, 27, 19, 0.92) !important;
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.2);
    }

    .fi-input-wrp {
        border-color: rgba(203, 213, 192, 0.18);
        background: rgba(7, 12, 8, 0.7) !important;
    }

    .fi-input-wrp:focus-within {
        border-color: var(--dayz-accent-strong);
        box-shadow: 0 0 0 1px var(--dayz-accent-strong);
    }

    .fi-btn.fi-color-primary {
        color: #17210d;
        box-shadow: 0 8px 24px rgba(132, 204, 22, 0.2);
    }

    .fi-btn.fi-color-primary:hover {
        box-shadow: 0 10px 30px rgba(163, 230, 53, 0.28);
    }
</style>
