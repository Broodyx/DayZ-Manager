<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Services\Dayz\ServerFileLayout;
use Tests\TestCase;

class ServerFileLayoutTest extends TestCase
{
    public function test_pc_nests_db_and_root_files_under_the_official_mission_folder(): void
    {
        $layout = new ServerFileLayout;
        $project = new Project(['platform' => 'steam', 'map' => 'chernarusplus']);

        $this->assertSame('dayzOffline.chernarusplus/db/types.xml', $layout->relativePath('types.xml', $project));
        $this->assertSame('dayzOffline.chernarusplus/mapgroupproto.xml', $layout->relativePath('mapgroupproto.xml', $project));
        $this->assertSame('dayzOffline.chernarusplus/env/wolf_territories.xml', $layout->relativePath('wolf_territories.xml', $project));
        $this->assertSame('serverDZ.cfg', $layout->relativePath('serverDZ.cfg', $project));
    }

    public function test_console_platforms_stay_flat_with_only_db_and_env_subfolders(): void
    {
        $layout = new ServerFileLayout;
        $project = new Project(['platform' => 'playstation', 'map' => 'chernarusplus']);

        $this->assertSame('db/types.xml', $layout->relativePath('types.xml', $project));
        $this->assertSame('mapgroupproto.xml', $layout->relativePath('mapgroupproto.xml', $project));
        $this->assertSame('env/wolf_territories.xml', $layout->relativePath('wolf_territories.xml', $project));
    }

    public function test_community_maps_have_no_known_mission_folder_and_stay_flat_even_on_pc(): void
    {
        $layout = new ServerFileLayout;
        $project = new Project(['platform' => 'steam', 'map' => 'namalsk']);

        $this->assertSame('db/types.xml', $layout->relativePath('types.xml', $project));
        $this->assertNull($layout->missionFolderFor($project));
    }

    public function test_display_name_can_differ_from_the_classification_name(): void
    {
        $layout = new ServerFileLayout;

        $this->assertSame('db/types.xml.2', $layout->relativePathForMissionFolder('types.xml', null, 'types.xml.2'));
    }

    public function test_unrecognised_file_keeps_its_original_custom_subfolder(): void
    {
        $layout = new ServerFileLayout;

        $this->assertSame('custom/startovni-vybava.json', $layout->relativePathForMissionFolder('custom/startovni-vybava.json', null));
        $this->assertSame(
            'dayzOffline.chernarusplus/custom/startovni-vybava.json',
            $layout->relativePathForMissionFolder('custom/startovni-vybava.json', 'dayzOffline.chernarusplus'),
        );
    }

    public function test_subfolder_qualified_db_and_env_files_do_not_double_nest(): void
    {
        $layout = new ServerFileLayout;

        $this->assertSame('db/types.xml', $layout->relativePathForMissionFolder('db/types.xml', null));
        $this->assertSame('env/wolf_territories.xml', $layout->relativePathForMissionFolder('env/wolf_territories.xml', null));
    }

    public function test_display_name_dedup_suffix_keeps_the_original_custom_subfolder(): void
    {
        $layout = new ServerFileLayout;

        $this->assertSame(
            'custom/startovni-vybava.2.json',
            $layout->relativePathForMissionFolder('custom/startovni-vybava.json', null, 'startovni-vybava.2.json'),
        );
    }
}
