<?php

namespace App\Libraries;

class NaverShoppingProvider
{
    private string $clientId;
    private string $clientSecret;
    private string $endpoint = 'https://openapi.naver.com/v1/search/shop.json';

    public function __construct()
    {
        $settings           = model('SettingModel')->getAllAsMap();
        $this->clientId     = $settings['naver_shopping_client_id']     ?? '';
        $this->clientSecret = $settings['naver_shopping_client_secret'] ?? '';
    }

    /**
     * 네이버 쇼핑 상품 검색
     *
     * @return array{items: list<array{title:string,image:string,lprice:string,hprice:string,mallName:string,brand:string,category1:string,link:string}>, total: int}
     */
    public function search(string $keyword, int $display = 10, int $start = 1): array
    {
        if ($keyword === '' || $this->clientId === '' || $this->clientSecret === '') {
            return ['items' => [], 'total' => 0];
        }

        $url = $this->endpoint . '?' . http_build_query([
            'query'   => $keyword,
            'display' => $display,
            'start'   => $start,
            'sort'    => 'sim',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'X-Naver-Client-Id: '     . $this->clientId,
                'X-Naver-Client-Secret: ' . $this->clientSecret,
            ],
        ]);

        $raw = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            return ['items' => [], 'total' => 0];
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['items'])) {
            return ['items' => [], 'total' => 0];
        }

        $items = array_map(function (array $item): array {
            return [
                'title'     => html_entity_decode(strip_tags($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'image'     => $item['image']    ?? '',
                'lprice'    => $item['lprice']   ?? '0',
                'hprice'    => $item['hprice']   ?? '0',
                'mallName'  => $item['mallName'] ?? '',
                'brand'     => $item['brand']    ?? '',
                'category1' => $item['category1'] ?? '',
                'link'      => $item['link']     ?? '',
            ];
        }, $data['items']);

        return [
            'items' => $items,
            'total' => (int) ($data['total'] ?? 0),
        ];
    }
}
