<?php

namespace LaravelEnso\Versions\App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Version extends ConflictHttpException
{
    public static function recordModified(string $class)
    {
        return new static(__(
            'Current record was changed since it was loaded',
            ['class' => $class]
        ));
    }
}
