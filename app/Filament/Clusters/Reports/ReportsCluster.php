<?php

namespace App\Filament\Clusters\Reports;

use Filament\Clusters\Cluster;

class ReportsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $title = 'דוחות';

    protected static ?int $navigationSort = 200;

}
