<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Koodilab\Models\Expedition;
use Koodilab\Models\Star;
use Koodilab\Models\Unit;
use Koodilab\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ExpeditionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp()
    {
        parent::setUp();

        $user = factory(User::class)->create([
            'started_at' => Carbon::now(),
        ]);

        Passport::actingAs($user);
    }

    public function testIndex()
    {
        $star = factory(Star::class)->create([
            'name' => 'Voyager',
        ]);

        $unit = factory(Unit::class)->create([
            'name' => [
                'en' => 'Fighter',
            ],
            'description' => [
                'en' => 'english description',
            ],
        ]);

        $expedition = factory(Expedition::class)->create([
            'star_id' => $star->id,
            'user_id' => auth()->user()->id,
            'solarion' => 5,
            'experience' => 2,
            'ended_at' => Carbon::now(),
        ]);

        $expedition->units()->attach($unit, [
            'quantity' => 5,
        ]);

        $this->getJson('/api/expedition')->assertStatus(200)
            ->assertJsonStructure([
                'units' => [
                    [
                        'id',
                        'name',
                        'description',
                        'quantity',
                    ],
                ],
                'expeditions' => [
                    [
                        'id',
                        'star',
                        'solarion',
                        'experience',
                        'remaining',
                        'units' => [
                            [
                                'id',
                                'name',
                                'description',
                                'quantity',
                            ],
                        ],
                    ],
                ],
            ])->assertJson([
                'units' => [
                    [
                        'id' => 1,
                        'name' => 'Fighter',
                        'description' => 'english description',
                        'quantity' => 0,
                    ],
                ],
                'expeditions' => [
                    [
                        'id' => 1,
                        'star' => 'Voyager',
                        'solarion' => 5,
                        'experience' => 2,
                        'remaining' => 0,
                        'units' => [
                            [
                                'id' => 1,
                                'name' => 'Fighter',
                                'description' => 'english description',
                                'quantity' => 5,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function testStore()
    {
        $user = auth()->user();
        $star = factory(Expedition::class)->create();

        $expedition = factory(Expedition::class)->create([
            'star_id' => $star->id,
            'user_id' => $user->id,
        ]);

        $this->post('/api/expedition/10')
            ->assertStatus(404);

        $this->post('/api/expedition/not-id')
            ->assertStatus(404);

        $this->assertDatabaseHas('expeditions', [
            'id' => $expedition->id,
        ]);

        $this->post("/api/expedition/{$expedition->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('expeditions', [
            'id' => $expedition->id,
        ]);

        $this->assertDatabaseHas('expedition_logs', [
            'star_id' => $star->id,
            'user_id' => $user->id,
        ]);
    }
}