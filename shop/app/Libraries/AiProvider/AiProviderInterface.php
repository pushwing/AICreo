<?php

namespace App\Libraries\AiProvider;

interface AiProviderInterface
{
    /**
     * 상품명과 설명을 바탕으로 추천 카테고리 ID 배열을 반환한다.
     *
     * @param  string $name        상품명
     * @param  string $description 상품 설명
     * @param  array  $tree        CategoryModel::getTree() 반환값
     * @return int[]               추천 카테고리 ID 배열 (최대 3개)
     */
    public function suggestCategories(string $name, string $description, array $tree): array;

    /**
     * 상품명과 기존 설명을 바탕으로 새로운 상품 설명(HTML)을 생성해 반환한다.
     *
     * @param  string $name        상품명
     * @param  string $description 기존 설명 (참고용, 비어있어도 됨)
     * @return string              생성된 HTML 설명 (실패 시 빈 문자열)
     */
    public function generateDescription(string $name, string $description): string;

    /**
     * 상품 문의에 대한 답변 초안을 생성해 반환한다.
     *
     * @param  string $productName        상품명
     * @param  string $productDescription 상품 설명 (참고용)
     * @param  string $questionTitle      문의 제목
     * @param  string $questionContent    문의 내용
     * @return string                     생성된 답변 텍스트 (실패 시 빈 문자열)
     */
    public function generateQnaAnswer(string $productName, string $productDescription, string $questionTitle, string $questionContent): string;

    /**
     * 상품 리뷰 목록을 요약하고 장단점·감성을 분석한다.
     *
     * @param  string $productName 상품명
     * @param  array  $reviews     [['id' => int, 'content' => string], ...]
     * @return array{summary:string, pros:string[], cons:string[], sentiment:string, negative_review_ids:int[]}
     *               실패 시 빈 요약(summary='')을 담은 기본 구조를 반환한다.
     *               sentiment: 'positive' | 'mixed' | 'negative'
     */
    public function summarizeReviews(string $productName, array $reviews): array;
}
