<?php

namespace App\Http\Controllers;

use App\Http\Requests\IsbnSearchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class IsbnController extends Controller
{
    /**
     * ISBNをもとにGoogle Books APIから書籍情報を取得する。
     *
     * ISBNが13桁でない場合は422、APIがエラーを返した場合は502、
     * 書籍が見つからない場合は404を返す。
     * 取得成功時は書籍情報をJSON形式で返す。
     */
    public function show(IsbnSearchRequest $request, string $isbn): JsonResponse
    {
        $validated = $request->validated();

        $response = Http::get(
            'https://www.googleapis.com/books/v1/volumes',
            [
                'q' => "isbn:{$validated['isbn']}",
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        $item = $response->json('items.0.volumeInfo');

        if (! $item) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        return response()->json([
            'title' => $item['title'] ?? '',
            'author' => collect($item['authors'] ?? [])->join(', '),
            'description' => $item['description'] ?? '',
            'image_url' => $item['imageLinks']['thumbnail'] ?? '',
            'published_date' => $item['publishedDate'] ?? '',
        ]);
    }
}
