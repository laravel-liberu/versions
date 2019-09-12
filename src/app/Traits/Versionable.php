<?php

namespace LaravelEnso\Versions\app\Traits;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versions
{
    // protected $versioningAttribute = 'version';

    protected static function bootVersionable()
    {
        self::creating(function ($model) {
            $model->{$this->versioningAttribute()} = 1;
        });

        self::updating(function ($model) {
            DB::beginTransaction();
            $model->checkVersion();
            $model->{$this->versioningAttribute()}++;
        });
        
        self::updated(function () {
            DB::commit();
        });
    }

    private function checkVersion()
    {
        if ($this->{$this->versioningAttribute()}
            !== $this->lockWithoutEvents()->{$this->versioningAttribute()}) {
            $this->throwInvalidVersionException();
        }
    }

    private function lockWithoutEvents()
    {
        DB::table($this->getTable())->lock()
            ->where($this->getKeyName(), $this->getKey())
            ->first();
    }

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'Current record was changed since it was loaded',
            ['class' => get_class($this)]
        ));
    }
}
