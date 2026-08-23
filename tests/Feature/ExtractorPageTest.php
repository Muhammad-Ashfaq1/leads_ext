<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExtractorPageTest extends TestCase
{
    public function test_page_renders_awt_phone_extractor(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AWT Phone')
            ->assertSee('Lead Extractor')
            ->assertSee('What leads do you want to find?')
            ->assertSee('Start Extraction')
            ->assertSee('Extraction Status')
            ->assertSee('Leads Found')
            ->assertSee('id="leadsGrid"')
            ->assertDontSee('Login');
    }
}
