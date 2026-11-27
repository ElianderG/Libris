<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_photo_is_stored_square_and_becomes_the_default(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatars.store'), [
                'photo' => UploadedFile::fake()->image('foto.png', 640, 400),
            ])
            ->assertRedirect(route('profile.edit'));

        $avatar = UserAvatar::query()->first();

        $this->assertNotNull($avatar);
        $this->assertSame($avatar->id, $user->fresh()->avatar_id);
        $this->assertMatchesRegularExpression('#^avatars/'.$user->id.'/[0-9a-f-]{36}\.jpg$#', $avatar->path);
        Storage::disk('public')->assertExists($avatar->path);
        $this->assertStringStartsWith("\xFF\xD8", (string) Storage::disk('public')->get($avatar->path));
        $this->assertSame('/avatars/'.$avatar->id, $avatar->url());

        $this->get($avatar->url())
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_a_jpeg_is_stored_without_reencoding(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUQEhIVFhUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGhAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAABBQEBAAAAAAAAAAAAAAADAAIEBQYBB//EABQBAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AvoAA/9k=', true);

        $this->assertIsString($jpeg);

        $this->actingAs($user)
            ->post(route('profile.avatars.store'), [
                'photo' => UploadedFile::fake()->createWithContent('foto.jpg', $jpeg, 'image/jpeg'),
            ])
            ->assertRedirect(route('profile.edit'));

        $avatar = UserAvatar::query()->firstOrFail();
        $this->assertSame($jpeg, Storage::disk('public')->get($avatar->path));
    }

    public function test_a_second_photo_does_not_replace_the_default_until_chosen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('foto.jpg', 300, 300);

        $this->actingAs($user)->post(route('profile.avatars.store'), ['photo' => $photo]);
        $first = $user->fresh()->avatar_id;

        $this->actingAs($user)->post(route('profile.avatars.store'), [
            'photo' => UploadedFile::fake()->image('outra.jpg', 300, 300),
        ]);

        $this->assertSame($first, $user->fresh()->avatar_id);
        $this->assertSame(2, UserAvatar::query()->count());

        $second = UserAvatar::query()->where('id', '!=', $first)->first();
        $this->assertNotNull($second);

        $this->actingAs($user)
            ->patch(route('profile.avatars.update', $second))
            ->assertRedirect(route('profile.edit'));

        $this->assertSame($second->id, $user->fresh()->avatar_id);
    }

    public function test_the_owner_can_delete_a_photo_and_another_user_cannot(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatars.store'), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $avatar = UserAvatar::query()->firstOrFail();

        $this->actingAs($other)
            ->delete(route('profile.avatars.destroy', $avatar))
            ->assertNotFound();

        Storage::disk('public')->assertExists($avatar->path);

        $this->actingAs($user)
            ->delete(route('profile.avatars.destroy', $avatar))
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->avatar_id);
        $this->assertDatabaseMissing('user_avatars', ['id' => $avatar->id]);
        Storage::disk('public')->assertMissing($avatar->path);
    }

    public function test_a_text_file_is_refused_and_the_ninth_photo_is_refused(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.avatars.store'), [
                'photo' => UploadedFile::fake()->create('nota.txt', 20, 'text/plain'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('photo');

        for ($i = 0; $i < UserAvatar::MAX_PER_USER; $i++) {
            $this->actingAs($user)->post(route('profile.avatars.store'), [
                'photo' => UploadedFile::fake()->image("foto-{$i}.jpg", 200, 200),
            ])->assertRedirect(route('profile.edit'));
        }

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.avatars.store'), [
                'photo' => UploadedFile::fake()->image('extra.jpg', 200, 200),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('photo');

        $this->assertSame(UserAvatar::MAX_PER_USER, UserAvatar::query()->count());
    }

    public function test_deleting_the_account_removes_the_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatars.store'), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $path = UserAvatar::query()->firstOrFail()->path;
        $user->delete();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('user_avatars', ['path' => $path]);
    }

    public function test_a_guest_cannot_upload(): void
    {
        $this->post(route('profile.avatars.store'), [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect(route('login'));
    }
}
