<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Libraries\Seo\IndexNowService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * IndexNow 키 검증 파일 — GET /indexnow-key.txt.
 * 검색엔진이 소유권 확인용으로 조회한다. 키 미설정 시 404.
 */
class IndexNowController extends BaseController
{
    public function key(): ResponseInterface
    {
        $key = (new IndexNowService())->getKey();

        if ($key === '') {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($key);
    }
}
