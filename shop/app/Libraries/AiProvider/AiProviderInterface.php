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
}
