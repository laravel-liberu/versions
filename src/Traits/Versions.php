<?php

namespace LaravelEnso\Versions\Traits;

use Illuminate\Support\Facades\DB;
use LaravelEnso\Versions\Exceptions\Version;

trait Versions
{
    // protected $versioningAttribute = 'version';

    public static function bootVersions()
    {
        self::creating(fn ($model) => $model->{$model->versioningAttribute()} = 1);

        self::updating(fn ($model) => $model->incrementVersion());

        self::updated(fn () => DB::commit());
    }

    public function checkVersion(?int $version = null)
    {
        if ($this->versionMismatch($version)) {
            DB::rollBack();
            throw Version::recordModified(static::class);
        }
    }

    private function versionMismatch(?int $version)
    {
        return ($version ?? $this->{$this->versioningAttribute()})
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()};
    }

    private function incrementVersion()
    {
        DB::beginTransaction();

        $this->checkVersion();
        $this->{$this->versioningAttribute()}++;
    }

    private function lockWithoutEvents()
    {
        return DB::table($this->getTable())->lock()
            ->where($this->getKeyName(), $this->getKey())
            ->first();
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
            ? $this->versioningAttribute
            : 'version';
    }
}
