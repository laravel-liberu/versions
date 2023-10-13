<?php

namespace LaravelLiberu\Versions\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelLiberu\Versions\Exceptions\Version;
use Throwable;

trait Versions
{
    // protected $versioningAttribute = 'version';

    public static function bootVersions()
    {
        self::creating(fn ($model) => $model->{$model->versioningAttribute()} = 1);

        self::updating(fn ($model) => $model->incrementVersion());

        self::updated(fn () => DB::commit());
    }

    public function checkVersion(?int $version = null): void
    {
        if ($this->versionMismatch($version)) {
            throw Version::recordModified(static::class);
        }
    }

    private function versionMismatch(?int $version): bool
    {
        return ($version ?? $this->{$this->versioningAttribute()})
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()};
    }

    private function incrementVersion(): void
    {
        DB::beginTransaction();

        try {
            $this->checkVersion();
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        $this->{$this->versioningAttribute()}++;
    }

    private function lockWithoutEvents()
    {
        return DB::table($this->getTable())->lock()
            ->where($this->getKeyName(), $this->getKey())
            ->first();
    }

    private function versioningAttribute(): string
    {
        return property_exists($this, 'versioningAttribute')
            ? $this->versioningAttribute
            : 'version';
    }
}
