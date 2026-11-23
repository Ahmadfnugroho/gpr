<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseOptimizedResource extends Resource
{
    /**
     * Columns to select (override in child classes)
     */
    protected static function getSelectColumns(): array
    {
        return ['*'];
    }

    /**
     * Relations to eager load (override in child classes)
     */
    protected static function getEagerLoadRelations(): array
    {
        return [];
    }
}
