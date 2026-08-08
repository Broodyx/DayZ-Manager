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
    \App\Services\Revision\TypesXmlEditor $typesEditor,
    \App\Services\Dayz\EventsXmlEditor $eventsEditor,
    \App\Services\Revision\SpawnableTypesXmlEditor $spawnableEditor,
    \App\Services\Revision\ObjectSpawnerJsonEditor $objectSpawnerEditor,
    ConfigurationImporter $configurationImporter,
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
        'parameters.auto_add_types'=>'nullable|boolean',
        'parameters.damage_min'=>'nullable|numeric|min:0|max:1','parameters.damage_max'=>'nullable|numeric|min:0|max:1','parameters.cargo_preset'=>'nullable|string|max:80','parameters.attachments'=>'nullable|string|max:2000',
        'parameters.cargo_items'=>'nullable|string|max:20000','parameters.hoarder'=>'nullable|boolean',
        'parameters.event_nominal'=>'nullable|integer|min:0|max:100000','parameters.event_min'=>'nullable|integer|min:0|max:100000','parameters.event_max'=>'nullable|integer|min:0|max:100000',
        'parameters.event_lifetime'=>'nullable|integer|min:0|max:3888000','parameters.event_restock'=>'nullable|integer|min:0|max:3888000','parameters.event_saferadius'=>'nullable|integer|min:0|max:20000','parameters.event_distanceradius'=>'nullable|integer|min:0|max:20000','parameters.event_cleanupradius'=>'nullable|integer|min:0|max:20000','parameters.event_active'=>'nullable|boolean','parameters.event_position'=>'nullable|in:fixed,player','parameters.event_limit'=>'nullable|in:mixed,custom,child,parent',
        'parameters.classname'=>'nullable|string|max:120','parameters.height'=>'nullable|numeric|min:-1000|max:5000',
        'parameters.yaw'=>'nullable|numeric|min:-360|max:360','parameters.pitch'=>'nullable|numeric|min:-360|max:360','parameters.roll'=>'nullable|numeric|min:-360|max:360',
        'parameters.scale'=>'nullable|numeric|min:0.01|max:100','parameters.enable_ce_persistency'=>'nullable|boolean',
    ]);
    $parameters = $data['parameters'] ?? [];
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);

    if ($data['type'] === 'custom') {
        // Object Spawner is JSON-shaped, project-chosen-filename ("custom/military.json"), and
        // can create both its target file AND its cfggameplay.json registration on first save —
        // none of which fits the shared XML-editor match(true) flow below (which has no default
        // arm and would UnhandledMatchError on 'custom' if this didn't return early).
        $targetFile = str_replace('\\', '/', trim($data['target_filename']));
        abort_unless((bool) preg_match('#^custom/[A-Za-z0-9_-]+\.json$#', $targetFile), 422, 'Cílový soubor musí být ve tvaru custom/nazev.json.');
        $objectData = [
            'name' => trim((string) ($parameters['classname'] ?? '')),
            'pos' => [(float) $data['x'], (float) ($parameters['height'] ?? 0), (float) $data['z']],
            'ypr' => [(float) ($parameters['yaw'] ?? 0), (float) ($parameters['pitch'] ?? 0), (float) ($parameters['roll'] ?? 0)],
            'scale' => (float) ($parameters['scale'] ?? 1),
            'enableCEPersistency' => (bool) ($parameters['enable_ce_persistency'] ?? false),
        ];
        $errors = $objectSpawnerEditor->validate($objectData);
        abort_if($errors !== [], 422, $errors[0] ?? '');

        $source = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? '')) === strtolower($targetFile));
        if (! $source || ! Storage::disk('dayz')->exists($source->storage_path)) {
            try {
                $import = $configurationImporter->importGeneratedFile($project, $targetFile, '{"Objects": []}', auth()->user());
            } catch (\RuntimeException $exception) {
                abort(422, $exception->getMessage());
            }
            $source = $import->revisions()->latest('revision_number')->first();
        }

        try {
            $content = $objectSpawnerEditor->append(Storage::disk('dayz')->get($source->storage_path), $objectData);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }
        $saved = $editor->save($project, $source, $content, 'Přidán objekt '.$objectData['name'].' ('.round($data['x'], 1).', '.round((float) ($parameters['height'] ?? 0), 1).', '.round($data['z'], 1).')', auth()->user());

        $gameplayWarning = null;
        $gameplaySource = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === 'cfggameplay.json');
        if (! $gameplaySource || ! Storage::disk('dayz')->exists($gameplaySource->storage_path)) {
            $gameplayWarning = 'Objekt uložen. Registrace v cfggameplay.json se nesynchronizovala — nejprve importujte aktuální cfggameplay.json.';
        } else {
            try {
                $gameplayContent = $objectSpawnerEditor->registerSpawnerFile(Storage::disk('dayz')->get($gameplaySource->storage_path), $targetFile);
                $editor->save($project, $gameplaySource, $gameplayContent, 'Zaregistrován '.$targetFile.' v objectSpawnersArr', auth()->user());
            } catch (\RuntimeException $exception) {
                $gameplayWarning = 'Objekt uložen. Registrace v cfggameplay.json se nesynchronizovala — '.$exception->getMessage();
            }
        }

        return response()->json(['ok' => true, 'revision' => $saved->revision_number, 'warning' => $gameplayWarning]);
    }

    $eventTypes = ['vehicle','dynamic','heli','convoy','aerial'];
    $eventBacked = [...$eventTypes, 'animal'];
    $eventName = $data['type'] === 'animal' ? 'Animal'.$data['label'] : $data['label'];
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
    if (in_array($data['type'], $eventBacked, true)) {
        $eventsRevision = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? ''))) === 'events.xml');
        abort_unless($eventsRevision && Storage::disk('dayz')->exists($eventsRevision->storage_path), 422, 'Nejprve importujte aktuální events.xml.');
        $eventsXml = @simplexml_load_string(Storage::disk('dayz')->get($eventsRevision->storage_path));
        $eventNames = [];
        foreach ($eventsXml?->event ?? [] as $event) {
            $eventNames[] = (string) ($event['name'] ?? '');
        }
        abort_unless(in_array($eventName, $eventNames, true), 422, 'Odpovídající event '.$eventName.' v aktuálním events.xml neexistuje.');
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
    if (in_array($data['type'], $eventBacked, true) && array_key_exists('event_nominal', $parameters)) {
        $eventsRevision = $revisions->first(fn ($revision) => $filenameOf($revision) === 'events.xml');
        if ($eventsRevision && Storage::disk('dayz')->exists($eventsRevision->storage_path)) {
            $eventValues = collect($parameters)->filter(fn ($value, $key) => str_starts_with((string) $key, 'event_'))->mapWithKeys(fn ($value, $key) => [substr($key, 6) => $value])->all();
            $eventContent = $eventsEditor->update(Storage::disk('dayz')->get($eventsRevision->storage_path), $eventName, $eventValues);
            $editor->save($project, $eventsRevision, $eventContent, 'Upraven event '.$eventName.' při přidání mapového bodu', auth()->user());
        }
    }
    if ($data['type'] !== 'animal' && in_array($data['type'], $eventTypes, true) && (array_key_exists('damage_min', $parameters) || array_key_exists('cargo_preset', $parameters) || array_key_exists('cargo_items', $parameters) || array_key_exists('hoarder', $parameters) || array_key_exists('attachments', $parameters))) {
        $spawnableRevision = $revisions->first(fn ($revision) => $filenameOf($revision) === 'cfgspawnabletypes.xml');
        if ($spawnableRevision && Storage::disk('dayz')->exists($spawnableRevision->storage_path)) {
            // iterator_to_array(..., false) is required here — collect() on a raw SimpleXMLElement
            // (which iterator_to_array defaults to preserve_keys=true for) collapses every <event>
            // sibling down to just the LAST one, because SimpleXML's iterator yields the same
            // string key (the tag name) for each match instead of a unique index.
            $eventNode = collect(iterator_to_array($eventsXml?->event ?? [], false))->first(fn ($event) => trim((string) ($event['name'] ?? '')) === trim($data['label']));
            $attachmentItems = array_values(array_filter(array_map(fn ($name) => ['name' => trim($name), 'chance' => 1], explode(',', (string) ($parameters['attachments'] ?? ''))), fn ($item) => $item['name'] !== ''));
            // A cargo_items row is a single guaranteed item; each becomes its own <cargo> group,
            // matching the one-item-per-group convention vanilla weapon-crate/heli-crash events use.
            // A "preset" reference is only used as a fallback when no specific items are listed.
            $cargoItems = [];
            if (trim((string) ($parameters['cargo_items'] ?? '')) !== '') {
                $decodedCargo = json_decode((string) $parameters['cargo_items'], true);
                foreach (is_array($decodedCargo) ? $decodedCargo : [] as $cargoItem) {
                    $itemName = trim((string) ($cargoItem['name'] ?? ''));
                    if ($itemName === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $itemName)) {
                        continue;
                    }
                    $quantmin = $cargoItem['quantmin'] ?? '';
                    $quantmax = $cargoItem['quantmax'] ?? '';
                    $chanceRaw = $cargoItem['chance'] ?? '';
                    $cargoItems[] = [
                        'name' => $itemName,
                        // Empty means "not filled in" (default 100%), NOT 0% — an empty string
                        // survives `?? 1` unharmed since the key is present, so it must be
                        // checked explicitly or every item silently gets chance=0.
                        'chance' => max(0, min(1, $chanceRaw === '' || $chanceRaw === null ? 1.0 : (float) $chanceRaw)),
                        'quantmin' => $quantmin !== '' && $quantmin !== null ? max(0, (int) $quantmin) : null,
                        'quantmax' => $quantmax !== '' && $quantmax !== null ? max(0, (int) $quantmax) : null,
                    ];
                }
            }
            $cargoGroups = $cargoItems !== []
                ? collect($cargoItems)->map(fn ($item) => ['chance' => 1, 'items' => [$item]])->all()
                : (trim((string) ($parameters['cargo_preset'] ?? '')) !== '' ? [['chance' => 1, 'preset' => trim((string) $parameters['cargo_preset'])]] : []);
            $spawnableContent = Storage::disk('dayz')->get($spawnableRevision->storage_path);
            $spawnClassnames = collect(iterator_to_array($eventNode?->children->child ?? [], false))->map(fn ($child) => trim((string) ($child['type'] ?? '')))->filter()->unique()->values();
            foreach ($spawnClassnames as $classname) {
                $spawnableContent = $spawnableEditor->update($spawnableContent, $classname, [
                    'damage_min' => $parameters['damage_min'] ?? 0,
                    'damage_max' => $parameters['damage_max'] ?? 0,
                    'hoarder' => (bool) ($parameters['hoarder'] ?? false),
                    'attachments' => $attachmentItems ? [['chance' => 1, 'items' => $attachmentItems]] : [],
                    'cargo' => $cargoGroups,
                ]);
            }
            $editor->save($project, $spawnableRevision, $spawnableContent, 'Nastavení kontejneru '.$data['label'].' při přidání mapového bodu', auth()->user());
        }
    }
    $typesAdded = [];
    if (in_array($data['type'], $eventTypes, true) && ($parameters['auto_add_types'] ?? false)) {
        $typesRevision = $revisions->first(fn ($revision) => $filenameOf($revision) === 'types.xml');
        if ($typesRevision && Storage::disk('dayz')->exists($typesRevision->storage_path)) {
            // iterator_to_array(..., false) is required here — collect() on a raw SimpleXMLElement
            // (which iterator_to_array defaults to preserve_keys=true for) collapses every <event>
            // sibling down to just the LAST one, because SimpleXML's iterator yields the same
            // string key (the tag name) for each match instead of a unique index.
            $eventNode = collect(iterator_to_array($eventsXml?->event ?? [], false))->first(fn ($event) => trim((string) ($event['name'] ?? '')) === trim($data['label']));
            $typesContent = Storage::disk('dayz')->get($typesRevision->storage_path);
            $defined = collect($typesEditor->entries($typesContent))->pluck('name')->map(fn ($name) => strtolower($name))->all();
            foreach ($eventNode?->children->child ?? [] as $child) {
                $classname = trim((string) ($child['type'] ?? ''));
                if ($classname === '' || in_array(strtolower($classname), $defined, true)) continue;
                if (array_key_exists('spawn_type_'.$classname, $parameters) && ! filter_var($parameters['spawn_type_'.$classname], FILTER_VALIDATE_BOOLEAN)) continue;
                $typesContent = $typesEditor->add($typesContent, $classname, ['nominal'=>0, 'lifetime'=>1800, 'restock'=>0, 'min'=>0, 'quantmin'=>-1, 'quantmax'=>-1, 'cost'=>100], 'other');
                $defined[] = strtolower($classname);
                $typesAdded[] = $classname;
            }
            if ($typesAdded !== []) $editor->save($project, $typesRevision, $typesContent, 'Automaticky doplněny classy eventu '.$data['label'].' do types.xml', auth()->user());
        }
    }
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number,'types_added'=>$typesAdded]);
})->middleware('auth')->name('map-editor.points.store');

