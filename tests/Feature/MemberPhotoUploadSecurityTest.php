<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Rules\Base64Image;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Memastikan foto webcam (base64) hanya bisa disimpan jika benar-benar gambar
 * JPG/PNG/WEBP, dan ekstensi file ditentukan oleh server.
 */
class MemberPhotoUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();

        Storage::fake('public');
    }

    public function test_valid_jpeg_webcam_snapshot_is_stored_as_jpg(): void
    {
        $response = $this->actingAs($this->cs)->post(route('members.store'), $this->memberPayload([
            'photo_data' => $this->makeDataUri('image/jpeg', $this->makeImageBytes('jpeg')),
        ]));

        $member = Member::where('phone', '081234500001')->firstOrFail();
        $response->assertRedirect(route('members.show', $member));

        $this->assertStringEndsWith('.jpg', $member->photo_url);
        Storage::disk('public')->assertExists($member->photo_url);
    }

    public function test_file_extension_comes_from_image_content_not_from_user_input(): void
    {
        // Isinya PNG, tapi user mengaku "image/jpeg". Server harus menyimpan sebagai .png.
        $this->actingAs($this->cs)->post(route('members.store'), $this->memberPayload([
            'photo_data' => $this->makeDataUri('image/jpeg', $this->makeImageBytes('png')),
        ]));

        $member = Member::where('phone', '081234500001')->firstOrFail();
        $this->assertStringEndsWith('.png', $member->photo_url);
    }

    public function test_non_image_content_is_rejected_and_nothing_is_stored(): void
    {
        $response = $this->actingAs($this->cs)->post(route('members.store'), $this->memberPayload([
            'photo_data' => $this->makeDataUri('image/php', 'bukan gambar, hanya teks biasa'),
        ]));

        $response->assertSessionHasErrors('photo_data');
        $this->assertDatabaseMissing('members', ['phone' => '081234500001']);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_image_type_outside_the_whitelist_is_rejected(): void
    {
        // GIF memang gambar asli, tapi tidak ada di daftar yang diizinkan.
        $response = $this->actingAs($this->cs)->post(route('members.store'), $this->memberPayload([
            'photo_data' => $this->makeDataUri('image/gif', $this->makeImageBytes('gif')),
        ]));

        $response->assertSessionHasErrors('photo_data');
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_oversized_snapshot_is_rejected(): void
    {
        $tooLongText = 'data:image/jpeg;base64,'.str_repeat('A', Base64Image::MAX_ENCODED_LENGTH);

        $response = $this->actingAs($this->cs)->post(route('members.store'), $this->memberPayload([
            'photo_data' => $tooLongText,
        ]));

        $response->assertSessionHasErrors('photo_data');
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_updating_member_with_non_image_snapshot_is_rejected(): void
    {
        $member = Member::factory()->create(['photo_url' => null]);

        $response = $this->actingAs($this->cs)->put(route('members.update', $member), [
            'full_name' => $member->full_name,
            'phone' => $member->phone,
            'gender' => 'male',
            'status' => 'active',
            'photo_data' => $this->makeDataUri('image/jpeg', 'bukan gambar'),
        ]);

        $response->assertSessionHasErrors('photo_data');
        $this->assertNull($member->fresh()->photo_url);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    /**
     * Data member minimal yang valid untuk endpoint pendaftaran.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function memberPayload(array $overrides = []): array
    {
        return [
            'full_name' => 'Rina Wulandari',
            'phone' => '081234500001',
            'gender' => 'female',
            ...$overrides,
        ];
    }

    /**
     * Bungkus isi file menjadi teks "data:<mime>;base64,<isi>" seperti hasil webcam.
     */
    private function makeDataUri(string $declaredMimeType, string $contents): string
    {
        return 'data:'.$declaredMimeType.';base64,'.base64_encode($contents);
    }

    /**
     * Buat gambar kecil 2x2 piksel dalam format tertentu (jpeg, png, atau gif).
     */
    private function makeImageBytes(string $format): string
    {
        $image = imagecreatetruecolor(2, 2);

        ob_start();
        match ($format) {
            'jpeg' => imagejpeg($image),
            'png' => imagepng($image),
            'gif' => imagegif($image),
        };
        $bytes = ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }
}
