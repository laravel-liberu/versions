<?php

namespace LaravelEnso\Versions\app\Traits;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versions
{
    // protected $versioningAttribute = 'version';

    protected static function bootVersions()
    {
        self::creating(function ($model) {
            $model->{$model->versioningAttribute()} = 1;
        });

        self::updating(function ($model) {
            DB::beginTransaction();
            $model->checkVersion();
            $model->{$model->versioningAttribute()}++;
        });

        self::updated(function () {
            DB::commit();
        });
    }

    public function checkVersion(int $version = null)
    {
        if (($version ?? $this->{$this->versioningAttribute()})
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()}) {
            $this->throwInvalidVersionException();
        }
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
            ['class' => get_class($this)]
        ));
    }
}
