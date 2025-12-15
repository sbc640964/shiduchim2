<?php

namespace App\Filament\Clusters\Settings\Resources\Banners;

use App\Filament\Clusters\Settings\Resources\Banners\Pages\CreateBanner;
use App\Filament\Clusters\Settings\Resources\Banners\Pages\EditBanner;
use App\Filament\Clusters\Settings\Resources\Banners\Pages\ListBanners;
use App\Filament\Clusters\Settings\Resources\Banners\Schemas\BannerForm;
use App\Filament\Clusters\Settings\Resources\Banners\Tables\BannersTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use App\Models\Banner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'באנר';

    protected static ?string $pluralLabel = 'באנרים';

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_banners');
    }

    public static function form(Schema $schema): Schema
    {
        return BannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
//            'create' => CreateBanner::route('/create'),
//            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
