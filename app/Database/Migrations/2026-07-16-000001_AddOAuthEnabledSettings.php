<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 소셜 로그인 제공자별 On/Off 설정 키 추가 (#234).
 */
class AddOAuthEnabledSettings extends Migration
{
    /**
     * @var list<array{key:string,value:string,label:string,type:string}>
     */
    private array $rows = [
        ['key' => 'oauth_naver_enabled', 'value' => '1', 'label' => '네이버 로그인 사용', 'type' => 'boolean'],
        ['key' => 'oauth_kakao_enabled', 'value' => '1', 'label' => '카카오 로그인 사용', 'type' => 'boolean'],
        ['key' => 'oauth_google_enabled', 'value' => '1', 'label' => '구글 로그인 사용', 'type' => 'boolean'],
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->rows as $row) {
            $exists = $this->db->table('settings')->where('key', $row['key'])->get()->getRow();
            if ($exists) {
                continue;
            }

            $this->db->table('settings')->insert([
                'group'      => 'oauth',
                'key'        => $row['key'],
                'value'      => $row['value'],
                'label'      => $row['label'],
                'type'       => $row['type'],
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $keys = array_column($this->rows, 'key');
        $this->db->table('settings')->whereIn('key', $keys)->delete();
    }
}
