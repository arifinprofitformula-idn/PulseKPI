<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login page with branding', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Selamat datang kembali');
});

it('renders the forgot password page with branding', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Reset password');
});

it('renders the reset password page with branding', function () {
    $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'user@example.com']))
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Buat password baru');
});

it('renders the confirm password page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Konfirmasi password');
});

it('renders the verify email page for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Verifikasi email Anda');
});
