<?php

it('renders the public landing page', function (): void {
    $this->withoutVite();

    $response = $this->get(route('landing'));

    $response
        ->assertSuccessful()
        ->assertSee('Trust-first cooperative operations')
        ->assertSee('Open Admin Portal')
        ->assertSee('Marketplace Preview')
        ->assertSee('Admin sign in');
});
