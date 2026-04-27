<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // EXPENSE categories (Pengeluaran)
            ['name' => 'Food & Drinks', 'icon' => '🍔', 'type' => 'EXPENSE'],
            ['name' => 'Transportation', 'icon' => '🚗', 'type' => 'EXPENSE'],
            ['name' => 'Shopping', 'icon' => '🛍️', 'type' => 'EXPENSE'],
            ['name' => 'Entertainment', 'icon' => '🎬', 'type' => 'EXPENSE'],
            ['name' => 'Bills & Utilities', 'icon' => '💡', 'type' => 'EXPENSE'],
            ['name' => 'Healthcare', 'icon' => '🏥', 'type' => 'EXPENSE'],
            ['name' => 'Education', 'icon' => '📚', 'type' => 'EXPENSE'],
            
            // INCOME categories (Pemasukan)
            ['name' => 'Salary', 'icon' => '💰', 'type' => 'INCOME'],
            ['name' => 'Bonus', 'icon' => '🎁', 'type' => 'INCOME'],
            ['name' => 'Freelance', 'icon' => '💻', 'type' => 'INCOME'],
            ['name' => 'Investment', 'icon' => '📈', 'type' => 'INCOME'],
            ['name' => 'Gift', 'icon' => '🎀', 'type' => 'INCOME'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}