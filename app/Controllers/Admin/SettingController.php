<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    private SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index(string $group = 'general')
    {
        // oauth 탭은 별도 뷰 (읽기 전용 가이드)
        if ($group === 'oauth') {
            return $this->render('admin/settings/oauth', ['group' => 'oauth']);
        }

        $allowed = ['general', 'contact', 'sns', 'seo', 'footer'];
        if (! in_array($group, $allowed)) $group = 'general';

        return $this->render('admin/settings/index', [
            'group'    => $group,
            'settings' => $this->settingModel->getGroup($group),
        ]);
    }

    public function update(string $group = 'general')
    {
        $postData = $this->request->getPost();
        unset($postData[csrf_token()]);

        $this->settingModel->saveSettings($postData);

        return redirect()->to("/admin/settings/{$group}")->with('success', '설정이 저장되었습니다.');
    }
}
