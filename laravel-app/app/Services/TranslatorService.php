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
                return $this->postProcessTranslation($data['text'], true);
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
                return $this->postProcessTranslation($data['translations'][0]['text'], false);
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

            // 翻訳後のテキストを後処理
            $googleBackward = $this->postProcessTranslation($googleBackward);
            $deeplBackward = $this->postProcessTranslation($deeplBackward);

            // スコア計算
            $googleTotalScore = $this->calculateFinalScore($text, $googleBackward, $target);
            $deeplTotalScore = $this->calculateFinalScore($text, $deeplBackward, $target);

            // より精度の高い翻訳を選択
            $recommendedTranslation = $deeplTotalScore > $googleTotalScore ? $deeplForward : $googleForward;

            return [
                'google' => [
                    'translation' => $googleForward,
                    'back_translation' => $googleBackward,
                    'total_score' => round($googleTotalScore, 2)
                ],
                'deepl' => [
                    'translation' => $deeplForward,
                    'back_translation' => $deeplBackward,
                    'total_score' => round($deeplTotalScore, 2)
                ],
                'recommended' => $recommendedTranslation
            ];
        } catch (Exception $e) {
            throw new Exception('Translation comparison failed: ' . $e->getMessage());
        }
    }

    private function calculateFinalScore($original, $translated, $target)
    {
        // 言語ごとの重み付け設定
        $weights = $this->getLanguageWeights($target);

        // レーベンシュタイン距離
        $levenshtein = $this->calculateLevenshteinSimilarity($original, $translated);

        // BLEU
        $bleu = $this->calculateBleuScore($original, $translated, $target);

        // Jaccard類似度
        $jaccard = $this->calculateJaccardSimilarity($original, $translated);

        // コサイン類似度
        $cosine = $this->calculateCosineSimilarity($original, $translated);

        // 重み付けしたスコアの計算
        $weightedScore = ($levenshtein * $weights['levenshtein']) +
                        ($bleu * $weights['bleu']) +
                        ($jaccard * $weights['jaccard']) +
                        ($cosine * $weights['cosine']);

        return $weightedScore * 100;
    }

    private function getLanguageWeights($target)
    {
        switch ($target) {
            case 'en':
                // 英語：単語単位の評価を重視
                return [
                    'levenshtein' => 0.10,
                    'bleu' => 0.45,        // 単語の順序を重視
                    'jaccard' => 0.15,
                    'cosine' => 0.30
                ];

            case 'zh':
                // 中国語：文字単位の評価を重視
                return [
                    'levenshtein' => 0.15,
                    'bleu' => 0.35,
                    'jaccard' => 0.20,
                    'cosine' => 0.30       // 漢字の一致を重視
                ];

            case 'ko':
                // 韓国語：文字と語順の両方を考慮
                return [
                    'levenshtein' => 0.10,
                    'bleu' => 0.35,
                    'jaccard' => 0.25,
                    'cosine' => 0.30
                ];

            default:
                return [
                    'levenshtein' => 0.10,
                    'bleu' => 0.35,
                    'jaccard' => 0.20,
                    'cosine' => 0.35
                ];
        }
    }

    private function calculateBleuScore($reference, $candidate, $target)
    {
        // 言語に応じたトークン化
        $referenceTokens = $this->tokenize($reference, $target);
        $candidateTokens = $this->tokenize($candidate, $target);

        $maxN = $this->getMaxNGram($target);
        $weights = array_fill(0, $maxN, 1.0 / $maxN);
        $scores = [];

        // n-gramごとのスコアを計算
        for ($n = 1; $n <= $maxN; $n++) {
            $refNgrams = $this->getNgrams($referenceTokens, $n);
            $candNgrams = $this->getNgrams($candidateTokens, $n);

            if (empty($candNgrams)) {
                $scores[] = 0;
                continue;
            }

            $matches = 0;
            $refCounts = array_count_values($refNgrams);
            $candCounts = array_count_values($candNgrams);

            foreach ($candCounts as $ngram => $candCount) {
                $refCount = isset($refCounts[$ngram]) ? $refCounts[$ngram] : 0;
                $matches += min($candCount, $refCount);
            }

            $scores[] = $matches / count($candNgrams);
        }

        // ブレビティペナルティの計算
        $bp = min(1, exp(1 - count($referenceTokens) / max(1, count($candidateTokens))));

        // 最終スコアの計算
        $weightedScore = 0;
        for ($i = 0; $i < $maxN; $i++) {
            if ($scores[$i] > 0) {
                $weightedScore += $weights[$i] * log($scores[$i]);
            }
        }

        return max(0, min(1, $bp * exp($weightedScore)));
    }

    private function tokenize($text, $target)
    {
        switch ($target) {
            case 'en':
                // 英語：単語単位で分割
                return preg_split('/\s+/', $text);

            case 'zh':
                // 中国語：文字単位で分割
                return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

            case 'ko':
                // 韓国語：文字単位で分割（ただし空白も考慮）
                return preg_split('/((?<=\s)|(?=\s))/u', $text);

            default:
                return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
    }

    private function getMaxNGram($target)
    {
        switch ($target) {
            case 'en':
                return 4;  // 英語は4-gramまで
            case 'zh':
                return 3;  // 中国語は3-gramまで
            case 'ko':
                return 3;  // 韓国語は3-gramまで
            default:
                return 4;
        }
    }

    private function calculateLevenshteinSimilarity($original, $backTranslated)
    {
        $maxLength = max(strlen($original), strlen($backTranslated));
        return $maxLength === 0 ? 1.0 : 1 - (levenshtein(mb_strtolower($original), mb_strtolower($backTranslated)) / $maxLength);
    }

    private function calculateJaccardSimilarity($original, $backTranslated)
    {
        $originalSet = array_unique(str_split(mb_strtolower($original)));
        $backTranslatedSet = array_unique(str_split(mb_strtolower($backTranslated)));

        $intersection = count(array_intersect($originalSet, $backTranslatedSet));
        $union = count(array_unique(array_merge($originalSet, $backTranslatedSet)));

        return $union === 0 ? 1.0 : $intersection / $union;
    }

    private function calculateCosineSimilarity($original, $backTranslated)
    {
        $originalVector = $this->textToVector($original);
        $backTranslatedVector = $this->textToVector($backTranslated);

        $dotProduct = array_sum(array_map(fn($a, $b) => $a * $b, $originalVector, $backTranslatedVector));
        $magnitudeOriginal = sqrt(array_sum(array_map(fn($a) => $a * $a, $originalVector)));
        $magnitudeBackTranslated = sqrt(array_sum(array_map(fn($b) => $b * $b, $backTranslatedVector)));

        return ($magnitudeOriginal * $magnitudeBackTranslated) == 0 ? 1.0 : $dotProduct / ($magnitudeOriginal * $magnitudeBackTranslated);
    }

    private function textToVector($text)
    {
        $vector = [];
        foreach (str_split(mb_strtolower($text)) as $char) {
            $vector[$char] = ($vector[$char] ?? 0) + 1;
        }
        return $vector;
    }

    private function postProcessTranslation($text, $isGoogle = false)
    {
        // 様々な引用符を標準的な引用符に統一し、「」はそのまま維持
        $text = str_replace(
            ['``', "''", '“', '”', '‘', '’', '‹', '›', '«', '»'],
            '"',
            $text
        );

        // 「」はそのまま維持
        $text = preg_replace('/"([^"]+)"/', '「$1」', $text);

        // 連続する引用符を単一の引用符に
        $text = preg_replace('/""+/', '"', $text);

        // GoogleとDeepLの特有処理
        if ($isGoogle) {
            // Google翻訳特有の処理
            $text = preg_replace('/\s*「\s*([^」]+)\s*」\s*/', '「$1」', $text);
            $text = str_replace('。」', '」。', $text);
        } else {
            // DeepL特有の処理
            $text = preg_replace('/\s+/', ' ', $text);
            $text = str_replace(' 「', '「', $text);
            $text = str_replace('」 ', '」', $text);
        }

        return $text;
    }

    private function getNgrams($tokens, $n)
    {
        $ngrams = [];
        $count = count($tokens);

        for ($i = 0; $i <= $count - $n; $i++) {
            $ngram = array_slice($tokens, $i, $n);
            $ngrams[] = implode('', $ngram);
        }

        return $ngrams;
    }
}
