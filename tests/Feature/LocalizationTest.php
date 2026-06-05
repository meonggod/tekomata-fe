<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_homepage_renders_in_indonesian_by_default(): void
    {
        config(['app.locale' => 'id']);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('messages.landing.hero.title', [], 'id'))
            ->assertSee(__('messages.landing.features.heading', [], 'id'));
    }

    public function test_switching_locale_to_english_persists_and_renders(): void
    {
        $this->get(route('locale.switch', 'en'))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee(__('messages.landing.hero.title', [], 'en'));
    }

    public function test_unknown_locale_is_ignored(): void
    {
        $this->get(route('locale.switch', 'fr'));

        $this->assertNull(session('locale'));
    }
}
