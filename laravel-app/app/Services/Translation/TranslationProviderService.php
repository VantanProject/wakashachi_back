<?php

namespace App\Services\Translation;

use GuzzleHttp\Client;
use Exception;

class TranslationProviderService
{
    // DeepLとGoogle翻訳の言語コードマッピング
    private const LANGUAGE_CODES = [
        '1' => ['deepl' => 'JA', 'google' => 'ja'],  // 日本語
        '2' => ['deepl' => 'EN', 'google' => 'en'],  // 英語
        '3' => ['deepl' => 'ZH', 'google' => 'zh'],  // 中国語
        '4' => ['deepl' => 'KO', 'google' => 'ko']   // 韓国語
    ];

    private $client;
    private $googleScriptUrl;
    private $deeplApiUrl;
    private $deeplApiKey;

    // HTTPクライアントと設定の初期化
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->googleScriptUrl = config('app.google_script_url');
        $this->deeplApiUrl = config('app.deepl_api_url');
        $this->deeplApiKey = config('app.deepl_api_key');
    }

    // Google翻訳APIを使用した翻訳処理
    public function translateWithGoogle($text, $source, $target)
    {
        try {
            // Google Script URLに翻訳パラメータを付与してGETリクエスト
            $response = $this->client->get($this->googleScriptUrl, [
                'query' => [
                    'text' => $text,
                    'source' => self::LANGUAGE_CODES[$source]['google'],
                    'target' => self::LANGUAGE_CODES[$target]['google'],
                ],
            ]);

            // レスポンスボディをJSON形式から連想配列に変換
            // getContents()でレスポンスの生データを取得し、json_decode()で PHP で扱える形式に変換
            $data = json_decode($response->getBody()->getContents(), true);

            // APIからのレスポンスが期待する形式（配列で'text'キーを含む）であることを確認
            // 不正なレスポンス形式の場合、以降の処理でエラーが発生する可能性があるため、早期にチェック
            if (!is_array($data) || !isset($data['text'])) {
                throw new Exception('Google翻訳からの不正なレスポンスです');
            }

            // 翻訳テキストの後処理を実行して返却
            return $this->postProcessTranslation($data['text'], $target, true);
        } catch (Exception $e) {
            throw new Exception('Google翻訳での処理に失敗しました: ' . $e->getMessage());
        }
    }

    // DeepL APIを使用した翻訳処理
    public function translateWithDeepL($text, $source, $target)
    {
        try {
            // DeepL APIにリクエストパラメータを指定してPOSTリクエスト
            $response = $this->client->post($this->deeplApiUrl, [
                'form_params' => [
                    'auth_key' => $this->deeplApiKey,
                    'text' => $text,
                    'source_lang' => self::LANGUAGE_CODES[$source]['deepl'],
                    'target_lang' => self::LANGUAGE_CODES[$target]['deepl'],
                ],
            ]);

            // DeepL APIのレスポンスをJSON形式から連想配列に変換
            // レスポンスは {"translations": [{"text": "翻訳されたテキスト"}]} の形式で返される
            $data = json_decode($response->getBody()->getContents(), true);

            // DeepL APIの仕様に基づき、translations配列の最初の要素のtextキーに翻訳結果が格納されていることを確認
            // この形式でない場合はAPIエラーの可能性があるため例外をスロー
            if (!$data || !isset($data['translations'][0]['text'])) {
                throw new Exception('Invalid response from DeepL');
            }

            // 翻訳テキストの後処理を実行して返却
            return $this->postProcessTranslation($data['translations'][0]['text'], $target, false);
        } catch (Exception $e) {
            throw new Exception('Failed to translate with DeepL: ' . $e->getMessage());
        }
    }

    // 翻訳テキストの整形処理
    private function postProcessTranslation($text, $target, $isGoogle = true)
    {
        // 英語の二重引用符で囲まれた部分を日本語の鉤括弧に変換
        // 例: "Hello" → 「Hello」
        // 正規表現: "([^"]+)" は二重引用符で囲まれた任意の文字列にマッチ
        $text = preg_replace('/"([^"]+)"/', '「$1」', $text);

        // 開き鉤括弧が連続する場合を修正（翻訳エンジンの誤変換対策）
        // 例: 「こんにちは「 → 「こんにちは」
        // 正規表現: 「([^」]+)「 は開き鉤括弧で始まり、別の開き鉤括弧で終わる文字列にマッチ
        $text = preg_replace('/「([^」]+)「/', '「$1」', $text);
        return $text;
    }
}
