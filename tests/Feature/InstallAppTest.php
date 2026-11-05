<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The "install the app" window explains each phone, including Safari on iOS 26 (Share moved into the ⋯ menu). */
class InstallAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_iphone_steps_follow_the_current_safari(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('data-platform="ios"', false)
            ->assertSee('data-platform="ios-browser"', false)
            ->assertSee('بجانب شريط العنوان، ثم «مشاركة»', false)
            ->assertSee('اترك «فتح كتطبيق ويب» مفعّلاً')
            ->assertSee('افتح الموقع في Safari واضغط «تثبيت التطبيق» من هناك.');
    }
}
