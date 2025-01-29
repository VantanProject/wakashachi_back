<?php

namespace App\Services\Translation;

/**
 * 翻訳の品質を評価するサービスクラス
 *
 * 4つの評価方法を組み合わせて、翻訳の品質をチェックします。
 * 言語ごとの特徴も考慮して、できるだけ正確なスコアを出すようにしてます。
 */
class TranslationEvaluationService
{
    /**
     * テキストを言語に合わせて分割するメソッド
     *
     * 言語によって単語の区切り方が違うので、それぞれに合った方法で分割します：
     *
     * 英語は単語ごとに分ける:
     * 「I love cats」→ ['I', 'love', 'cats']
     *
     * 中国語は1文字ずつ分ける:
     * 「我爱猫」→ ['我', '爱', '猫']
     *
     * 韓国語はスペースも大事にしながら分ける:
     * 「나는 고양이를」→ ['나', '는', ' ', '고', '양', '이', '를']
     */
    private function tokenize($text, $target)
    {
        switch ($target) {
            case 'en':
                return preg_split('/\s+/', $text);
            case 'zh':
                return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
            case 'ko':
                return preg_split('/((?<=\s)|(?=\s))/u', $text);
            default:
                return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
    }


    /**
     * 文章をn-gramに分割するメソッド
     *
     * 文章を決まった長さの部分に分けていきます。
     * これで文章の構造がどれくらい似ているか比べられます。
     *
     * 例えば「私は猫が好き」を2文字ずつ分けると:
     * 「私は」「は猫」「猫が」「が好」「好き」
     * みたいな感じです。
     */
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

    /**
     * 各言語に最適なn-gramの長さを決定するメソッド
     *
     * n-gramとは文章を連続したn個の要素で区切る方法です
     *
     * 言語ごとの設定理由：
     *
     * 英語（4-gram）：
     * - 一般的な英語の慣用句や表現が3-4単語で構成されることが多い
     * - 例：'nice to meet you'
     *
     * 中国語（3-gram）：
     * - 一般的な中国語の表現が2-3文字で意味を成すことが多い
     * - 例：'你好吗'（お元気ですか）
     *
     * 韓国語（3-gram）：
     * - 韓国語の文法構造と一般的な表現の長さを考慮
     * - 助詞などを含む一般的な表現単位に合わせている
     */
    private function getMaxNGram($target)
    {
        switch ($target) {
            case 'en':
                return 4;
            case 'zh':
                return 3;
            case 'ko':
                return 3;
            default:
                return 4;
        }
    }

    /**
     * 韓国語の自然さを評価するメソッド
     *
     * 韓国語特有の文末表現や助詞の使い方をチェックします。
     * 文の終わり方や助詞の使い方で、どれくらい自然な韓国語か判断します。
     */
    private function evaluateKoreanNaturalness(string $text): float
    {
        $score = 0.0;

        // 文末表現の評価
        $formalEndings = ['입니다', '습니다', '니다'];
        $politeEndings = ['요', '세요'];
        $hasProperEnding = false;

        foreach ($formalEndings as $ending) {
            if (mb_substr($text, -mb_strlen($ending)) === $ending) {
                $score += 0.4;
                $hasProperEnding = true;
                break;
            }
        }

        if (!$hasProperEnding) {
            foreach ($politeEndings as $ending) {
                if (mb_substr($text, -mb_strlen($ending)) === $ending) {
                    $score += 0.3;
                    break;
                }
            }
        }

        // 助詞の評価
        $particles = ['은', '는', '이', '가', '을', '를'];
        $particleCount = 0;
        foreach ($particles as $particle) {
            if (mb_strpos($text, $particle) !== false) {
                $particleCount++;
            }
        }
        $score += min(0.3, $particleCount * 0.1);

        // 句読点の評価
        $punctuationScore = 0.0;
        $commaCount = substr_count($text, ',');
        $periodCount = substr_count($text, '.');
        if ($commaCount > 0) $punctuationScore += 0.15;
        if ($periodCount > 0) $punctuationScore += 0.15;
        $score += $punctuationScore;

        return min(1.0, $score);
    }

    /**
     * 言語特性に基づいた重み付けを取得するメソッド
     *
     * 各言語の特徴に応じて評価指標の重要度を調整します：
     *
     * 英語(en):
     * - BLEU重視（0.40）：文法構造と語順が重要
     * - Jaccard係数（0.25）：語彙の一致度
     *
     * 中国語(zh):
     * - BLEU（0.35）とコサイン類似度（0.30）を重視
     * - 文字の出現パターンが重要
     *
     * 韓国語(ko):
     * - BLEUとJaccard係数（各0.35）を重視
     * - 助詞や語尾の正確性が重要
     *
     * @param string $target 対象言語コード
     * @return array 評価基準ごとの重み（合計が1.0になるよう正規化）
     */
    private function getLanguageWeights(string $target): array
    {
        switch ($target) {
            case 'en':
                return [
                    'levenshtein' => 0.15,
                    'bleu' => 0.40,
                    'jaccard' => 0.25,
                    'cosine' => 0.20,
                    'naturalness' => 0.0
                ];
            case 'zh':
                return [
                    'levenshtein' => 0.15,
                    'bleu' => 0.35,
                    'jaccard' => 0.20,
                    'cosine' => 0.30,
                    'naturalness' => 0.0
                ];
            case 'ko':
                return [
                    'levenshtein' => 0.15,
                    'bleu' => 0.35,
                    'jaccard' => 0.35,
                    'cosine' => 0.15
                ];
            default:
                return [
                    'levenshtein' => 0.10,
                    'bleu' => 0.35,
                    'jaccard' => 0.20,
                    'cosine' => 0.35,
                    'naturalness' => 0.0
                ];
        }
    }


    /**
     * レーベンシュタイン類似度を計算するメソッド
     *
     * 2つの文章がどれくらい似ているかを、
     * 文字の追加・削除・置換の回数で判断します。
     *
     * 例えば「りんご」と「りんか」なら、
     * 「ご」を「か」に変えるだけなので、
     * かなり似てると判断されます。
     */
    public function calculateLevenshteinSimilarity($original, $backTranslated)
    {
        $maxLength = max(strlen($original), strlen($backTranslated));
        return $maxLength === 0 ? 1.0 : 1 - (levenshtein(mb_strtolower($original), mb_strtolower($backTranslated)) / $maxLength);
    }

    /**
      * Jaccard類似度を計算するメソッド
      *
      * 2つの文章で使用されている文字の集合を比較して、
      * どれだけ共通の文字が使われているかを評価します
      *
      * 計算の考え方：
      * 1. 各文章を一意の文字の集合に変換
      *    例：「こんにちは」→ [こ,ん,に,ち,は]
      *
      * 2. 2つの集合について以下を計算
      *    - 共通部分（両方の文章で使用されている文字）
      *    - 和集合（どちらかの文章で使用されている文字全て）
      *
      * 3. 「共通部分の数 ÷ 和集合の数」で類似度を算出
      *    例：
      *    文章A：「こんにちは」→ 5文字
      *    文章B：「こんばんは」→ 5文字
      *    共通：「こんは」→ 3文字
      *    和集合：「こんにちばは」→ 6文字
      *    類似度 = 3/6 = 0.5
      */
    public function calculateJaccardSimilarity($original, $backTranslated)
    {
        $originalSet = array_unique(str_split(mb_strtolower($original)));
        $backTranslatedSet = array_unique(str_split(mb_strtolower($backTranslated)));

        $intersection = count(array_intersect($originalSet, $backTranslatedSet));
        $union = count(array_unique(array_merge($originalSet, $backTranslatedSet)));

        return $union === 0 ? 1.0 : $intersection / $union;
    }


    /**
     * テキストを文字出現頻度ベクトルに変換するメソッド
     *
     * 入力テキストを文字単位で分解し、各文字の出現回数をカウントして
     * 頻度ベクトルを生成します。
     *
     * 例：
     * 入力: "hello"
     * 出力: ['h' => 1, 'e' => 1, 'l' => 2, 'o' => 1]
     *
     * @param string $text 変換対象のテキスト
     * @return array 文字をキー、出現回数を値とする連想配列
     */
    private function textToVector(string $text): array
    {
        // 文字単位で分割
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        // 各文字の出現回数をカウント
        $vector = [];
        foreach ($chars as $char) {
            $vector[$char] = ($vector[$char] ?? 0) + 1;
        }

        return $vector;
    }

    /**
      * コサイン類似度を計算するメソッド
      *
      * 2つの文章を文字の出現頻度ベクトルに変換し、
      * そのベクトル間の角度から類似度を計算します
      *
      * 計算の考え方：
      * 1. 各文章を文字の出現回数のベクトルに変換
      *    例：「こんにちは」
      *    こ：1回, ん：1回, に：1回, ち：1回, は：1回
      *
      * 2. 2つのベクトルの内積と大きさを計算
      *    - 内積：同じ文字の出現回数を掛け合わせて合計
      *    - ベクトルの大きさ：各文字の出現回数の二乗和の平方根
      *
      * 3. 内積をベクトルの大きさの積で割って類似度を算出
      *    - 結果は0〜1の値（1が完全一致）
      *    - 文字の出現パターンが似ているほど高スコアに
      */
    public function calculateCosineSimilarity(string $original, string $translated): float
    {
        // 各テキストを出現頻度ベクトルに変換
        $originalVector = $this->textToVector($original);
        $translatedVector = $this->textToVector($translated);

        // 両方のベクトルで使われている文字を全部集める
        $allKeys = array_unique(array_merge(array_keys($originalVector), array_keys($translatedVector)));

        // 初期化されたベクトルを作成
        $vector1 = $this->initializeVector($originalVector, $allKeys);
        $vector2 = $this->initializeVector($translatedVector, $allKeys);

        $dotProduct = array_sum(array_map(function($a, $b) { return $a * $b; }, $vector1, $vector2));
        $magnitude1 = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector1)));
        $magnitude2 = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector2)));

        return ($magnitude1 * $magnitude2) == 0 ? 0.0 : $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * ベクトルを初期化するヘルパーメソッド
     *
     * 指定されたキーのリストに基づいて、ベクトルを初期化します。
     * 指定されたキーがベクトルに存在しない場合、対応する値は0に設定されます。
     *
     * @param array $originalVector 初期化対象のベクトル
     * @param array $allKeys ベクトルのキーのリスト
     * @return array 初期化されたベクトル
     */
    private function initializeVector(array $originalVector, array $allKeys): array
    {
        $vector = array_fill_keys($allKeys, 0);
        foreach ($originalVector as $key => $value) {
            $vector[$key] = $value;
        }
        return $vector;
    }

    /**
     * BLEU（Bilingual Evaluation Understudy）スコアを計算するメソッド
     *
     * 翻訳の品質を評価する業界標準の指標の1つです。
     *
     * 処理の流れ：
     * 1. 原文と翻訳文をそれぞれの言語に適した方法で単語や文字に分割
     * 2. n-gram（1語、2語、3語、4語のまとまり）ごとに一致度を計算
     *    例：「私は猫が好きです」
     *    1-gram：「私」「は」「猫」「が」「好き」「です」
     *    2-gram：「私は」「は猫」「猫が」「が好き」「好きです」
     * 3. 文章の長さの違いによるペナルティを計算
     * 4. 全ての評価を組み合わせて最終スコアを算出
     *
     * 返り値：0.0〜1.0（1.0が完璧な一致）
     */
    public function calculateBleuScore($reference, $candidate, $target)
    {
        $referenceTokens = $this->tokenize($reference, $target);
        $candidateTokens = $this->tokenize($candidate, $target);

        $maxN = $this->getMaxNGram($target);
        $weights = array_fill(0, $maxN, 1.0 / $maxN);
        $scores = [];

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

        $bp = min(1, exp(1 - count($referenceTokens) / max(1, count($candidateTokens))));

        $weightedScore = 0;
        for ($i = 0; $i < $maxN; $i++) {
            if ($scores[$i] > 0) {
                $weightedScore += $weights[$i] * log($scores[$i]);
            }
        }

        return max(0, min(1, $bp * exp($weightedScore)));
    }

    /**
     * 最終的な翻訳スコアを計算するメソッド
     *
     * いろんな評価方法を組み合わせて、100点満点のスコアを出します。
     * 言語ごとに重視するポイントを変えて、より正確な評価になるようにしてます。
     */
    public function calculateFinalScore(string $original, string $translated, string $target): array
    {
        // 言語ごとの重み付けを取得
        $weights = $this->getLanguageWeights($target);

        // 4つの異なる方法で類似度を計算
        $levenshtein = $this->calculateLevenshteinSimilarity($original, $translated);
        $bleu = $this->calculateBleuScore($original, $translated, $target);
        $jaccard = $this->calculateJaccardSimilarity($original, $translated);
        $cosine = $this->calculateCosineSimilarity($original, $translated);

        // 韓国語の場合のみ自然さを評価
        $naturalness = $target === 'ko' ? $this->evaluateKoreanNaturalness($translated) : 0.0;

        // 各スコアに重み付けを適用
        $weightedLevenshtein = $levenshtein * $weights['levenshtein'];
        $weightedBleu = $bleu * $weights['bleu'];
        $weightedJaccard = $jaccard * $weights['jaccard'];
        $weightedCosine = $cosine * $weights['cosine'];
        $weightedNaturalness = $naturalness * ($weights['naturalness'] ?? 0.0);

        // 最終スコアを100点満点で計算
        $totalScore = ($weightedLevenshtein + $weightedBleu + $weightedJaccard + $weightedCosine + $weightedNaturalness) * 100;

        return [
            'totalScore' => round($totalScore, 2),
        ];
    }
}