Route::post('/admin/map-editor/points/update', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor, \App\Services\Revision\SpawnableTypesXmlEditor $spawnableEditor, \App\Services\Dayz\EventsXmlEditor $eventsEditor, \App\Services\Revision\ObjectSpawnerJsonEditor $objectSpawnerEditor) {
    $data = $request->validate([
        'project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','label'=>'required|string|max:120','path'=>'required|string|max:1000',
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
        'parameters.damage_min'=>'nullable|numeric|min:0|max:1','parameters.damage_max'=>'nullable|numeric|min:0|max:1','parameters.cargo_preset'=>'nullable|string|max:80','parameters.attachments'=>'nullable|string|max:2000',
        'parameters.cargo_items'=>'nullable|string|max:20000','parameters.hoarder'=>'nullable|boolean',
        'parameters.pos_y'=>'nullable|numeric|min:-1000|max:5000',
        'parameters.pitch'=>'nullable|numeric|min:-360|max:360','parameters.yaw'=>'nullable|numeric|min:-360|max:360','parameters.roll'=>'nullable|numeric|min:-360|max:360',
        'parameters.zone_type'=>'nullable|string|max:60|regex:/^[A-Za-z0-9_.-]+$/','parameters.radius'=>'nullable|numeric|min:1|max:5000',
        'parameters.smin'=>'nullable|integer|min:0|max:1000','parameters.smax'=>'nullable|integer|min:0|max:1000',
        'parameters.dmin'=>'nullable|integer|min:0|max:1000','parameters.dmax'=>'nullable|integer|min:0|max:1000',
        'parameters.name'=>['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]*$/'],
        'parameters.event_classname'=>['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]*$/'],
        'parameters.event_nominal'=>'nullable|integer|min:0|max:100000','parameters.event_min'=>'nullable|integer|min:0|max:100000','parameters.event_max'=>'nullable|integer|min:0|max:100000',
        'parameters.event_lifetime'=>'nullable|integer|min:0|max:3888000','parameters.event_restock'=>'nullable|integer|min:0|max:3888000','parameters.event_saferadius'=>'nullable|integer|min:0|max:20000','parameters.event_distanceradius'=>'nullable|integer|min:0|max:20000','parameters.event_cleanupradius'=>'nullable|integer|min:0|max:20000','parameters.event_active'=>'nullable|boolean','parameters.event_deletable'=>'nullable|boolean','parameters.event_init_random'=>'nullable|boolean','parameters.event_remove_damaged'=>'nullable|boolean','parameters.event_position'=>'nullable|in:fixed,player','parameters.event_limit'=>'nullable|in:mixed,custom,child,parent',
        'parameters.classname'=>'nullable|string|max:120','parameters.height'=>'nullable|numeric|min:-1000|max:5000','parameters.scale'=>'nullable|numeric|min:0.01|max:100','parameters.enable_ce_persistency'=>'nullable|boolean',
    ]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    $filename = strtolower(basename(str_replace('\\', '/', $data['filename'])));
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_unless(strtolower(basename($source->configurationImport?->original_filename ?? '')) === $filename, 422, 'Vybraná revize nepatří k tomuto mapovému souboru. Obnovte Mapu a zkuste to znovu.');
    $sourceContent = Storage::disk('dayz')->get($source->storage_path);
    $isObjectSpawner = str_ends_with(strtolower($data['filename']), '.json') && $objectSpawnerEditor->supports($data['filename'], $sourceContent);
    abort_if(str_ends_with(strtolower($data['filename']), '.json') && ! $isObjectSpawner, 422, 'JSON mapové body upravte ve vizuálním JSON editoru.');
    try {
        if ($isObjectSpawner) {
            $objectParameters = $data['parameters'] ?? [];
            $content = $objectSpawnerEditor->updateAt($sourceContent, $objectSpawnerEditor->indexFromPath($data['path']), [
                'name' => trim((string) ($objectParameters['classname'] ?? $data['label'])),
                'pos' => [(float) $data['new_x'], (float) ($objectParameters['height'] ?? 0), (float) $data['new_z']],
                'ypr' => [(float) ($objectParameters['yaw'] ?? 0), (float) ($objectParameters['pitch'] ?? 0), (float) ($objectParameters['roll'] ?? 0)],
                'scale' => (float) ($objectParameters['scale'] ?? 1),
                'enableCEPersistency' => (bool) ($objectParameters['enable_ce_persistency'] ?? false),
            ]);
        } else {
            $content = $mapEditor->updateCoordinates($data['filename'], $sourceContent, $data['path'], (float) $data['new_x'], (float) $data['new_z'], $data['parameters'] ?? []);
        }
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $content, 'Upraven mapový bod X/Z', auth()->user());
    $eventSettingsWarning = null;
    if ($filename === 'cfgeventspawns.xml' && collect($data['parameters'] ?? [])->keys()->contains(fn ($key) => str_starts_with((string) $key, 'event_'))) {
        // Same rationale as the cfgspawnabletypes.xml sync below: these fields belong to the
        // whole event (nominal/min/max/limit/flags…), not to this position, so a missing
        // events.xml just skips the sync with a warning rather than failing the point save.
        $eventsRevisionForSettings = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === 'events.xml');
        if (! $eventsRevisionForSettings || ! Storage::disk('dayz')->exists($eventsRevisionForSettings->storage_path)) {
            $eventSettingsWarning = 'Nastavení eventu se neuložilo — nejprve importujte aktuální events.xml.';
        } else {
            $eventValues = collect($data['parameters'])->filter(fn ($value, $key) => str_starts_with((string) $key, 'event_'))->mapWithKeys(fn ($value, $key) => [substr((string) $key, 6) => $value])->all();
            // 'event_classname' strips down to 'classname', but EventsXmlEditor::update()
            // expects 'child_classname' for its single-child rename path.
            if (array_key_exists('classname', $eventValues)) {
                $eventValues['child_classname'] = $eventValues['classname'];
                unset($eventValues['classname']);
            }
            try {
                $eventsContent = $eventsEditor->update(Storage::disk('dayz')->get($eventsRevisionForSettings->storage_path), $data['label'], $eventValues);
                $editor->save($project, $eventsRevisionForSettings, $eventsContent, 'Upraveno nastavení eventu '.$data['label'], auth()->user());
            } catch (\RuntimeException $exception) {
                $eventSettingsWarning = 'Nastavení eventu se neuložilo — '.$exception->getMessage();
            }
        }
    }
    $spawnableWarning = null;
    if ($filename === 'cfgeventspawns.xml' && (array_key_exists('damage_min', $data['parameters'] ?? []) || array_key_exists('damage_max', $data['parameters'] ?? []) || array_key_exists('cargo_preset', $data['parameters'] ?? []) || array_key_exists('cargo_items', $data['parameters'] ?? []) || array_key_exists('hoarder', $data['parameters'] ?? []) || array_key_exists('attachments', $data['parameters'] ?? []))) {
        // This block only syncs cfgspawnabletypes.xml (container contents) — a secondary,
        // best-effort step. eventFields always sends these keys (with their defaults) on every
        // save of a dynamic event point, so a hard abort here would block ordinary coordinate/
        // orientation edits whenever events.xml/cfgspawnabletypes.xml aren't uploaded yet or the
        // event has no <children> defined. The point itself was already saved above; skip
        // quietly (surfacing why) instead of failing the whole request.
        $eventsRevision = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === 'events.xml');
        $spawnableRevision = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()->first(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === 'cfgspawnabletypes.xml');
        if (! $eventsRevision || ! Storage::disk('dayz')->exists($eventsRevision->storage_path)) {
            $spawnableWarning = 'Souřadnice uloženy. Obsah kontejneru se nesynchronizoval — nejprve importujte aktuální events.xml.';
        } elseif (! $spawnableRevision || ! Storage::disk('dayz')->exists($spawnableRevision->storage_path)) {
            $spawnableWarning = 'Souřadnice uloženy. Obsah kontejneru se nesynchronizoval — nejprve importujte aktuální cfgspawnabletypes.xml.';
        } else {
            $eventXml = @simplexml_load_string(Storage::disk('dayz')->get($eventsRevision->storage_path));
            // The event may well have a correct <children> block — but if ANY part of events.xml
            // fails to parse as XML, simplexml_load_string() returns false for the WHOLE file, and
            // everything below would otherwise silently behave as "event not found". Distinguish
            // parse failure / event-not-found / no-children so the warning is actually diagnostic.
            if ($eventXml === false) {
                $spawnableWarning = 'Souřadnice uloženy. Obsah kontejneru se nesynchronizoval — events.xml se nepodařilo naparsovat jako XML (zkontrolujte formát souboru, např. přes Raw data).';
            } else {
                // Compared with trim() on both sides — a stray leading/trailing space in either
                // file's name="..." attribute (easy to introduce by hand-editing, and otherwise
                // invisible) previously made an event that visibly matches fail this strict ===
                // comparison, producing a false "event not found" warning.
                //
                // iterator_to_array(..., false) is required — collect() on a raw SimpleXMLElement
                // (which internally uses iterator_to_array with preserve_keys=true) collapses every
                // <event> sibling down to just the LAST one, because SimpleXML's iterator yields the
                // same string key (the tag name) for each match instead of a unique index. This was
                // the actual cause of "event not found despite a visibly-correct events.xml": with
                // dozens of <event> elements in a real file, only the very last one ever survived.
                $eventNode = collect(iterator_to_array($eventXml->event ?? [], false))->first(fn ($event) => trim((string) ($event['name'] ?? '')) === trim($data['label']));
                $spawnClassnames = collect(iterator_to_array($eventNode?->children->child ?? [], false))->map(fn ($child) => trim((string) ($child['type'] ?? '')))->filter()->unique()->values()->all();
                if (! $eventNode) {
                    $availableNames = collect(iterator_to_array($eventXml->event ?? [], false))->map(fn ($event) => trim((string) ($event['name'] ?? '')))->filter()->unique()->values()->all();
                    $hint = $availableNames === []
                        ? ' (v events.xml nebyl nalezen žádný event)'
                        : ' (nalezené eventy v events.xml: '.implode(', ', array_slice($availableNames, 0, 10)).(count($availableNames) > 10 ? ', …' : '').')';
                    // Names how the app is picking "the current events.xml" so a genuinely stale
                    // revision (created by some earlier in-app action, not the latest FTP import)
                    // is diagnosable from the warning alone instead of needing a follow-up
                    // database inspection.
                    $allEventsXmlRevisions = $project->revisions()->with('configurationImport')->get()->filter(fn ($revision) => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === 'events.xml')->sortBy('revision_number')->values();
                    $revisionHint = ' [použitá revize #'.$eventsRevision->revision_number.' z '.$allEventsXmlRevisions->count().', vytvořena '.$eventsRevision->created_at?->format('d.m. H:i:s').', poznámka: "'.$eventsRevision->change_summary.'"]';
                    // A full events.xml revision timeline (event count per revision) pinpoints
                    // exactly which save first shrank the file — vs. the newer-revisions check
                    // above, which only catches a *skipped* revision, not one that was picked
                    // correctly but was itself already thin when it was written.
                    $timeline = $allEventsXmlRevisions->slice(-10)->map(function ($revision) {
                        $xml = Storage::disk('dayz')->exists($revision->storage_path) ? @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path)) : false;
                        $count = $xml !== false ? count($xml->event ?? []) : null;
                        return '#'.$revision->revision_number.'='.($count === null ? 'neparsovatelné' : $count.'ev').' ('.$revision->created_at?->format('H:i:s').')';
                    })->implode(' → ');
                    $revisionHint .= ' [posledních '.min(10, $allEventsXmlRevisions->count()).' revizí events.xml (počet eventů): '.$timeline.']';
                    // If a newer revision exists that this closure's basename-match skipped over,
                    // show exactly what filename it resolved to and why — the direct way to catch
                    // a revision whose configuration_import_id link doesn't actually point at an
                    // import named events.xml (or has none), which silently drops it from every
                    // "find the current events.xml" lookup even though it holds newer content.
                    $newerRevisions = $project->revisions()->with('configurationImport')->where('revision_number', '>', $eventsRevision->revision_number)->orderBy('revision_number')->get();
                    if ($newerRevisions->isNotEmpty()) {
                        $newerHint = $newerRevisions->map(function ($revision) {
                            $resolvedName = basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path));
                            $importId = $revision->configuration_import_id ?? 'NULL';
                            return '#'.$revision->revision_number.'→"'.$resolvedName.'" (import_id='.$importId.')';
                        })->implode(', ');
                        $revisionHint .= ' [novější revize přeskočené tímto hledáním: '.$newerHint.']';
                    }
                    $spawnableWarning = 'Souřadnice uloženy. Obsah kontejneru se nesynchronizoval — event '.$data['label'].' nebyl v aktuálně nahraném events.xml nalezen (zkontrolujte, že jde o nejnovější revizi a přesnou shodu jména)'.$hint.$revisionHint;
                } elseif ($spawnClassnames === []) {
                    $spawnableWarning = 'Souřadnice uloženy. Obsah kontejneru se nesynchronizoval — event '.$data['label'].' nemá v events.xml žádnou spawnovanou child třídu (zkontrolujte <children><child type="..."/></children>).';
                } else {
                    $parameters = $data['parameters'] ?? [];
                    $attachmentItems = array_values(array_filter(array_map(fn ($name) => ['name' => trim($name), 'chance' => 1], explode(',', (string) ($parameters['attachments'] ?? ''))), fn ($item) => $item['name'] !== ''));
                    // A cargo_items row is a single guaranteed item; each becomes its own <cargo> group,
                    // matching the one-item-per-group convention vanilla weapon-crate/heli-crash events use.
                    // A "preset" reference is only used as a fallback when no specific items are listed.
                    $cargoItems = [];
                    if (trim((string) ($parameters['cargo_items'] ?? '')) !== '') {
                        $decodedCargo = json_decode((string) $parameters['cargo_items'], true);
                        foreach (is_array($decodedCargo) ? $decodedCargo : [] as $cargoItem) {
                            $itemName = trim((string) ($cargoItem['name'] ?? ''));
                            if ($itemName === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $itemName)) {
                                continue;
                            }
                            $quantmin = $cargoItem['quantmin'] ?? '';
                            $quantmax = $cargoItem['quantmax'] ?? '';
                            $chanceRaw = $cargoItem['chance'] ?? '';
                            $cargoItems[] = [
                                'name' => $itemName,
                                // Empty means "not filled in" (default 100%), NOT 0% — an empty
                                // string survives `?? 1` unharmed since the key is present, so
                                // it must be checked explicitly or items get chance=0.
                                'chance' => max(0, min(1, $chanceRaw === '' || $chanceRaw === null ? 1.0 : (float) $chanceRaw)),
                                'quantmin' => $quantmin !== '' && $quantmin !== null ? max(0, (int) $quantmin) : null,
                                'quantmax' => $quantmax !== '' && $quantmax !== null ? max(0, (int) $quantmax) : null,
                            ];
                        }
                    }
                    $cargoGroups = $cargoItems !== []
                        ? collect($cargoItems)->map(fn ($item) => ['chance' => 1, 'items' => [$item]])->all()
                        : (trim((string) ($parameters['cargo_preset'] ?? '')) !== '' ? [['chance' => 1, 'preset' => trim((string) $parameters['cargo_preset'])]] : []);
                    $spawnableContent = Storage::disk('dayz')->get($spawnableRevision->storage_path);
                    foreach ($spawnClassnames as $classname) {
                        $spawnableContent = $spawnableEditor->update($spawnableContent, $classname, [
                            'damage_min' => $parameters['damage_min'] ?? 0,
                            'damage_max' => $parameters['damage_max'] ?? 0,
                            'hoarder' => (bool) ($parameters['hoarder'] ?? false),
                            'attachments' => $attachmentItems ? [['chance' => 1, 'items' => $attachmentItems]] : [],
                            'cargo' => $cargoGroups,
                        ]);
                    }
                    $editor->save($project, $spawnableRevision, $spawnableContent, 'Upraven obsah kontejneru '.$data['label'], auth()->user());
                }
            }
        }
    }
    $combinedWarning = trim(implode(' ', array_filter([$eventSettingsWarning, $spawnableWarning]))) ?: null;

    return response()->json(['ok'=>true,'revision'=>$saved->revision_number,'revision_id'=>$saved->id,'warning'=>$combinedWarning]);
})->middleware('auth')->name('map-editor.points.update');

