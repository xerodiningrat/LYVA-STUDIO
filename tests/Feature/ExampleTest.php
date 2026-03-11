<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('LYVA Studio')
        ->assertSee('Rancang')
        ->assertSee('Bot Discord')
        ->assertSee('Sekelas Produk');
});
