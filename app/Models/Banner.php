<?php

namespace App\Models;

use App\Filament\Clusters\Settings\Resources\Banners\Utilities\Location;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'name',
        'heading',
        'body',
        'published_at',
        'expires_at',
        'locations',
        'locations_data',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'timestamp',
            'expires_at' => 'timestamp',
            'locations' => 'array',
            'locations_data' => 'array',
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    function getLocationsLabelsAttribute()
    {
        $locations = $this->locations ?? [];

        return array_map(function ($location) {
            return Location::getLabel($location);
        }, $locations);
    }

    public function getStyle (): string
    {
        $style = $this->config['style'] ?? [];

        return collect([
            'background-color' => $style['background_color'] ?? '#ffffff',
            'color' => $style['text_color'] ?? '#000000',
            'border-color' => $style['border_color'] ?? '#dddddd',
            'border-width' => $style['border_width'] ?? '0px'
        ])->map(fn ($value, $key) => "$key: $value;")->implode(' ');
    }

    public function getIconColor()
    {
        return $this->config['style']['icon_color']
            ?? $this->config['style']['text_color']
            ?? '#000000';
    }

    public function getIconCase()
    {
        $icon = $this->config['style']['icon'] ?? null;

        if ($icon === null) {
            return null;
        }

        $iconCases = collect(Heroicon::cases());

        $iconCase = $iconCases->firstWhere('value', $icon)?->name ?? null;

        if ($iconCase === null) {
            return null;
        }

        try {
            return constant(Heroicon::class.'::'.$iconCase);
        } catch (\Exception $e) {
            return null;
        }
    }
}
