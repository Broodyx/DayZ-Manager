<?php

use App\Models\Project;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/up', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('/admin/projects/{project}/configuration/{revision}/download', function (Project $project, \App\Models\ConfigurationRevision $revision) {
    abort_unless((int) $project->user_id === (int) auth()->id() && (int) $revision->project_id === (int) $project->id, 403);
    $disk = Storage::disk('dayz');
    abort_unless($disk->exists($revision->storage_path), 404, 'Soubor revize již není v úložišti.');
    $revision->load('configurationImport');
    $name = app(\App\Services\Revision\ConfigurationRevisionEditor::class)->downloadName($revision);
    return $disk->download($revision->storage_path, $name, ['Content-Type' => 'application/octet-stream']);
})->middleware('auth')->name('configuration-revision.download');

Route::post('/admin/configuration-import/upload', function (ConfigurationImporter $importer) {
    request()->validate(['project_id' => ['required', 'integer'], 'platform' => ['required', 'in:playstation,xbox,steam'], 'file' => ['required', 'file', 'max:102400']]);
    $project = Project::where('user_id', auth()->id())->findOrFail(request('project_id'));
    $import = $importer->import($project, request()->file('file'), auth()->user());
    $project->update(['platform' => request('platform'), 'platform_confidence' => 100]);
    $revision = $import->revisions()->latest('id')->firstOrFail();

    return redirect('/admin/projects/'.$project->id.'/configuration?revision='.$revision->id)->with('status', "Importováno: {$import->original_filename}");
})->middleware('auth')->name('configuration-import.upload');
