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

Route::post('/admin/map-editor/points', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor) {
    $data = $request->validate(['project_id'=>'required|integer','type'=>'required|string|max:40','label'=>'required|string|max:120','x'=>'required|numeric|min:0|max:15360','z'=>'required|numeric|min:0|max:15360']);
    $project = Project::where('user_id', auth()->id())->findOrFail($data['project_id']);
    $filename = in_array($data['type'], ['vehicle','dynamic','animal','infected','heli','convoy'], true) ? 'events.xml' : 'cfgeventspawns.xml';
    $source = $project->revisions()->with('configurationImport')->get()->first(fn ($r) => strtolower($r->configurationImport?->original_filename ?? '') === $filename);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422, "Nejprve importujte {$filename}.");
    $xml = simplexml_load_string(Storage::disk('dayz')->get($source->storage_path), \SimpleXMLElement::class, LIBXML_NONET);
    abort_unless($xml, 422, 'XML soubor není validní.');
    if ($filename === 'events.xml') { $node = $xml->addChild('event'); $node->addAttribute('name', $data['label']); $pos = $node->addChild('pos'); $pos->addAttribute('x', (string) $data['x']); $pos->addAttribute('z', (string) $data['z']); }
    else { $node = $xml->addChild('event'); $node->addAttribute('name', $data['label']); $node->addAttribute('x', (string) $data['x']); $node->addAttribute('z', (string) $data['z']); }
    $saved = $editor->save($project, $source, $xml->asXML(), 'Přidán bod z mapového editoru', auth()->user());
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number]);
})->middleware('auth')->name('map-editor.points.store');

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
