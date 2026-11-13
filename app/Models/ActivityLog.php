<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Log Aktivitas staf (issue #28): "siapa melakukan apa, kapan, dari mana".
 *
 * CARA MEMAKAI (cukup 1 baris di controller, SETELAH aksi berhasil):
 *
 *     ActivityLog::record('member.created', "Menambahkan member {$member->full_name}", $member);
 *
 * Untuk aksi "ubah data", kirim juga perubahannya (nilai sebelum -> sesudah):
 *
 *     $member->update($data);
 *     ActivityLog::record('member.updated', '...', $member, ActivityLog::changesOf($member));
 *
 * Aturan:
 * - Panggil hanya jika aksi BERHASIL. Di dalam DB::transaction, panggil di dalam
 *   transaksi yang sama supaya aksi yang dibatalkan (rollback) tidak meninggalkan log.
 * - Log tidak pernah diubah / dihapus (tidak ada route edit / hapus).
 * - Data rahasia (password, PIN, foto base64) otomatis dibuang dari "properties".
 */
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * Tabel ini hanya punya created_at (tidak ada updated_at).
     */
    public const UPDATED_AT = null;

    /**
     * Nama field yang TIDAK BOLEH ikut tersimpan di log.
     *
     * @var list<string>
     */
    public const SECRET_KEYS = [
        'password',
        'password_confirmation',
        'supervisor_pin',
        'remember_token',
        'portal_password',
        'photo',
        'photo_base64',
        'snapshot',
    ];

    /**
     * Field yang tidak perlu dicatat sebagai "perubahan" (berubah otomatis).
     *
     * @var list<string>
     */
    private const IGNORED_CHANGE_KEYS = ['updated_at', 'created_at'];

    /**
     * Area / kelompok aksi. Kunci = awalan "action" (sebelum titik), nilai = label.
     * Dipakai untuk filter & warna badge di halaman Log Aktivitas.
     *
     * @var array<string, string>
     */
    public const AREAS = [
        'auth' => 'Login & Logout',
        'member' => 'Member',
        'subscription' => 'Langganan',
        'package' => 'Paket',
        'product' => 'Produk',
        'pos' => 'Kasir POS',
        'pt' => 'Personal Trainer',
        'report' => 'Laporan',
        'user' => 'Manajemen User',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Catat 1 aktivitas. User, role, IP, dan browser diisi otomatis dari request saat ini.
     *
     * @param  string  $action  Kunci aksi "area.aksi", contoh: "product.updated"
     * @param  string  $description  Kalimat Bahasa Indonesia untuk dibaca admin
     * @param  Model|null  $subject  Data yang diubah (opsional)
     * @param  array<string, mixed>  $properties  Data tambahan (opsional)
     * @param  User|null  $actor  Pelaku. Kosong = user yang sedang login.
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): self {
        $actor ??= Auth::guard('web')->user();
        $request = request();

        return self::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'user_role' => $actor?->getRoleNames()->first(),
            'action' => $action,
            'description' => mb_substr($description, 0, 500),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => self::withoutSecrets($properties) ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
        ]);
    }

    /**
     * Ambil perubahan dari save() / update() TERAKHIR sebuah model:
     * ['old' => nilai sebelum, 'new' => nilai sesudah]. Field rahasia dibuang.
     *
     * Panggil SETELAH $model->save() / $model->update(...).
     *
     * @return array{old?: array<string, mixed>, new?: array<string, mixed>}
     */
    public static function changesOf(Model $model): array
    {
        $new = Arr::except($model->getChanges(), [...self::IGNORED_CHANGE_KEYS, ...self::SECRET_KEYS]);

        if ($new === []) {
            return [];
        }

        $old = Arr::only($model->getPrevious(), array_keys($new));

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Buang field rahasia dari data (termasuk yang ada di dalam "old" / "new").
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function withoutSecrets(array $data): array
    {
        $clean = Arr::except($data, self::SECRET_KEYS);

        foreach ($clean as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::withoutSecrets($value);
            }
        }

        return $clean;
    }

    /**
     * Area dari action, contoh: "pos.voided" -> "pos".
     */
    public function area(): string
    {
        return explode('.', $this->action)[0];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