Route::post('/admin/map-editor/points/delete', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor, \App\Services\Revision\ObjectSpawnerJsonEditor $objectSpawnerEditor) {
    $data = $request->validate(['project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','path'=>'required|string|max:1000','x'=>'required|numeric','z'=>'required|numeric']);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_unless(strtolower(basename($source->configurationImport?->original_filename ?? '')) === strtolower(basename($data['filename'])), 422, 'Vybraná revize nepatří k tomuto mapovému souboru. Obnovte Mapu a zkuste to znovu.');
    $sourceContent = Storage::disk('dayz')->get($source->storage_path);
    $isObjectSpawner = str_ends_with(strtolower($data['filename']), '.json') && $objectSpawnerEditor->supports($data['filename'], $sourceContent);
    abort_if(str_ends_with(strtolower($data['filename']), '.json') && ! $isObjectSpawner, 422, 'JSON mapové body odstraňte ve vizuálním JSON editoru.');
    try {
        $content = $isObjectSpawner
            ? $objectSpawnerEditor->removeAt($sourceContent, $objectSpawnerEditor->indexFromPath($data['path']))
            : $mapEditor->delete($sourceContent, $data['path']);
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $content, 'Odstraněn mapový bod', auth()->user());
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number,'revision_id'=>$saved->id]);
})->middleware('auth')->name('map-editor.points.delete');

