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
    $data = $request->validate(['project_id'=>'required|integer','type'=>'required|string|max:40','label'=>'required|string|max:120','x'=>'required|numeric|min:0|max:15360','z'=>'required|numeric|min:0|max:15360','radius'=>'nullable|numeric|min:1|max:5000']);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $eventTypes = ['vehicle','dynamic','animal','infected','heli','convoy','aerial'];
    $filename = in_array($data['type'], $eventTypes, true) ? 'cfgeventspawns.xml' : ($data['type'] === 'player' ? 'cfgplayerspawnpoints.xml' : null);
    abort_unless($filename, 422, 'Tento typ vyžaduje specializovaný soubor a nelze ho bezpečně zapsat bez dalších parametrů. Nahrajte a upravte příslušnou konfiguraci.');
    $source = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
        ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? ''))) === $filename);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422, "Nejprve importujte {$filename}.");
    try {
        $content = Storage::disk('dayz')->get($source->storage_path);
        $content = $filename === 'cfgeventspawns.xml'
            ? $eventEditor->appendPosition($content, $data['label'], (float) $data['x'], (float) $data['z'])['xml']
            : $mapEditor->appendPlayerSpawnArea($content, $data['label'], (float) $data['x'], (float) $data['z']);
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
