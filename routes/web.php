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
        'parameters.zone_type'=>'nullable|string|max:60|regex:/^[A-Za-z0-9_.-]+$/',
        'parameters.smin'=>'nullable|integer|min:0|max:1000','parameters.smax'=>'nullable|integer|min:0|max:1000',
        'parameters.dmin'=>'nullable|integer|min:0|max:1000','parameters.dmax'=>'nullable|integer|min:0|max:1000',
        'parameters.spawn_mode'=>'nullable|in:fresh,hop,travel','parameters.group_name'=>'nullable|string|max:120',
        'parameters.group_lifetime_override'=>'nullable|integer|min:-1','parameters.group_counter_override'=>'nullable|integer|min:-1',
        'parameters.min_dist_infected'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_infected'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_dist_player'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_player'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_dist_static'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_static'=>'nullable|numeric|min:0|max:15360',
        'parameters.grid_density'=>'nullable|integer|min:1|max:1000',
        'parameters.grid_width'=>'nullable|numeric|min:1|max:15360','parameters.grid_height'=>'nullable|numeric|min:1|max:15360',
        'parameters.generator_min_dist_static'=>'nullable|numeric|min:0|max:15360','parameters.generator_max_dist_static'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_steepness'=>'nullable|numeric|min:-90|max:90','parameters.max_steepness'=>'nullable|numeric|min:-90|max:90',
        'parameters.enablegroups'=>'nullable|in:true,false','parameters.groups_as_regular'=>'nullable|in:true,false',
        'parameters.lifetime'=>'nullable|integer|min:-1','parameters.counter'=>'nullable|integer|min:-1',
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
    $revisions = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get();
    $filenameOf = fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path)));
    $environmentTargets = app(\App\Services\Dayz\EnvironmentTargetCatalog::class)->targets($revisions, $filenameOf);
    $animalTargets = collect($environmentTargets)->reject(fn ($item) => $item['is_infected'])->pluck('file', 'name')->all();
    $infectedTargets = collect($environmentTargets)->filter(fn ($item) => $item['is_infected'])->pluck('file', 'name')->all();
    $validTarget = match (true) {
        in_array($data['type'], $eventTypes, true) => $filename === 'cfgeventspawns.xml',
        $data['type'] === 'player' => $filename === 'cfgplayerspawnpoints.xml',
        $data['type'] === 'contaminated' => $filename === 'cfgeffectarea.json',
        $data['type'] === 'loot' => $filename === 'mapgrouppos.xml',
        $data['type'] === 'animal' => ($animalTargets[$data['label']] ?? null) === $filename,
        $data['type'] === 'infected' => ($infectedTargets[$data['label']] ?? null) === $filename,
        $data['type'] === 'territory' => \Illuminate\Support\Str::is('*_territories.xml', $filename),
        default => false,
    };
    abort_unless($validTarget, 422, 'Zvolený typ nelze bezpečně zapsat do požadovaného souboru.');
    $source = $revisions->first(fn ($revision) => $filenameOf($revision) === $filename);
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
            $filename === 'cfgplayerspawnpoints.xml' => $mapEditor->appendPlayerSpawnArea($content, $parameters['group_name'] ?? $data['label'], (float) $data['x'], (float) $data['z'], $parameters['spawn_mode'] ?? 'fresh', $parameters),
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
    $data = $request->validate([
        'project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','path'=>'required|string|max:1000',
        'x'=>'required|numeric','z'=>'required|numeric','new_x'=>'required|numeric|min:0|max:15360','new_z'=>'required|numeric|min:0|max:15360',
        'parameters'=>'nullable|array','parameters.group_name'=>'nullable|string|max:120',
        'parameters.group_lifetime_override'=>'nullable|integer|min:-1','parameters.group_counter_override'=>'nullable|integer|min:-1',
        'parameters.min_dist_infected'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_infected'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_dist_player'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_player'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_dist_static'=>'nullable|numeric|min:0|max:15360','parameters.max_dist_static'=>'nullable|numeric|min:0|max:15360',
        'parameters.grid_density'=>'nullable|integer|min:1|max:1000',
        'parameters.grid_width'=>'nullable|numeric|min:1|max:15360','parameters.grid_height'=>'nullable|numeric|min:1|max:15360',
        'parameters.generator_min_dist_static'=>'nullable|numeric|min:0|max:15360','parameters.generator_max_dist_static'=>'nullable|numeric|min:0|max:15360',
        'parameters.min_steepness'=>'nullable|numeric|min:-90|max:90','parameters.max_steepness'=>'nullable|numeric|min:-90|max:90',
        'parameters.enablegroups'=>'nullable|in:true,false','parameters.groups_as_regular'=>'nullable|in:true,false',
        'parameters.lifetime'=>'nullable|integer|min:-1','parameters.counter'=>'nullable|integer|min:-1',
        'parameters.orientation'=>'nullable|numeric|min:0|max:359.999',
        'parameters.pos_y'=>'nullable|numeric|min:-1000|max:5000',
        'parameters.pitch'=>'nullable|numeric|min:-360|max:360','parameters.yaw'=>'nullable|numeric|min:-360|max:360','parameters.roll'=>'nullable|numeric|min:-360|max:360',
        'parameters.zone_type'=>'nullable|string|max:60|regex:/^[A-Za-z0-9_.-]+$/','parameters.radius'=>'nullable|numeric|min:1|max:5000',
        'parameters.smin'=>'nullable|integer|min:0|max:1000','parameters.smax'=>'nullable|integer|min:0|max:1000',
        'parameters.dmin'=>'nullable|integer|min:0|max:1000','parameters.dmax'=>'nullable|integer|min:0|max:1000',
        'parameters.name'=>['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]*$/'],
    ]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_if(str_ends_with(strtolower($data['filename']), '.json'), 422, 'JSON mapové body upravte ve vizuálním JSON editoru.');
    try {
        $content = $mapEditor->updateCoordinates($data['filename'], Storage::disk('dayz')->get($source->storage_path), $data['path'], (float) $data['new_x'], (float) $data['new_z'], $data['parameters'] ?? []);
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

Route::post('/admin/map-editor/points/bulk-delete', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor) {
    $data = $request->validate([
        'project_id' => ['required', 'integer'],
        'revision_id' => ['required', 'integer'],
        'filename' => ['required', 'in:cfgeventspawns.xml,cfgplayerspawnpoints.xml'],
        'scopes' => ['required', 'array', 'min:1'],
        'scopes.*' => ['string', 'max:220'],
    ]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless(strtolower(basename($source->configurationImport?->original_filename ?? '')) === $data['filename'], 422, 'Revize nepatří vybranému souboru.');
    abort_unless(Storage::disk('dayz')->exists($source->storage_path), 422, 'Zdrojová revize už není dostupná.');
    try {
        $result = $mapEditor->deleteScopes($data['filename'], Storage::disk('dayz')->get($source->storage_path), $data['scopes']);
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $result['content'], "Hromadně odstraněno {$result['deleted']} mapových bodů", auth()->user());

    return response()->json(['ok' => true, 'deleted' => $result['deleted'], 'revision' => $saved->revision_number]);
})->middleware('auth')->name('map-editor.points.bulk-delete');

Route::get('/admin/projects/{project}/configuration/{revision}/download', function (Project $project, \App\Models\ConfigurationRevision $revision) {
    abort_unless(((int) $project->user_id === (int) auth()->id() || auth()->user()?->is_admin) && (int) $revision->project_id === (int) $project->id, 403);
    $disk = Storage::disk('dayz');
    abort_unless($disk->exists($revision->storage_path), 404, 'Soubor revize již není v úložišti.');
    $revision->load('configurationImport');
    $name = app(\App\Services\Revision\ConfigurationRevisionEditor::class)->downloadName($revision);
    $revision->forceFill(['downloaded_at' => now()])->save();
    return $disk->download($revision->storage_path, $name, ['Content-Type' => 'application/octet-stream']);
})->middleware('auth')->name('configuration-revision.download');

Route::get('/admin/projects/{project}/configuration/download-all', function (Project $project, \App\Services\Storage\ProjectConfigurationZipBuilder $zipBuilder) {
    abort_unless((int) $project->user_id === (int) auth()->id() || auth()->user()?->is_admin, 403);
    try {
        $result = $zipBuilder->build($project);
    } catch (\RuntimeException $exception) {
        return redirect(\App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $project->id]))
            ->with('status_warning', $exception->getMessage());
    }
    $filename = \Illuminate\Support\Str::slug($project->name).'-configuration.zip';

    return response()->download($result['path'], $filename)->deleteFileAfterSend(true);
})->middleware('auth')->name('project.configuration.download-all');

