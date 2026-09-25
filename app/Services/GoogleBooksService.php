<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleBooksService
{
    /**
     * ISBNから書籍情報を取得
     */
    public function searchByIsbn(string $isbn): array
    {
        // a. 13桁チェック（400 Bad Request）
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            return [
                'success' => false,
                'status' => 400,
                'error' => 'ISBNは13桁で入力してください。',
            ];
        }

        $apiKey = config('services.google_books.api_key');

        try {
            // Laravel標準の Http ファサードを使用
            $queryParams = ['q' => 'isbn:' . $isbn];
            if ($apiKey) {
                $queryParams['key'] = $apiKey;
            }

            $response = Http::get('https://www.googleapis.com/books/v1/volumes', $queryParams);

            // c. クォータ超過（429 Too Many Requests）
            if ($response->status() === 429) {
                return [
                    'success' => false,
                    'status' => 429,
                    'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
                ];
            }

            // 通信失敗（500 Internal Server Error）
            if ($response->failed()) {
                return [
                    'success' => false,
                    'status' => 500,
                    'error' => 'API通信エラーが発生しました。',
                ];
            }

            $data = $response->json();

            // b. 書籍が見つからない（404 Not Found）
            if (empty($data['items'])) {
                return [
                    'success' => false,
                    'status' => 404,
                    'error' => '書籍が見つかりませんでした。',
                ];
            }

            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            // 日付フォーマットの整形 (YYYY-MM-DD)
            $rawDate = $volumeInfo['publishedDate'] ?? '';
            $publishedDate = '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $publishedDate = $rawDate;
            } elseif (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
                $publishedDate = $rawDate . '-01';
            } elseif (preg_match('/^\d{4}$/', $rawDate)) {
                $publishedDate = $rawDate . '-01-01';
            }

            return [
                'success' => true,
                'status' => 200,
                'data' => [
                    'title' => $volumeInfo['title'] ?? '',
                    'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '',
                    'published_date' => $publishedDate,
                    'description' => $volumeInfo['description'] ?? '',
                    'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? $volumeInfo['imageLinks']['smallThumbnail'] ?? '',
                ],
            ];

        } catch (Exception $e) {
            Log::error('Google Books API Error: ' . $e->getMessage());

            // d. API通信エラー（500 Internal Server Error）
            return [
                'success' => false,
                'status' => 500,
                'error' => 'API通信エラーが発生しました。',
            ];
        }
    }
}