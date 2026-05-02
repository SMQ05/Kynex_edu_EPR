<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * EloquentUserProvider that swallows QueryExceptions thrown when the
 * underlying user table doesn't exist (e.g. SchoolUser lookup against
 * the central DB when no tenancy is initialised because the original
 * tenant DB was dropped). Without this, every Livewire poll from a
 * stale browser tab 500s.
 */
class SafeEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        try {
            return parent::retrieveById($identifier);
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'school_users') && str_contains($msg, 'does not exist')) {
                Log::info('SafeEloquentUserProvider: school_users missing, returning null user', [
                    'identifier' => $identifier,
                ]);
                return null;
            }
            throw $e;
        }
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        try {
            return parent::retrieveByToken($identifier, $token);
        } catch (QueryException $e) {
            return null;
        }
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        try {
            return parent::retrieveByCredentials($credentials);
        } catch (QueryException $e) {
            return null;
        }
    }
}
