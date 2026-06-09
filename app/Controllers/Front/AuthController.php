<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/');
        }
        return $this->render('auth/login');
    }

    public function loginProcess()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $this->userModel->findByEmail($this->request->getPost('email'));

        if (! $user || ! password_verify($this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', '이메일 또는 비밀번호가 올바르지 않습니다.');
        }

        session()->set([
            'user_id'       => $user['id'],
            'user_nickname' => $user['nickname'],
            'user_role'     => $user['role'],
        ]);

        $this->userModel->updateLastLogin($user['id']);

        return redirect()->to(session()->getTempdata('redirect_url') ?? '/');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login');
    }

    public function register()
    {
        return $this->render('auth/register');
    }

    public function registerProcess()
    {
        $rules = [
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'nickname' => 'required|min_length[2]|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->insert([
            'email'    => $this->request->getPost('email'),
            'username' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'nickname' => $this->request->getPost('nickname'),
            'role'     => 'member',
        ]);

        return redirect()->to('/auth/login')->with('success', '회원가입이 완료되었습니다. 로그인해주세요.');
    }
}
