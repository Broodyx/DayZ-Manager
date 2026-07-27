<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashStatusRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_flashed_status_and_warning_messages_are_rendered_on_the_next_page(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Flash test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->actingAs($user)
            ->withSession(['status' => '2× importováno: types.xml, events.xml', 'status_warning' => '1× se nepodařilo importovat: bad.exe'])
            ->get('/admin/configuration-wizard?project='.$project->id)
            ->assertOk()
            ->assertSee('2× importováno: types.xml, events.xml')
            ->assertSee('1× se nepodařilo importovat: bad.exe');
    }
}
