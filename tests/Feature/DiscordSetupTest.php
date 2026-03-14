<?php

use App\Models\User;

test('discord setup prefills invite url for selected guild', function () {
    config()->set('services.discord.application_id', '1480084741226106880');
    config()->set('services.discord.bot_invite_permissions', '274878221376');

    $user = User::factory()->create([
        'selected_guild_id' => '1459102944032194676',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => '1459102944032194676',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('discord.setup'));

    $response
        ->assertOk()
        ->assertSee('guild_id=1459102944032194676', false)
        ->assertSee('disable_guild_select=true', false)
        ->assertSee('integration_type=0', false);
});
