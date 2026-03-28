<?php
namespace App\Facades;

use App\Services\HookManager;
use Illuminate\Support\Facades\Facade;

class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
