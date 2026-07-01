<?php

declare(strict_types=1);

namespace App\Libraries\Seo;

use Throwable;

/**
 * IndexNow — 콘텐츠 발행/수정 시 변경 URL 을 Bing·Naver·Yandex 등에 즉시 제출.
 *
 * - 키는 .env 의 INDEXNOW_KEY 로 설정. 미설정이면 비활성(no-op).
 * - 로컬 도메인(localhost 등)은 제출하지 않는다.
 * - 외부 호출 실패는 요청 흐름을 막지 않도록 타임아웃 + 예외 로깅으로 흡수한다.
 *
 * 참고: https://www.indexnow.org  (Brave/Claude 는 미참여 — §6.1)
 */
class IndexNowService
{
    private const ENDPOINT     = 'https://api.indexnow.org/indexnow';
    private const KEY_FILENAME = 'indexnow-key.txt';
    private const TIMEOUT      = 5;

    private string $key;
    private string $baseUrl;

    public function __construct(?string $key = null, ?string $baseUrl = null)
    {
        $this->key     = $key ?? (string) env('INDEXNOW_KEY', '');
        $this->baseUrl = $baseUrl ?? (string) config('App')->baseURL;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * 키가 있고 운영 도메인일 때만 활성.
     */
    public function isEnabled(): bool
    {
        return $this->key !== '' && ! $this->isLocalHost();
    }

    /**
     * 변경 URL 제출. 비활성이면 아무것도 하지 않고 false.
     *
     * @param list<string> $urls
     */
    public function submit(array $urls): bool
    {
        $urls = array_values(array_filter($urls, static fn (string $u): bool => $u !== ''));
        if ($urls === [] || ! $this->isEnabled()) {
            return false;
        }

        $host = (string) parse_url($this->baseUrl, PHP_URL_HOST);

        try {
            service('curlrequest')->post(self::ENDPOINT, [
                'timeout' => self::TIMEOUT,
                'json'    => [
                    'host'        => $host,
                    'key'         => $this->key,
                    'keyLocation' => rtrim($this->baseUrl, '/') . '/' . self::KEY_FILENAME,
                    'urlList'     => $urls,
                ],
            ]);

            return true;
        } catch (Throwable $e) {
            log_message('error', 'IndexNow 제출 실패: ' . $e->getMessage());

            return false;
        }
    }

    private function isLocalHost(): bool
    {
        $host = (string) parse_url($this->baseUrl, PHP_URL_HOST);

        return $host === ''
            || in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
    }
}
