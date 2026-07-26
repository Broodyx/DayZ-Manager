<?php

use App\Models\Project;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/up', fn () => response()->json(['status' => 'ok']))->name('health');

Route::post('/admin/map-editor/points', function (
    Request $request,
    \App\Services\Revision\ConfigurationRevisionEditor $editor,
    \App\Services\Revision\MapXmlEditor $eventEditor,
    \App\Services\Dayz\MapConfigurationEditor $mapEditor,
) {
    $data = $request->validate([
        'project_id'=>'required|integer','type'=>'required|string|max:40','label'=>'required|string|max:120',
        'target_filename'=>'required|string|max:160','x'=>'required|numeric|min:0|max:15360','z'=>'required|numeric|min:0|max:15360',
        'parameters'=>'nullable|array',
        'parameters.orientation'=>'nullable|numeric|min:-360|max:360',
        'parameters.radius'=>'nullable|numeric|min:1|max:5000',
        'parameters.zone_type'=>'nullable|in:HuntingGround,Rest,Graze,Water',
        'parameters.smin'=>'nullable|integer|min:0|max:1000','parameters.smax'=>'nullable|integer|min:0|max:1000',
        'parameters.dmin'=>'nullable|integer|min:0|max:1000','parameters.dmax'=>'nullable|integer|min:0|max:1000',
        'parameters.spawn_mode'=>'nullable|in:fresh,hop,travel','parameters.group_name'=>'nullable|string|max:120',
        'parameters.pos_y'=>'nullable|numeric|min:-1000|max:5000',
        'parameters.pitch'=>'nullable|numeric|min:-360|max:360','parameters.yaw'=>'nullable|numeric|min:-360|max:360','parameters.roll'=>'nullable|numeric|min:-360|max:360',
        'parameters.area_name'=>'nullable|string|max:120','parameters.pos_height'=>'nullable|numeric|min:0|max:5000',
        'parameters.neg_height'=>'nullable|numeric|min:0|max:5000','parameters.inner_part_dist'=>'nullable|numeric|min:1|max:1000',
        'parameters.outer_offset'=>'nullable|numeric|min:0|max:1000','parameters.particle_name'=>'nullable|string|max:255',
        'parameters.around_particle'=>'nullable|string|max:255','parameters.tiny_particle'=>'nullable|string|max:255',
        'parameters.ppe_type'=>'nullable|string|max:160',
    ]);
    $parameters = $data['parameters'] ?? [];
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $eventTypes = ['vehicle','dynamic','heli','convoy','aerial'];
    $filename = strtolower(basename($data['target_filename']));
    $animalTargets = [
        'AnimalBear' => 'bear_territories.xml', 'AnimalCow' => 'cattle_territories.xml',
        'AnimalDeer' => 'red_deer_territories.xml', 'AnimalRoeDeer' => 'roe_deer_territories.xml',
        'AnimalWolf' => 'wolf_territories.xml', 'AnimalWildBoar' => 'wild_boar_territories.xml',
        'AnimalSheep' => 'sheep_goat_territories.xml', 'AnimalPig' => 'pig_territories.xml',
        'AnimalFox' => 'fox_territories.xml', 'AnimalHare' => 'hare_territories.xml',
        'AnimalHen' => 'hen_territories.xml', 'AnimalDomestic' => 'domestic_animals_territories.xml',
    ];
    $validTarget = match (true) {
        in_array($data['type'], $eventTypes, true) => $filename === 'cfgeventspawns.xml',
        $data['type'] === 'player' => $filename === 'cfgplayerspawnpoints.xml',
        $data['type'] === 'contaminated' => $filename === 'cfgeffectarea.json',
        $data['type'] === 'loot' => $filename === 'mapgrouppos.xml',
        $data['type'] === 'animal' => ($animalTargets[$data['label']] ?? null) === $filename,
        $data['type'] === 'territory' => \Illuminate\Support\Str::is('*_territories.xml', $filename),
        default => false,
    };
    abort_unless($validTarget, 422, 'Zvolený typ nelze bezpečně zapsat do požadovaného souboru.');
    $source = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
        ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? ''))) === $filename);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422, "Nejprve importujte {$filename}.");
    if (in_array($data['type'], $eventTypes, true)) {
        $eventsRevision = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? ''))) === 'events.xml');
        abort_unless($eventsRevision && Storage::disk('dayz')->exists($eventsRevision->storage_path), 422, 'Nejprve importujte aktuální events.xml.');
        $eventsXml = @simplexml_load_string(Storage::disk('dayz')->get($eventsRevision->storage_path));
        $eventNames = [];
        foreach ($eventsXml?->event ?? [] as $event) {
            $eventNames[] = (string) ($event['name'] ?? '');
        }
        abort_unless(in_array($data['label'], $eventNames, true), 422, 'Vybraný event v aktuálním events.xml neexistuje.');
    }
    if ($data['type'] === 'loot') {
        $prototype = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? ''))) === 'mapgroupproto.xml');
        abort_unless($prototype && Storage::disk('dayz')->exists($prototype->storage_path), 422, 'Nejprve importujte aktuální mapgroupproto.xml.');
        $prototypeXml = @simplexml_load_string(Storage::disk('dayz')->get($prototype->storage_path));
        $groupNames = [];
        foreach ($prototypeXml?->group ?? [] as $group) {
            $groupNames[] = (string) ($group['name'] ?? '');
        }
        abort_unless(in_array($data['label'], $groupNames, true), 422, 'Vybraná loot skupina v aktuálním mapgroupproto.xml neexistuje.');
    }
    try {
        $content = Storage::disk('dayz')->get($source->storage_path);
        $content = match (true) {
            $filename === 'cfgeventspawns.xml' => $eventEditor->appendPosition($content, $data['label'], (float) $data['x'], (float) $data['z'], (float) ($parameters['orientation'] ?? 0))['xml'],
            $filename === 'cfgplayerspawnpoints.xml' => $mapEditor->appendPlayerSpawnArea($content, $parameters['group_name'] ?? $data['label'], (float) $data['x'], (float) $data['z'], $parameters['spawn_mode'] ?? 'fresh'),
            $filename === 'cfgeffectarea.json' => $mapEditor->appendContaminatedArea($content, $parameters['area_name'] ?? $data['label'], (float) $data['x'], (float) $data['z'], $parameters),
            $filename === 'mapgrouppos.xml' => $mapEditor->appendMapGroup($content, $data['label'], (float) $data['x'], (float) $data['z'], $parameters),
            \Illuminate\Support\Str::is('*_territories.xml', $filename) => $mapEditor->appendTerritoryZone($content, $parameters['zone_type'] ?? 'HuntingGround', (float) $data['x'], (float) $data['z'], $parameters),
        };
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $content, 'Přidán mapový bod '.$data['label'], auth()->user());
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number]);
})->middleware('auth')->name('map-editor.points.store');

