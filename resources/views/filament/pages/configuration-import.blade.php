<x-filament-panels::page>
    <div class="dz-import-page"><p class="dz-eyebrow">DAYZ MANAGER · NOVÝ IMPORT</p><h2>Nahrát konfiguraci serveru</h2><p class="dz-muted">Vyberte oblast, server a soubor. Import vytvoří novou revizi a původní data zůstanou zachována.</p>
        <form wire:submit="import" class="dz-import-form">{{ $this->form }}<x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Importovat a vytvořit revizi</x-filament::button></form>
    </div>
</x-filament-panels::page>
