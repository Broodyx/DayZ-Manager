<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro">
            <p class="dz-eyebrow">DAYZ MANAGER · PRŮVODCE</p>
            <h2>Co chcete na serveru změnit?</h2>
            <p class="dz-muted">Nemusíte znát názvy všech DayZ souborů. Vyberte oblast, server a nahrajte doporučený soubor nebo celý ZIP.</p>
            <ol class="dz-steps"><li><b>1</b> Vyberte oblast</li><li><b>2</b> Nahrajte konfiguraci</li><li><b>3</b> Upravte ji vizuálně</li><li><b>4</b> Stáhněte novou revizi</li></ol>
        </div>
        @foreach ($areas as $key => $area)
            <a class="dz-wizard-card" href="{{ url('/admin/configuration-import') }}?area={{ $key }}"><span class="dz-card-number">{{ $loop->iteration }}</span><x-filament::icon :icon="$area['icon']" class="dz-wizard-icon" /><strong>{{ $area['label'] }}</strong><span>{{ $area['files'] }}</span><small>{{ $area['description'] }}</small><em>Pokračovat k nahrání →</em></a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>
