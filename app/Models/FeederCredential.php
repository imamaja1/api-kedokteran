<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class FeederCredential extends Model
{
    protected $table = 'feeder_credentials';

    protected $fillable = [
        'key_name',
        'key_value',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * Enkripsi key_value sebelum disimpan ke basis data (AES-256-CBC).
     */
    public function setKeyValueAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['key_value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['key_value'] = '';
        }
    }

    /**
     * Dekripsi key_value saat diambil dari basis data.
     */
    public function getKeyValueAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Relasi ke pembuat record (User).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengubah record (User).
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ambil nilai terdekripsi untuk kunci tertentu.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $item = static::where('key_name', $key)->first();
        if (! $item || $item->key_value === null) {
            return $default;
        }

        return $item->key_value;
    }

    /**
     * Simpan atau perbarui nilai terenkripsi untuk kunci tertentu.
     */
    public static function setValue(string $key, ?string $value, ?string $description = null): static
    {
        $userId = auth()->id();

        $record = static::firstOrNew(['key_name' => $key]);
        $record->key_value = $value ?? '';
        if ($description !== null) {
            $record->description = $description;
        }

        if (! $record->exists) {
            $record->created_by = $userId;
        }
        $record->updated_by = $userId;
        $record->save();

        return $record;
    }

    /**
     * Ambil seluruh konfigurasi feeder sebagai array asosiatif [key_name => key_value].
     *
     * @return array<string, string|null>
     */
    public static function getAllCredentials(): array
    {
        $keys = [
            'feeder_url',
            'feeder_port',
            'feeder_username',
            'feeder_password',
            'feeder_endpoint',
            'feeder_token',
        ];

        $records = static::whereIn('key_name', $keys)->get()->keyBy('key_name');

        $result = [];
        foreach ($keys as $k) {
            $result[$k] = isset($records[$k]) ? $records[$k]->key_value : null;
        }

        return $result;
    }

    /**
     * Susun URL Web Service lengkap dari konfigurasi tersimpan.
     */
    public static function getWsUrl(): ?string
    {
        $url = static::getValue('feeder_url');
        if (! $url) {
            return null;
        }

        $port = static::getValue('feeder_port');
        $endpoint = static::getValue('feeder_endpoint', 'ws/live2.php') ?: 'ws/live2.php';

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? $url;
        $existingPort = $parsed['port'] ?? null;

        $targetPort = $port ?: $existingPort;
        $portSuffix = $targetPort ? ":{$targetPort}" : '';

        $cleanEndpoint = ltrim($endpoint, '/');

        return "{$scheme}://{$host}{$portSuffix}/{$cleanEndpoint}";
    }
}
