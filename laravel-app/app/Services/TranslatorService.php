<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class TranslatorService
{
    protected $client;
    protected $googleScriptUrl;
    protected $deeplApiUrl;
    protected $deeplApiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->googleScriptUrl = config('app.google_script_url');
        $this->deeplApiUrl = config('app.deepl_api_url');
        $this->deeplApiKey = config('app.deepl_api_key');
    }

    // Google Apps Script翻訳
    public function translateWithGoogle($text, $source, $target)
    {
        try {
            $response = $this->client->get($this->googleScriptUrl, [
                'query' => [
                    'text' => $text,
                    'source' => $source,
                    'target' => $target,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['code'] === 200) {
                return $data['text'];
            }

            throw new Exception('Google Translate Error: ' . ($data['text'] ?? 'Unknown error'));
        } catch (Exception $e) {
            throw new Exception('Failed to translate with Google: ' . $e->getMessage());
        }
    }

    // DeepL翻訳
    public function translateWithDeepL($text, $source, $target)
    {
        try {
            $response = $this->client->post($this->deeplApiUrl, [
                'form_params' => [
                    'auth_key' => $this->deeplApiKey,
                    'text' => $text,
                    'source_lang' => strtoupper($source),
                    'target_lang' => strtoupper($target),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['translations'][0]['text'])) {
                return $data['translations'][0]['text'];
            }

            throw new Exception('DeepL Translate Error: No translation found');
        } catch (Exception $e) {
            throw new Exception('Failed to translate with DeepL: ' . $e->getMessage());
        }
    }

    // 比較用関数
    public function compareTranslations($text, $source, $target)
    {
        try {
            // 順方向の翻訳
            $googleForward = $this->translateWithGoogle($text, $source, $target);
            $deeplForward = $this->translateWithDeepL($text, $source, $target);

            // 逆翻訳
            $googleBackward = $this->translateWithGoogle($googleForward, $target, $source);
            $deeplBackward = $this->translateWithDeepL($deeplForward, $target, $source);

            // 類似度スコアの計算
            $googleScore = $this->calculateSimilarity($text, $googleBackward);
            $deeplScore = $this->calculateSimilarity($text, $deeplBackward);

            // より精度の高い翻訳を選択
            $recommendedTranslation = $googleScore > $deeplScore ? $googleForward : $deeplForward;

            return [
                'google' => [
                    'translation' => $googleForward,
                    'back_translation' => $googleBackward,
                    'accuracy_score' => round($googleScore * 100, 2)
                ],
                'deepl' => [
                    'translation' => $deeplForward,
                    'back_translation' => $deeplBackward,
                    'accuracy_score' => round($deeplScore * 100, 2)
                ],
                'recommended' => $recommendedTranslation
            ];
        } catch (Exception $e) {
            throw new Exception('Translation comparison failed: ' . $e->getMessage());
        }
    }

    private function calculateSimilarity($original, $backTranslated)
    {
        // レーベンシュタイン距離を使用して類似度を計算
        $maxLength = max(strlen($original), strlen($backTranslated));
        if ($maxLength === 0) return 1.0;

        $levenshtein = levenshtein(
            mb_strtolower($original),
            mb_strtolower($backTranslated)
        );

        return 1 - ($levenshtein / $maxLength);
    }
}
