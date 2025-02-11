<?php

namespace App\Services\Translation;

use Exception;
use Illuminate\Support\Facades\Cache;

class TranslatorService
{
    private $evaluationService; //翻訳の品質を評価するやつ
    private $translationProvider; // 実際の翻訳APIを叩いたりするやつ
    protected $dictionary; // メニューとか専門用語の辞書

    /**
     * 言語コード
     */
    private const LANGUAGE_CODES = [
        '1' => ['deepl' => 'JA', 'google' => 'ja'],
        '2' => ['deepl' => 'EN', 'google' => 'en'],
        '3' => ['deepl' => 'ZH', 'google' => 'zh'],
        '4' => ['deepl' => 'KO', 'google' => 'ko']
    ];

    public function __construct(
        TranslationEvaluationService $evaluationService,
        TranslationProviderService $translationProvider
    ) {
        $this->evaluationService = $evaluationService;
        $this->translationProvider = $translationProvider;
        $this->loadDictionary();
    }

    /**
     * 辞書データを読み込む
     *
     * 毎回ファイルを読み込むのは重いので、
     * 1時間だけキャッシュするようにしてます。
     */
    private function loadDictionary(): array
    {
        return Cache::remember('translation_dictionary', 3600, function () {
            $dictionaryPath = config_path('dictionary.json');
            // ファイルが見つからない時のエラーを防ぐため@をつけてます
            $dictionaryContent = @file_get_contents($dictionaryPath);

            if ($dictionaryContent === false) {
                throw new Exception("Failed to read dictionary file: $dictionaryPath");
            }

            $dictionary = json_decode($dictionaryContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid dictionary JSON: " . json_last_error_msg());
            }

            return $dictionary;
        });
    }

    /**
     * カテゴリーごとの翻訳を適用するメソッド
     *
     * 翻訳の処理を小分けにするために
     */
    private function applyTranslationCategory(array $items, string $text, string $targetLang): string
    {
        foreach ($items as $jp => $translations) {
            if (isset($translations[$targetLang])) {
                $text = str_replace($jp, $translations[$targetLang], $text);
            }
        }
        return $text;
    }

    /**
     * カテゴリーごとの翻訳を適用するメソッド
     *
     * メニューとか、トッピングとか、カテゴリーごとに
     * 辞書の内容を適用していきます
     */
    private function applyDictionary(string $text, string $target): string
    {
        $dictionary = $this->loadDictionary();
        $targetLang = strtolower(self::LANGUAGE_CODES[$target]['google']);

        // メニューの翻訳
        if (isset($dictionary['menu_categories'])) {
            foreach ($dictionary['menu_categories'] as $category) {
                $text = $this->applyTranslationCategory($category, $text, $targetLang);
            }
        }

        // トッピングとかサイドメニューとかも翻訳
        $categories = ['toppings', 'side_dishes', 'special_items'];
        foreach ($categories as $category) {
            if (isset($dictionary[$category])) {
                $text = $this->applyTranslationCategory($dictionary[$category], $text, $targetLang);
            }
        }

        return $text;
    }

    /**
     * メインの翻訳処理
     *
     * try-catchでエラー処理をしてます
     */
    public function translate($text, $sourceId, $targetId)
    {
        try {
            $result = $this->compareTranslations($text, $sourceId, $targetId);

            return [
                'success' => true,
                'results' => $result
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 翻訳の品質を比べるメソッド
     *
     * こんな感じで翻訳してます：
     * 1. まず辞書で専門用語を置き換え
     * 2. DeepLとGoogleで翻訳
     * 3. もう一回逆翻訳して品質チェック
     * 4. スコアが高い方を採用
     *
     */
    private function compareTranslations(string $text, string $source, string $target): array
    {
        // まずは辞書を使って専門用語を置き換え
        $preprocessedText = $this->applyDictionary($text, $target);

        // 並列で翻訳を実行
        $translations = [
            'google' => $this->translationProvider->translateWithGoogle($preprocessedText, $source, $target),
            'deepl' => $this->translationProvider->translateWithDeepL($preprocessedText, $source, $target)
        ];

        // 翻訳結果を元の言語に戻してみて、どれくらい元の文章に近いか確認
        $backTranslations = [
            'google' => $this->translationProvider->translateWithGoogle($translations['google'], $target, $source),
            'deepl' => $this->translationProvider->translateWithDeepL($translations['deepl'], $target, $source)
        ];

        // それぞれの翻訳の品質をスコア化
        $scores = [
            'google' => $this->evaluationService->calculateFinalScore($text, $backTranslations['google'], $target),
            'deepl' => $this->evaluationService->calculateFinalScore($text, $backTranslations['deepl'], $target)
        ];

        // スコアが高い方を採用
        $recommendedTranslation = $scores['deepl']['totalScore'] > $scores['google']['totalScore']
            ? $translations['deepl']
            : $translations['google'];

        return [
            'google' => [
                'translation' => $translations['google'],
                'backTranslation' => $backTranslations['google'],
                'totalScore' => $scores['google']['totalScore']
            ],
            'deepl' => [
                'translation' => $translations['deepl'],
                'backTranslation' => $backTranslations['deepl'],
                'totalScore' => $scores['deepl']['totalScore']
            ],
            'recommended' => $recommendedTranslation
        ];
    }
}
