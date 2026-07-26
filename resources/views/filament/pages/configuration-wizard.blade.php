<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro"><p class="dz-eyebrow">DAYZ MANAGER</p><h2>Co chcete nastavit?</h2><p class="dz-muted">Vyberte oblast. Editor vám nabídne správné soubory, parametry a bezpečnou validaci.</p></div>
        @foreach ($areas as $key => $area)
            <a class="dz-wizard-card" href="{{ url('/admin/configuration-import') }}?area={{ $key }}"><x-filament::icon :icon="$area['icon']" class="dz-wizard-icon" /><strong>{{ $area['label'] }}</strong><span>{{ $area['files'] }}</span><small>{{ $area['description'] }}</small><em>Nahrát soubor →</em></a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>