Route::post('/admin/map-editor/points/duplicate', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Revision\ObjectSpawnerJsonEditor $objectSpawnerEditor) {
    $data = $request->validate(['project_id'=>'required|integer','revision_id'=>'required|integer','filename'=>'required|string','path'=>'required|string|max:1000']);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    abort_unless($source && Storage::disk('dayz')->exists($source->storage_path), 422);
    abort_unless(strtolower(basename($source->configurationImport?->original_filename ?? '')) === strtolower(basename($data['filename'])), 422, 'Vybraná revize nepatří k tomuto mapovému souboru. Obnovte Mapu a zkuste to znovu.');
    $sourceContent = Storage::disk('dayz')->get($source->storage_path);
    abort_unless($objectSpawnerEditor->supports($data['filename'], $sourceContent), 422, 'Duplikace je zatím podporována jen pro Object Spawner objekty.');
    try {
        $content = $objectSpawnerEditor->duplicateAt($sourceContent, $objectSpawnerEditor->indexFromPath($data['path']));
    } catch (\RuntimeException $exception) {
        abort(422, $exception->getMessage());
    }
    $saved = $editor->save($project, $source, $content, 'Zdvojen objekt', auth()->user());
    return response()->json(['ok'=>true,'revision'=>$saved->revision_number,'revision_id'=>$saved->id]);
})->middleware('auth')->name('map-editor.points.duplicate');

