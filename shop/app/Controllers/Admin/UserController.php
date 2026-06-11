<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Mailer;
use App\Models\UserModel;

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $keyword  = $this->request->getGet('q') ?? '';
        $role     = $this->request->getGet('role') ?? '';
        $status   = $this->request->getGet('status') ?? '';
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage  = 20;

        $builder = $this->userModel->builder();

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('nickname', $keyword)
                ->orLike('email', $keyword)
                ->orLike('username', $keyword)
                ->orLike('phone', $keyword)
                ->groupEnd();
        }
        if ($role !== '') {
            $builder->where('role', $role);
        }
        if ($status === 'unverified') {
            // 이메일 미인증: is_active=0 이면서 verify_token이 있는 일반 가입자
            $builder->where('is_active', 0)
                    ->where('email_verify_token IS NOT NULL');
        } elseif ($status === '1') {
            $builder->where('is_active', 1);
        } elseif ($status === '0') {
            // 비활성(관리자 차단): is_active=0 이면서 token이 없는 경우
            $builder->where('is_active', 0)
                    ->where('email_verify_token IS NULL');
        }

        $total = (clone $builder)->countAllResults(false);
        $users = $builder->orderBy('id', 'DESC')
                         ->limit($perPage, ($page - 1) * $perPage)
                         ->get()->getResultArray();

        return $this->render('admin/users/list', [
            'users'       => $users,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => (int) ceil($total / $perPage),
            'keyword'     => $keyword,
            'role'        => $role,
            'status'      => $status,
        ]);
    }

    public function edit(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', '회원을 찾을 수 없습니다.');
        }

        return $this->render('admin/users/edit', ['member' => $user]);
    }

    public function update(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', '회원을 찾을 수 없습니다.');
        }

        if ((int) $id === (int) session()->get('user_id')) {
            return redirect()->back()->with('error', '본인 계정은 수정할 수 없습니다.');
        }

        $grade = $this->request->getPost('grade');
        $validGrades = ['bronze', 'silver', 'gold', 'platinum'];
        $data = [
            'nickname'  => $this->request->getPost('nickname'),
            'phone'     => $this->request->getPost('phone') ?: null,
            'gender'    => $this->request->getPost('gender') ?: null,
            'birthday'  => $this->request->getPost('birthday') ?: null,
            'role'      => $this->request->getPost('role'),
            'grade'     => in_array($grade, $validGrades, true) ? $grade : 'bronze',
            'is_active' => (int) $this->request->getPost('is_active'),
        ];

        // 관리자가 is_active=1 로 변경하면 미인증 토큰도 정리
        if ($data['is_active'] === 1 && $user['email_verify_token']) {
            $data['email_verify_token']    = null;
            $data['email_verify_token_at'] = null;
        }

        $this->userModel->update($id, $data);

        return redirect()->to('/admin/users')->with('success', '회원 정보가 수정되었습니다.');
    }

    public function delete(int $id)
    {
        if ((int) $id === (int) session()->get('user_id')) {
            return redirect()->back()->with('error', '본인 계정은 삭제할 수 없습니다.');
        }

        $this->userModel->delete($id);

        return redirect()->to('/admin/users')->with('success', '회원이 삭제되었습니다.');
    }

    /** 관리자 수동 이메일 인증 처리 */
    public function manualVerify(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', '회원을 찾을 수 없습니다.');
        }

        $this->userModel->clearVerifyToken($id);

        return redirect()->back()->with('success', '이메일 인증이 완료 처리되었습니다.');
    }

    /** 인증 메일 재발송 */
    public function resendVerify(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', '회원을 찾을 수 없습니다.');
        }

        if ($user['is_active']) {
            return redirect()->back()->with('error', '이미 인증된 회원입니다.');
        }

        $token = $this->userModel->generateVerifyToken($id);
        (new Mailer($this->viewData['settings'] ?? []))->sendVerify($user, $token);

        return redirect()->back()->with('success', '인증 메일을 재발송했습니다.');
    }

}

