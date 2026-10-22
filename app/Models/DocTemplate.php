<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocTemplate extends Model
{
    protected $guarded = [];

    protected $casts = ['config' => 'array'];

    /** Built-in starting templates created for every new agency. */
    public static function defaults(): array
    {
        $base = ['orientation' => 'portrait', 'paper' => 'a4', 'show_notes' => true, 'header' => '', 'footer' => 'Prepared with {brand}', 'charts' => true];

        return [
            ['name' => 'Campaign report', 'config' => $base + ['layout' => 'graph', 'sections' => ['kpis', 'charts', 'tabs']]],
            ['name' => 'Content calendar', 'config' => array_merge($base, ['layout' => 'normal', 'charts' => false, 'orientation' => 'landscape', 'sections' => ['calendar']])],
            ['name' => 'Client approval sheet', 'config' => array_merge($base, ['layout' => 'detailed', 'charts' => false, 'sections' => ['approval']])],
            ['name' => 'Strategy brief', 'config' => array_merge($base, ['layout' => 'normal', 'charts' => false, 'sections' => ['strategy']])],
            ['name' => 'Full campaign pack', 'config' => $base + ['layout' => 'detailed', 'sections' => ['strategy', 'kpis', 'charts', 'calendar', 'approval']]],
        ];
    }
}
