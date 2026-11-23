<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(BookIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                $validated['keyword'] ?? null,
                function ($query, $keyword) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where('title', 'like', "%{$keyword}%")
                            ->orWhere('author', 'like', "%{$keyword}%")
                            ->orWhere('isbn', 'like', "%{$keyword}%");
                    });
                }
            )
            ->when(
                $validated['genre_id'] ?? null,
                function ($query, $genreId) {
                    $query->whereHas(
                        'genres',
                        function ($query) use ($genreId) {
                            $query->where('genres.id', $genreId);
                        }
                    );
                }
            )
            ->paginate($validated['per_page'] ?? 20);

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];

        unset($validated['genres']);

        $book = DB::transaction(function () use (
            $request,
            $validated,
            $genreIds
        ) {
            $book = Book::create([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);

            $book->genres()->sync($genreIds);

            return $book;
        });

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Book $book): BookResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book
    ): BookResource {
        $this->authorize('update', $book);

        $validated = $request->validated();
        $genreIds = $validated['genres'];

        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);
            $book->genres()->sync($genreIds);
        });

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
