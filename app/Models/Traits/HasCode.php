<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

trait HasCode
{
    public function toCode(): string
    {
        return Crypt::encryptString($this->{$this->getCodeKey()});
    }

    public static function findByCode(string $code): ?static
    {
        try {
            $id = Crypt::decryptString($code);
            return static::find($id);
        } catch (DecryptException) {
            // Fallback 1: Query string sering kali mengkonversi karakter '+' menjadi spasi (' ')
            if (str_contains($code, ' ')) {
                try {
                    $id = Crypt::decryptString(str_replace(' ', '+', $code));
                    return static::find($id);
                } catch (DecryptException) {
                    // Lanjut ke fallback berikutnya
                }
            }

            // Fallback 2: Jika input dikirim berupa ID numerik langsung
            if (is_numeric($code) || ctype_digit($code)) {
                return static::find($code);
            }

            return null;
        }
    }

    protected function getCodeKey(): string
    {
        return $this->primaryKey;
    }
}
