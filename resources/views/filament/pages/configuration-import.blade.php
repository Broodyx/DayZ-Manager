<x-filament-panels::page>
    <div class="dz-import-page"><p class="dz-eyebrow">DAYZ MANAGER · KROK 2 ZE 4</p><h2>Nahrát konfiguraci serveru</h2><p class="dz-muted">Import nikdy nepřepíše původní revizi. ZIP se rozdělí na jednotlivé soubory, které potom otevřete ve správném editoru.</p>
        <form method="POST" action="{{ route('configuration-import.upload') }}" enctype="multipart/form-data" class="dz-import-form">@csrf
            <label><span><b>1</b> Co chcete upravit?</span><select name="area" required>@foreach(app(\App\Services\Dayz\ConfigurationCatalog::class)->options() as $key=>$label)<option value="{{ $key }}" @selected(request('area')===$key)>{{ $label }}</option>@endforeach</select><small>Podle volby vám editor vysvětlí příslušné parametry.</small></label>
            <label><span><b>2</b> Server</span><select name="project_id" required><option value="">Vyberte svůj server…</option>@foreach(\App\Models\Project::query()->when(!auth()->user()?->is_admin,fn($q)=>$q->where('user_id',auth()->id()))->orderBy('name')->pluck('name','id') as $id=>$name)<option value="{{ $id }}" @selected((int)request('project')===(int)$id)>{{ $name }}</option>@endforeach</select><small>Soubor i všechny další revize budou patřit k tomuto serveru.</small></label>
            <label><span><b>3</b> Platforma</span><select name="platform" required><option value="">Vyberte platformu…</option><option value="playstation">PlayStation</option><option value="xbox">Xbox</option><option value="steam">PC / Steam</option></select><small>Kontrola kompatibility upozorní na PC-only prvky.</small></label>
            <label><span><b>4</b> Soubor konfigurace</span><input type="file" name="file" required accept=".cfg,.txt,.xml,.json,.zip,.c"><small>{{ request('expected') ? 'Očekávaný soubor: '.request('expected') : 'Podporováno: XML, JSON, CFG, TXT, init.c nebo ZIP do 100 MB.' }}</small></label>
            <button type="submit" class="dz-import-submit">Importovat, ověřit a otevřít editor →</button>
        </form>
    </div>
</x-filament-panels::page>
