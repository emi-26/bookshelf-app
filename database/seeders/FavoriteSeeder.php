<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $favorites = [
            [0, 1, 2],
            [1, 3, 4, 5],
            [0, 2, 5, 6, 7],
            [3, 6, 8],
            [1, 4, 7, 9, 10],
        ];

        foreach ($users as $index => $user) {
            $bookIds = collect($favorites[$index])
                ->map(fn ($bookIndex) => $books[$bookIndex]->id);

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
