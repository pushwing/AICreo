<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class ScheduleController extends BaseController
{
    private SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index(): string
    {
        $jobs = $this->settingModel->getGroup('schedule');

        return $this->render('admin/schedule/index', [
            'pageTitle' => '배치 작업 관리',
            'jobs'      => $jobs,
        ]);
    }

    public function toggle(string $key): \CodeIgniter\HTTP\RedirectResponse
    {
        $existing = $this->settingModel->where('group', 'schedule')->where('key', $key)->first();

        if (! $existing) {
            return redirect()->to('/admin/schedule')->with('error', '존재하지 않는 설정 키입니다.');
        }

        $newValue = $existing['value'] === '1' ? '0' : '1';
        $this->settingModel->saveSettings([$key => $newValue]);

        $label  = $existing['label'];
        $status = $newValue === '1' ? '활성화' : '비활성화';

        return redirect()->to('/admin/schedule')->with('success', "{$label} — {$status}되었습니다.");
    }
}
