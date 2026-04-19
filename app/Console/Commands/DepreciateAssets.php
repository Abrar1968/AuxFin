<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;

class DepreciateAssets extends Command
{
    protected $signature = 'finerp:assets:depreciate';

    protected $description = 'Apply monthly straight-line depreciation to active assets';

    public function handle(): int
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Asset> $assets */
        $assets = Asset::query()->where('status', 'active')->get();

        foreach ($assets as $asset) {
            $nextValue = max(0, (float) $asset->current_book_value - (float) $asset->monthly_depreciation);

            $asset->update([
                'current_book_value' => $nextValue,
                'status' => $nextValue <= 0 ? 'fully_depreciated' : 'active',
            ]);
        }

        $this->info('Depreciation applied to '.$assets->count().' assets.');

        return self::SUCCESS;
    }
}
