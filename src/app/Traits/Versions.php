<?php

namespace LaravelEnso\Versions\app\Traits;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versions
{
    // protected $versioningAttribute = 'version';

    public function checkVersion(?int $version = null)
    {
        if (($version ?? $this->{$this->versioningAttribute()})
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()}) {
            $this->throwInvalidVersionException();
        }
    }

    protected static function bootVersions()
    {
        self::creating(fn ($model) => $model
            ->{$model->versioningAttribute()} = 1);

        self::updating(fn ($model) => $model->nextVersion());

        self::updated(fn () => DB::commit());
    }

    private function nextVersion()
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

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'Current record was changed since it was loaded',
            ['class' => static::class]
        ));
    }
}
