<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * admin 컨트롤러(auth:admin 보호) HTTP 테스트 공용 베이스.
 *
 * 시드된 관리자(id=1, role=admin) 세션을 제공한다.
 *
 * @internal
 */
abstract class AdminTestCase extends FeatureTestCase
{
    /**
     * 시드 관리자 세션 (AuthFilter 는 user_id + user_role 을 확인).
     *
     * @var array<string, mixed>
     */
    protected array $adminSession = [
        'user_id'       => 1,
        'user_nickname' => '관리자',
        'user_role'     => 'admin',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $memberSession = [
        'user_id'       => 2,
        'user_nickname' => '회원',
        'user_role'     => 'member',
    ];
}
