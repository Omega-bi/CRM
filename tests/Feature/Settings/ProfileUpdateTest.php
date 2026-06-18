<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('profile locale can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('locale', 'ru')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->locale)->toBe('ru');
});

test('profile photo can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profilePhoto', UploadedFile::fake()->image('avatar.jpg'))
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('old profile photo is deleted when replaced', function () {
    Storage::fake('public');

    $oldPath = 'profile-photos/old.jpg';
    Storage::disk('public')->put($oldPath, 'old');

    $user = User::factory()->create([
        'profile_photo_path' => $oldPath,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profilePhoto', UploadedFile::fake()->image('new-avatar.jpg'))
        ->assertHasNoErrors();

    $user->refresh();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('profile phone numbers can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('phoneNumbers', ['87011234567', '+7 702 765 43 21'])
        ->call('savePhoneNumbers')
        ->assertHasNoErrors();

    expect($user->refresh()->phone_numbers)->toBe([
        '+7 (701) 123-45-67',
        '+7 (702) 765-43-21',
    ]);
});

test('profile phone numbers must match supported format', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('phoneNumbers', ['123'])
        ->call('savePhoneNumbers')
        ->assertHasErrors(['phoneNumbers.0']);

    expect($user->refresh()->phone_numbers)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