Route::post('/admin/map-editor/points/update', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor) {
    $data = $request->validate(['project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','path'=>'required|string|max:1000','x'=>'required|numeric','z'=>'required|numeric','new_x'=>'required|numeric|min:0|max:15360','new_z'=>'required|numeric|min:0|max:15360']);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_if(str_ends_with(strtolower($data['filename']), '.json'), 422, 'JSON mapové body upravte ve vizuálním JSON editoru.');
    try {
        $content = $mapEditor->updateCoordinates($data['filename'], Storage::disk('dayz')->get($source->storage_path), $data['path'], (float) $data['new_x'], (float) $data['new_z']);
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $content, 'Upraven mapový bod X/Z', auth()->user());
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number]);
})->middleware('auth')->name('map-editor.points.update');

Route::post('/admin/map-editor/points/delete', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor) {
    $data = $request->validate(['project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','path'=>'required|string|max:1000','x'=>'required|numeric','z'=>'required|numeric']);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_if(str_ends_with(strtolower($data['filename']), '.json'), 422, 'JSON mapové body odstraňte ve vizuálním JSON editoru.');
    try {
        $content = $mapEditor->delete(Storage::disk('dayz')->get($source->storage_path), $data['path']);
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $editor->save($project, $source, $content, 'Odstraněn mapový bod', auth()->user());
    return response()->json(['ok'=>true]);
})->middleware('auth')->name('map-editor.points.delete');

Route::get('/admin/projects/{project}/configuration/{revision}/download', function (Project $project, \App\Models\ConfigurationRevision $revision) {
    abort_unless(((int) $project->user_id === (int) auth()->id() || auth()->user()?->is_admin) && (int) $revision->project_id === (int) $project->id, 403);
    $disk = Storage::disk('dayz');
    abort_unless($disk->exists($revision->storage_path), 404, 'Soubor revize již není v úložišti.');
    $revision->load('configurationImport');
    $name = app(\App\Services\Revision\ConfigurationRevisionEditor::class)->downloadName($revision);
    return $disk->download($revision->storage_path, $name, ['Content-Type' => 'application/octet-stream']);
})->middleware('auth')->name('configuration-revision.download');

Route::post('/admin/configuration-import/upload', function (ConfigurationImporter $importer) {
    request()->validate(['area' => ['nullable', 'string', 'max:40'], 'project_id' => ['required', 'integer'], 'platform' => ['required', 'in:playstation,xbox,steam'], 'file' => ['required', 'file', 'max:102400']]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail(request('project_id'));
    $import = $importer->import($project, request()->file('file'), auth()->user());
    $project->update(['platform' => request('platform'), 'platform_confidence' => 100]);
    $revision = $import->revisions()->latest('id')->firstOrFail();

    $destination = request('area') === 'map'
        ? '/admin/map-editor?project='.$project->id
        : '/admin/projects/'.$project->id.'/configuration?revision='.$revision->id;

    return redirect($destination)->with('status', "Importováno: {$import->original_filename}");
})->middleware('auth')->name('configuration-import.upload');