Route::get('/admin/projects/{project}/configuration/{revision}/raw', function (Project $project, \App\Models\ConfigurationRevision $revision) {
    abort_unless(((int) $project->user_id === (int) auth()->id() || auth()->user()?->is_admin) && (int) $revision->project_id === (int) $project->id, 403);
    $disk = Storage::disk('dayz');
    abort_unless($disk->exists($revision->storage_path), 404, 'Soubor revize již není v úložišti.');

    return response($disk->get($revision->storage_path), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'private, no-store',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->middleware('auth')->name('configuration-revision.raw');

Route::post('/admin/configuration-import/upload', function (ConfigurationImporter $importer) {
    request()->validate([
        'area' => ['nullable', 'string', 'max:40'],
        'project_id' => ['required', 'integer'],
        'platform' => ['required', 'in:playstation,xbox,steam'],
        'files' => ['required', 'array', 'min:1'],
        'files.*' => ['required', 'file', 'max:102400'],
    ]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail(request('project_id'));

    $files = request()->file('files');
    $importedNames = [];
    $failed = [];
    $lastImport = null;
    foreach ($files as $file) {
        try {
            $lastImport = $importer->import($project, $file, auth()->user());
            $importedNames[] = $lastImport->original_filename;
        } catch (\Throwable $exception) {
            $failed[] = ($file->getClientOriginalName() ?: 'soubor').': '.$exception->getMessage();
        }
    }
    $project->update(['platform' => request('platform'), 'platform_confidence' => 100]);

    $redirectResponse = null;
    if (count($files) === 1 && $failed === [] && $lastImport) {
        $revision = $lastImport->revisions()->latest('id')->firstOrFail();
        $destination = request('area') === 'map'
            ? '/admin/map-editor?project='.$project->id
            : '/admin/projects/'.$project->id.'/configuration?revision='.$revision->id;
        $redirectResponse = redirect($destination);
    } else {
        $redirectResponse = redirect(\App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $project->id]));
    }

    if ($importedNames !== []) {
        $redirectResponse->with('status', count($importedNames) === 1
            ? "Importováno: {$importedNames[0]}"
            : count($importedNames).'× importováno: '.implode(', ', $importedNames));
    }
    if ($failed !== []) {
        $redirectResponse->with('status_warning', count($failed).'× se nepodařilo importovat: '.implode(' | ', $failed));
    }

    return $redirectResponse;
})->middleware('auth')->name('configuration-import.upload');
