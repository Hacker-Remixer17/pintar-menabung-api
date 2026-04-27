<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'US Dollar', 'symbol' => '$', 'code' => 'USD'],
            ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'code' => 'IDR'],
            ['name' => 'Euro', 'symbol' => '€', 'code' => 'EUR'],
            ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'code' => 'SGD'],
            ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'code' => 'MYR'],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}