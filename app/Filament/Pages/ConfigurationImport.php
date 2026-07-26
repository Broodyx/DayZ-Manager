<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Import\ConfigurationImporter;
use App\Services\Dayz\ConfigurationCatalog;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConfigurationImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.configuration-import';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $projectId = request()->integer('project');
        $project = $projectId ? Project::where('user_id', auth()->id())->find($projectId) : null;
        $this->form->fill([
            'area' => request()->string('area')->toString() ?: null,
            'project_id' => $project?->id,
            'platform' => $project?->platform !== 'unknown' ? $project?->platform : null,
        ]);
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('area')->label('Co chcete editovat?')->options(fn (): array => app(ConfigurationCatalog::class)->options())->required(),
            Select::make('project_id')->label('Server')->options(fn (): array => Project::where('user_id', auth()->id())->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
            Select::make('platform')->label('Platforma')->options(['playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam'])->required(),
            FileUpload::make('file')->label('Soubor konfigurace')
                ->helperText(request()->query('expected') ? 'Očekávaný soubor: '.request()->query('expected') : 'Nahrajte XML, JSON, CFG nebo TXT konfiguraci.')
                ->storeFiles(false)->required(),
            TextInput::make('summary')->label('Poznámka k revizi')->maxLength(255),
        ];
    }

    public function import(ConfigurationImporter $importer): void
    {
        $state = $this->form->getState();
        $project = Project::where('user_id', auth()->id())->findOrFail($state['project_id']);
        if (! ($state['file'] ?? null) instanceof TemporaryUploadedFile) {
            Notification::make()->danger()->title('Vyberte soubor')->send();

            return;
        }
        $result = $importer->import($project, $state['file'], auth()->user());
        $project->update(['platform' => $state['platform'], 'platform_confidence' => 100]);
        Notification::make()->success()->title('Konfigurace importována')->body($result->original_filename)->send();
        $revision = $result->revisions()->latest('revision_number')->first();
        $destination = ($state['area'] ?? null) === 'map'
            ? '/admin/map-editor?project='.$project->id
            : '/admin/projects/'.$project->id.'/configuration?revision='.$revision?->id;
        $this->redirect($destination);
    }
}
