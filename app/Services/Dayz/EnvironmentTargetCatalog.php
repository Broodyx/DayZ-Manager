<?php

namespace App\Services\Dayz;

use App\Services\Revision\EnvironmentXmlEditor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Species → *_territories.xml catalog used by the map editor's "add zone" flow. Reads the
 * project's cfgenvironment.xml when it's been imported (so custom/modded species and the
 * previously-missing AnimalGoat show up automatically); falls back to a fixed list of the
 * common vanilla species for projects that only imported territory files directly.
 */
final readonly class EnvironmentTargetCatalog
{
    /** @var list<array{name:string,label:string,file:string,type:string,is_infected:bool}> */
    private const FALLBACK = [
        ['name' => 'AnimalBear', 'label' => 'AnimalBear · bear_territories.xml', 'file' => 'bear_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalCow', 'label' => 'AnimalCow · cattle_territories.xml', 'file' => 'cattle_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalDeer', 'label' => 'AnimalDeer · red_deer_territories.xml', 'file' => 'red_deer_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalRoeDeer', 'label' => 'AnimalRoeDeer · roe_deer_territories.xml', 'file' => 'roe_deer_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalWolf', 'label' => 'AnimalWolf · wolf_territories.xml', 'file' => 'wolf_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalWildBoar', 'label' => 'AnimalWildBoar · wild_boar_territories.xml', 'file' => 'wild_boar_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalSheep', 'label' => 'AnimalSheep · sheep_goat_territories.xml', 'file' => 'sheep_goat_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalGoat', 'label' => 'AnimalGoat · sheep_goat_territories.xml', 'file' => 'sheep_goat_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalPig', 'label' => 'AnimalPig · pig_territories.xml', 'file' => 'pig_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'AnimalFox', 'label' => 'AnimalFox · fox_territories.xml', 'file' => 'fox_territories.xml', 'type' => 'Ambient', 'is_infected' => false],
        ['name' => 'AnimalHare', 'label' => 'AnimalHare · hare_territories.xml', 'file' => 'hare_territories.xml', 'type' => 'Ambient', 'is_infected' => false],
        ['name' => 'AnimalHen', 'label' => 'AnimalHen · hen_territories.xml', 'file' => 'hen_territories.xml', 'type' => 'Ambient', 'is_infected' => false],
        ['name' => 'AnimalDomestic', 'label' => 'AnimalDomestic · domestic_animals_territories.xml', 'file' => 'domestic_animals_territories.xml', 'type' => 'Herd', 'is_infected' => false],
        ['name' => 'ZombieTest', 'label' => 'ZombieTest · zombie_territories.xml', 'file' => 'zombie_territories.xml', 'type' => 'Herd', 'is_infected' => true],
    ];

    public function __construct(private EnvironmentXmlEditor $editor) {}

    /**
     * @param  Collection<int, mixed>  $revisions  latest revisions for the project
     * @param  callable(mixed): string  $filenameOf  resolves a revision to its lowercase basename
     * @return list<array{name:string,label:string,file:string,type:string,is_infected:bool}>
     */
    public function targets(Collection $revisions, callable $filenameOf): array
    {
        $environmentRevision = $revisions->first(fn ($revision) => $filenameOf($revision) === 'cfgenvironment.xml');
        if ($environmentRevision && Storage::disk('dayz')->exists($environmentRevision->storage_path)) {
            $targets = $this->editor->territoryTargets(Storage::disk('dayz')->get($environmentRevision->storage_path));
            if ($targets !== []) {
                return $targets;
            }
        }

        return self::FALLBACK;
    }
}
