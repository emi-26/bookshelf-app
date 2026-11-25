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
    /**
     * 書籍一覧を検索条件に応じて取得する。
     *
     * タイトル・著者名・ISBNによる検索、ジャンルの絞り込みに対応する。
     * 検索結果は指定された件数（デフォルト20件）でページネーションする。
     */
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

    /**
     * 書籍を登録し、選択されたジャンルを関連付ける。
     *
     * 登録処理はトランザクション内で行い、
     * 登録した書籍情報をHTTPステータス201で返す。
     */
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

    /**
     * 書籍情報と関連するジャンルを更新する。
     *
     * 所有者の認可を行い、更新処理はトランザクション内で行う。
     * 更新後の書籍情報を返す。
     */
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
