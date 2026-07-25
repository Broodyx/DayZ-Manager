<x-filament-panels::page>
    <div class="dz-import-page"><p class="dz-eyebrow">DAYZ MANAGER · NOVÝ IMPORT</p><h2>Nahrát konfiguraci serveru</h2><p class="dz-muted">Vyberte oblast, server a soubor. Import vytvoří novou revizi a původní data zůstanou zachována.</p>
        <form method="POST" action="{{ route('configuration-import.upload') }}" enctype="multipart/form-data" class="dz-import-form">@csrf
            <label>Server<select name="project_id" required>@foreach(\App\Models\Project::where('user_id',auth()->id())->orderBy('name')->pluck('name','id') as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <label>Platforma<select name="platform" required><option value="playstation">PlayStation</option><option value="xbox">Xbox</option><option value="steam">PC / Steam</option></select></label>
            <label>Soubor konfigurace<input type="file" name="file" required accept=".cfg,.txt,.xml,.json,.zip"></label>
            <button type="submit" class="fi-btn fi-color-primary">Importovat a vytvořit revizi</button>
        </form>
    </div>
</x-filament-panels::page>
