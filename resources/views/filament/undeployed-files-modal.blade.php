@php $files = $project->undeployedFileRevisions(); @endphp
@if (count($files))
    <div class="dz-whitelist-list">
        @foreach ($files as $file)
            <div class="dz-whitelist-row">
                <span class="dz-whitelist-row-id"><code>{{ $file['filename'] }}</code><span class="dz-whitelist-comment">revize #{{ $file['revision_number'] }}</span></span>
                <a class="dz-secondary" href="{{ route('configuration-revision.download', ['project' => $project->id, 'revision' => $file['revision_id']]) }}">Stáhnout</a>
            </div>
        @endforeach
    </div>
@else
    <p class="dz-muted">Všechny soubory jsou nasazené — poslední revize každého souboru už byla aspoň jednou stažena.</p>
@endif
