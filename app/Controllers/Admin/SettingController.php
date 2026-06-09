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
        if ($group === 'oauth') {
            return $this->render('admin/settings/oauth', ['group' => 'oauth']);
        }

        if ($group === 'theme') {
            return $this->render('admin/settings/theme', [
                'group'         => 'theme',
                'activeTheme'   => $this->settingModel->getAllAsMap()['active_theme'] ?? 'default',
                'availableThemes' => $this->scanThemes(),
            ]);
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

        if ($group === 'theme') {
            $theme = $postData['active_theme'] ?? 'default';
            $this->settingModel->saveSettings(['active_theme' => $theme]);
            return redirect()->to('/admin/settings/theme')->with('success', "테마가 '{$theme}'으로 변경되었습니다.");
        }

        $this->settingModel->saveSettings($postData);

        return redirect()->to("/admin/settings/{$group}")->with('success', '설정이 저장되었습니다.');
    }

    /** app/Views/themes/ 하위 폴더를 스캔해 테마 목록 반환 */
    private function scanThemes(): array
    {
        $base   = APPPATH . 'Views/themes/';
        $themes = [];

        if (! is_dir($base)) {
            return $themes;
        }

        foreach (new \DirectoryIterator($base) as $item) {
            if ($item->isDir() && ! $item->isDot()) {
                $name = $item->getFilename();
                $themes[$name] = [
                    'name'      => $name,
                    'label'     => ucfirst($name),
                    'has_css'   => is_file(FCPATH . "themes/{$name}/css/style.css"),
                    'has_layout'=> is_file(APPPATH . "Views/themes/{$name}/layouts/main.php"),
                ];
            }
        }

        ksort($themes);
        return $themes;
    }
}
