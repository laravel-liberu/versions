<?php

namespace LaravelEnso\Versions\App\Traits;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versions
{
    // protected $versioningAttribute = 'version';
    public static function bootVersions()
    {
        self::creating(fn ($model) => $model
            ->{$model->versioningAttribute()} = 1);

        self::updating(fn ($model) => $model->incrementVersion());

        self::updated(fn () => DB::commit());
    }

    public function checkVersion(?int $version = null)
    {
        if (($version ?? $this->{$this->versioningAttribute()})
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()}) {
            $this->throwInvalidVersionException();
        }
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

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'Current record was changed since it was loaded',
            ['class' => static::class]
        ));
    }
}
