<div id="dz-global-confirm-dialog" class="dz-point-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dz-global-confirm-title">
    <div class="dz-point-modal-card dz-system-dialog-card">
        <p class="dz-eyebrow">POTVRZENÍ ZMĚNY</p>
        <h3 id="dz-global-confirm-title">Potvrdit akci</h3>
        <p class="dz-system-dialog-message"></p>
        <div class="dz-system-dialog-actions">
            <button type="button" class="dz-secondary dz-global-confirm-cancel">Zrušit</button>
            <button type="button" class="dz-danger dz-global-confirm-ok">Potvrdit</button>
        </div>
    </div>
</div>
<script>
    if (!window.dzConfirm) {
        window.dzConfirm = (message, options = {}) => new Promise((resolve) => {
            const dialog = document.getElementById('dz-global-confirm-dialog');
            if (!dialog) { resolve(window.confirm(message)); return; }
            dialog.querySelector('#dz-global-confirm-title').textContent = options.title || 'Potvrdit akci';
            dialog.querySelector('.dz-system-dialog-message').textContent = message;
            const okBtn = dialog.querySelector('.dz-global-confirm-ok');
            const cancelBtn = dialog.querySelector('.dz-global-confirm-cancel');
            okBtn.textContent = options.confirmLabel || 'Potvrdit';
            cancelBtn.textContent = options.cancelLabel || 'Zrušit';

            const cleanup = (result) => {
                dialog.hidden = true;
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                dialog.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey);
                resolve(result);
            };
            const onOk = () => cleanup(true);
            const onCancel = () => cleanup(false);
            const onBackdrop = (event) => { if (event.target === dialog) cleanup(false); };
            const onKey = (event) => { if (event.key === 'Escape') cleanup(false); };

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            dialog.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey);
            dialog.hidden = false;
        });
    }
</script>
