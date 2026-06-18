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
}
