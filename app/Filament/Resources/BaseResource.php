<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    /**
     * Get the redirect URL after creating a record.
     * Override this in child classes if different behavior is needed.
     */
    public static function getRedirectUrlAfterCreate($record): string
    {
        // Check if view page exists
        if (in_array('view', array_keys(static::getPages()))) {
            return static::getUrl('view', ['record' => $record]);
        }
        
        // Fallback to index
        return static::getUrl('index');
    }
    
    /**
     * Get the redirect URL after editing a record.
     * Override this in child classes if different behavior is needed.
     */
    public static function getRedirectUrlAfterEdit($record): string
    {
        // Check if view page exists
        if (in_array('view', array_keys(static::getPages()))) {
            return static::getUrl('view', ['record' => $record]);
        }
        
        // Fallback to index
        return static::getUrl('index');
    }
}
