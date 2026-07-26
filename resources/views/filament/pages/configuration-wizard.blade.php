<x-filament-panels::page>
    <div class="dz-wizard-grid">
        <div class="dz-wizard-intro">
            <p class="dz-eyebrow">DAYZ MANAGER · PRŮVODCE</p>
            <h2>Co chcete na serveru změnit?</h2>
            <p class="dz-muted">Nemusíte znát názvy všech DayZ souborů. Vyberte oblast, server a nahrajte doporučený soubor nebo celý ZIP.</p>
            @if ($projects)
                <label class="dz-wizard-server-picker">
                    <span>Server, který chcete spravovat</span>
                    <select onchange="window.location.href='{{ url('/admin/configuration-wizard') }}?project='+this.value">
                        @foreach ($projects as $id => $name)
                            <option value="{{ $id }}" @selected((int) $projectId === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <div class="dz-wizard-empty">Nejprve založte server. Průvodce pak pozná, které soubory už máte nahrané.</div>
            @endif
            <ol class="dz-steps"><li><b>1</b> Vyberte server</li><li><b>2</b> Vyberte oblast</li><li><b>3</b> Nahrajte chybějící soubor nebo rovnou editujte existující</li><li><b>4</b> Stáhněte novou revizi</li></ol>
        </div>
        @foreach ($areas as $key => $area)
            <a class="dz-wizard-card {{ $area['uploaded_count'] ? 'has-configuration' : 'needs-upload' }}" href="{{ $area['url'] }}">
                <span class="dz-card-number">{{ $loop->iteration }}</span>
                <x-filament::icon :icon="$area['icon']" class="dz-wizard-icon" />
                <strong>{{ $area['label'] }}</strong>
                <span>{{ $area['files'] }}</span>
                <small>{{ $area['description'] }}</small>
                @if ($area['uploaded_count'])
                    <span class="dz-wizard-file-state">Nahráno {{ $area['uploaded_count'] }} {{ $area['uploaded_count'] === 1 ? 'soubor' : ($area['uploaded_count'] < 5 ? 'soubory' : 'souborů') }} · poslední {{ $area['latest_filename'] }} · revize #{{ $area['latest_revision'] }}</span>
                    <em>{{ $key === 'map' ? 'Otevřít mapový editor' : 'Pokračovat do editoru' }} →</em>
                @else
                    <span class="dz-wizard-file-state missing">Pro tento server zatím není nahraná odpovídající konfigurace.</span>
                    <em>Nahrát první soubor →</em>
                @endif
            </a>
        @endforeach
        <a class="dz-wizard-card dz-wizard-create" href="{{ url('/admin/projects/create') }}"><strong>+ Založit nový server</strong><span>Nejdříve vytvořte server a potom do něj nahrajte konfiguraci.</span><em>Vytvořit server →</em></a>
    </div>
</x-filament-panels::page>
