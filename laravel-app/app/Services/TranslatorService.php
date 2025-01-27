<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TranslatorService
{
    protected $client;
    protected $googleScriptUrl;
    protected $deeplApiUrl;
    protected $deeplApiKey;
    protected $dictionary;

    // 言語コードの定数
    private const LANGUAGE_CODES = [
        '1' => ['deepl' => 'JA', 'google' => 'ja'],
        '2' => ['deepl' => 'EN', 'google' => 'en'],
        '3' => ['deepl' => 'ZH', 'google' => 'zh'],
        '4' => ['deepl' => 'KO', 'google' => 'ko']
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->googleScriptUrl = config('app.google_script_url');
        $this->deeplApiUrl = config('app.deepl_api_url');
        $this->deeplApiKey = config('app.deepl_api_key');

        // 辞書の読み込みを確実に
        $dictionaryPath = config_path('dictionary.json');
        $dictionaryContent = file_get_contents($dictionaryPath);

        if ($dictionaryContent === false) {
            throw new Exception("Dictionary file not found: {$dictionaryPath}");
        }

        $this->dictionary = json_decode($dictionaryContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid dictionary JSON: " . json_last_error_msg());
        }

        // 辞書の構造を検証
        if (!isset($this->dictionary['special_items'])) {
            Log::warning("Dictionary missing 'special_items' key. Creating from provided data.");

            // 提供されたデータから special_items を作成
            $specialItems = [
                "ピリ辛カレールゥ" => [
                    "en" => "spicy curry roux",
                    "zh" => "香辣咖喱汤底",
                    "ko" => "매운 카레 루"
                ],
                "極太麺" => [
                    "en" => "extra-thick noodles",
                    "zh" => "特粗面条",
                    "ko" => "굵은 면"
                ],
                "カツ" => [
                    "en" => "cutlet",
                    "zh" => "炸猪排",
                    "ko" => "돈가스"
                ],
                "天ぷら" => [
                    "en" => "tempura",
                    "zh" => "天妇罗",
                    "ko" => "튀김"
                ]
            ];

            $this->dictionary['special_items'] = $specialItems;
        }

        // キャッシュに辞書を保存
        Cache::put('translation_dictionary', $this->dictionary, now()->addHours(24));

        Log::info('Dictionary loaded successfully');
        Log::info('Special items count: ' . count($this->dictionary['special_items']));
    }

    private function getDictionaryTranslation($item, $langCode)
    {
        // キャッシュから辞書を取得
        $dictionary = Cache::get('translation_dictionary', $this->dictionary);

        if (isset($dictionary['special_items'][$item][$langCode])) {
            Log::info("Found translation for '{$item}': " . $dictionary['special_items'][$item][$langCode]);
            return $dictionary['special_items'][$item][$langCode];
        }

        Log::warning("No translation found for '{$item}' in language '{$langCode}'");
        return null;
    }

    // Google Apps Script翻訳
    private function translateWithGoogle($text, $source, $target)
    {
        try {
            Log::info('=== Google Translate Start ===');
            Log::info('Input text: ' . $text);
            Log::info('Source: ' . self::LANGUAGE_CODES[$source]['google']);
            Log::info('Target: ' . self::LANGUAGE_CODES[$target]['google']);

            $response = $this->client->get($this->googleScriptUrl, [
                'query' => [
                    'text' => $text,
                    'source' => self::LANGUAGE_CODES[$source]['google'],
                    'target' => self::LANGUAGE_CODES[$target]['google'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Log::info('Google API Response: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

            // レスポンスのバリデーション
            if (!is_array($data) || !isset($data['text'])) {
                Log::error('Invalid Google response structure: ' . print_r($data, true));
                throw new Exception('Invalid response from Google Translate: ' . print_r($data, true));
            }

            $rawTranslation = $data['text'];
            Log::info('Raw Google translation: ' . $rawTranslation);

            $processedTranslation = $this->postProcessTranslation($rawTranslation, $target, true);
            Log::info('Processed Google translation: ' . $processedTranslation);
            Log::info('=== Google Translate End ===');

            return $processedTranslation;
        } catch (Exception $e) {
            Log::error('Google translation error: ' . $e->getMessage());
            throw new Exception('Failed to translate with Google: ' . $e->getMessage());
        }
    }

    // DeepL翻訳
    private function translateWithDeepL($text, $source, $target)
    {
        try {
            Log::info('=== DeepL Translate Start ===');
            Log::info('Input text: ' . $text);
            Log::info('Source: ' . self::LANGUAGE_CODES[$source]['deepl']);
            Log::info('Target: ' . self::LANGUAGE_CODES[$target]['deepl']);

            $response = $this->client->post($this->deeplApiUrl, [
                'form_params' => [
                    'auth_key' => $this->deeplApiKey,
                    'text' => $text,
                    'source_lang' => self::LANGUAGE_CODES[$source]['deepl'],
                    'target_lang' => self::LANGUAGE_CODES[$target]['deepl'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Log::info('DeepL API Response: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

            if (!$data || !isset($data['translations'][0]['text'])) {
                Log::error('Invalid DeepL response structure: ' . print_r($data, true));
                throw new Exception('Invalid response from DeepL');
            }

            $rawTranslation = $data['translations'][0]['text'];
            Log::info('Raw DeepL translation: ' . $rawTranslation);

            $processedTranslation = $this->postProcessTranslation($rawTranslation, $target, false);
            Log::info('Processed DeepL translation: ' . $processedTranslation);
            Log::info('=== DeepL Translate End ===');

            return $processedTranslation;
        } catch (Exception $e) {
            Log::error('DeepL translation error: ' . $e->getMessage());
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
            $googleBackward = $this->postProcessTranslation($googleBackward, $target);
            $deeplBackward = $this->postProcessTranslation($deeplBackward, $target);

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

    private function postProcessTranslation($text, $target, $isGoogle = true)
    {
        try {
            Log::info('=== Post Processing Translation ===');
            Log::info('Input text: ' . $text);
            Log::info('Target language: ' . $target);
            Log::info('Translator: ' . ($isGoogle ? 'Google' : 'DeepL'));

            // 特殊アイテムの検出
            preg_match_all('/\[\[SPECIAL_ITEM_(\d+)\]\]/', $text, $matches, PREG_SET_ORDER);
            if (!empty($matches)) {
                Log::info('Found placeholders: ' . json_encode($matches, JSON_UNESCAPED_UNICODE));
            }

            // 句読点の処理
            if ($target === 'zh') {
                $text = str_replace(['、'], ['，'], $text);
                $text = str_replace(['。'], ['．'], $text);
                Log::info('Punctuation processed for Chinese: ' . $text);
            }

            Log::info('Final processed text: ' . $text);
            Log::info('=== Post Processing End ===');

            return $text;
        } catch (Exception $e) {
            Log::error('Post-processing error: ' . $e->getMessage());
            throw $e;
        }
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

    public function translate($text, $sourceId, $targetId)
    {
        try {
            Log::info('=== Translation Process Start ===');
            Log::info('Original text: ' . $text);

            $langCode = self::LANGUAGE_CODES[$targetId]['google'];

            // 1. 「」を一時的に{}に置換（より自然な区切り文字）
            $processedText = str_replace(['「', '」'], ['{', '}'], $text);
            Log::info('Text with brackets: ' . $processedText);

            // 2. 辞書の特殊アイテムを事前翻訳
            $processedText = preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($langCode) {
                $item = $matches[1];
                if (isset($this->dictionary['special_items'][$item][$langCode])) {
                    $translation = $this->dictionary['special_items'][$item][$langCode];
                    // 中括弧を維持して翻訳を挿入
                    return '{' . $translation . '}';
                }
                // 辞書にない場合は元の形式を維持
                return '{' . $item . '}';
            }, $processedText);

            Log::info('Pre-translated text: ' . $processedText);

            // 3. APIで翻訳
            $googleResult = $this->translateWithGoogle($processedText, $sourceId, $targetId);
            $deeplResult = $this->translateWithDeepL($processedText, $sourceId, $targetId);

            Log::info('Raw Google translation: ' . $googleResult);
            Log::info('Raw DeepL translation: ' . $deeplResult);

            // 4. 残った{}を適切な引用符に変換
            $googleResult = preg_replace('/\{([^}]+)\}/', '『$1』', $googleResult);
            $deeplResult = preg_replace('/\{([^}]+)\}/', '『$1』', $deeplResult);

            // 5. 不要な空白の調整
            $googleResult = preg_replace('/』\s+/', '』', $googleResult);
            $deeplResult = preg_replace('/』\s+/', '』', $deeplResult);

            Log::info('Final Google translation: ' . $googleResult);
            Log::info('Final DeepL translation: ' . $deeplResult);

            return [
                'success' => true,
                'results' => [
                    'google' => [
                        'translation' => $googleResult,
                        'back_translation' => $this->translateWithGoogle($googleResult, $targetId, $sourceId),
                        'total_score' => $this->calculateFinalScore($text, $this->translateWithGoogle($googleResult, $targetId, $sourceId), $targetId)
                    ],
                    'deepl' => [
                        'translation' => $deeplResult,
                        'back_translation' => $this->translateWithGoogle($deeplResult, $targetId, $sourceId),
                        'total_score' => $this->calculateFinalScore($text, $this->translateWithGoogle($deeplResult, $targetId, $sourceId), $targetId)
                    ],
                    'recommended' => $this->calculateFinalScore($text, $this->translateWithGoogle($googleResult, $targetId, $sourceId), $targetId) >
                                    $this->calculateFinalScore($text, $this->translateWithGoogle($deeplResult, $targetId, $sourceId), $targetId)
                                    ? $googleResult : $deeplResult
                ]
            ];

        } catch (Exception $e) {
            Log::error('Translation error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}


