<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\UserModel;
use Tests\Support\FeatureTestCase;

/**
 * @internal
 */
final class AuthControllerTest extends FeatureTestCase
{
    public function testLoginPageLoads(): void
    {
        $result = $this->get('auth/login');

        $result->assertStatus(200);
    }

    public function testRegisterPageLoads(): void
    {
        $result = $this->get('auth/register');

        $result->assertStatus(200);
    }

    public function testRegisterCreatesMemberAndRedirects(): void
    {
        $result = $this->post('auth/register', [
            'email'    => 'newbie@example.com',
            'password' => 'password123',
            'nickname' => '뉴비',
        ]);

        $result->assertRedirectTo('/auth/login');
        $this->assertSame('member', (new UserModel())->findByEmail('newbie@example.com')['role']);
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $result = $this->post('auth/register', [
            'email'    => 'shortpw@example.com',
            'password' => '123',
            'nickname' => '짧은',
        ]);

        $result->assertRedirect();
        $this->assertNull((new UserModel())->findByEmail('shortpw@example.com'));
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        (new UserModel())->insert([
            'username' => 'existing',
            'email'    => 'dup@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nickname' => '기존',
            'role'     => 'member',
        ]);

        $result = $this->post('auth/register', [
            'email'    => 'dup@example.com',
            'password' => 'password123',
            'nickname' => '중복',
        ]);

        $result->assertRedirect();
        // 중복 가입이 막혀 사용자 수가 그대로여야 함
        $this->assertSame(1, (new UserModel())->where('email', 'dup@example.com')->countAllResults());
    }

    public function testLoginWithValidCredentialsSetsSession(): void
    {
        (new UserModel())->insert([
            'username' => 'member1',
            'email'    => 'member1@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nickname' => '회원1',
            'role'     => 'member',
        ]);

        $result = $this->post('auth/login', [
            'email'    => 'member1@example.com',
            'password' => 'password123',
        ]);

        $result->assertRedirect();
        $this->assertSame('회원1', session()->get('user_nickname'));
        $this->assertSame('member', session()->get('user_role'));
    }

    public function testLoginWithWrongPasswordDoesNotAuthenticate(): void
    {
        (new UserModel())->insert([
            'username' => 'member2',
            'email'    => 'member2@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nickname' => '회원2',
            'role'     => 'member',
        ]);

        $result = $this->post('auth/login', [
            'email'    => 'member2@example.com',
            'password' => 'wrong-password',
        ]);

        $result->assertRedirect();
        $this->assertNull(session()->get('user_id'));
    }
}
