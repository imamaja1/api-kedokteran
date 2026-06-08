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
            return null;
        }
    }

    protected function getCodeKey(): string
    {
        return $this->primaryKey;
    }
}