Route::post('/admin/map-editor/points/bulk-delete', function (Request $request, \App\Services\Revision\ConfigurationRevisionEditor $editor, \App\Services\Dayz\MapConfigurationEditor $mapEditor, \App\Services\Revision\ObjectSpawnerJsonEditor $objectSpawnerEditor) {
    $data = $request->validate([
        'project_id' => ['required', 'integer'],
        'revision_id' => ['required', 'integer'],
        'filename' => ['required', 'string', 'max:160'],
        'scopes' => ['required', 'array', 'min:1'],
        'scopes.*' => ['string', 'max:220'],
    ]);
    $project = Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->findOrFail($data['project_id']);
    $source = $project->revisions()->with('configurationImport')->findOrFail($data['revision_id']);
    $resolvedFilename = strtolower(basename(str_replace('\\', '/', $source->configurationImport?->original_filename ?? '')));
    abort_unless($resolvedFilename === strtolower(basename($data['filename'])), 422, 'Revize nepatří vybranému souboru.');
    abort_unless(Storage::disk('dayz')->exists($source->storage_path), 422, 'Zdrojová revize už není dostupná.');
    $sourceContent = Storage::disk('dayz')->get($source->storage_path);

    if ($objectSpawnerEditor->supports($data['filename'], $sourceContent)) {
        try {
            $indexes = array_map(fn (string $path) => $objectSpawnerEditor->indexFromPath($path), $data['scopes']);
            $content = $objectSpawnerEditor->removeMany($sourceContent, $indexes);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }
        $saved = $editor->save($project, $source, $content, 'Hromadně odstraněno '.count($indexes).' objektů', auth()->user());

        return response()->json(['ok' => true, 'deleted' => count($indexes), 'revision' => $saved->revision_number]);
    }

    abort_unless(in_array($resolvedFilename, ['cfgeventspawns.xml', 'cfgplayerspawnpoints.xml'], true), 422, 'Hromadné mazání je podporováno jen pro cfgeventspawns.xml, cfgplayerspawnpoints.xml a Object Spawner soubory.');
    try {
        $result = $mapEditor->deleteScopes($data['filename'], $sourceContent, $data['scopes']);
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
