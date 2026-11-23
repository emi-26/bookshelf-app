<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    public function test_book_belongs_to_many_genres(): void
    {
        $book = new Book;

        $relation = $book->genres();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(Genre::class, $relation->getRelated());
        $this->assertSame('book_genre', $relation->getTable());
    }

    public function test_book_has_many_reviews(): void
    {
        $book = new Book;

        $relation = $book->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Review::class, $relation->getRelated());
        $this->assertSame('book_id', $relation->getForeignKeyName());
    }

    public function test_book_has_required_fillable_attributes(): void
    {
        $book = new Book;

        $this->assertSame([
            'user_id',
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
        ], $book->getFillable());
    }

    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book;

        $this->assertSame(
            'date',
            $book->getCasts()['published_date']
        );
    }
}
