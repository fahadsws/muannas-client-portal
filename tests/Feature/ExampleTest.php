<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_goes_to_the_portal_and_the_login_page_loads(): void
    {
        $this->get('/')->assertRedirect('/portal');
        $this->get('/login')->assertOk()->assertSee('Forgot password?');
    }
}
