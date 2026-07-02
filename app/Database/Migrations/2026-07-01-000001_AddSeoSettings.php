<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SEO/GEO 관리자 설정 키 추가 (#202).
 * 멀티테넌트: 클라이언트가 관리자에서 도메인·조직 관련 SEO 값을 입력.
 *
 * settings.type 은 ENUM(text·textarea·image·boolean) 이므로 유효 값만 사용한다.
 * org_type 은 'text' 로 저장하고, 위젯(select)은 뷰에서 key 로 분기한다.
 */
class AddSeoSettings extends Migration
{
    /**
     * @var list<array{key:string,value:string,label:string,type:string}>
     */
    private array $rows = [
        ['key' => 'og_default_image', 'value' => '', 'label' => 'OG 기본 이미지', 'type' => 'image'],
        ['key' => 'google_verify', 'value' => '', 'label' => 'Google 인증', 'type' => 'text'],
        ['key' => 'bing_verify', 'value' => '', 'label' => 'Bing 인증', 'type' => 'text'],
        ['key' => 'org_type', 'value' => 'Organization', 'label' => '조직 스키마 타입', 'type' => 'text'],
        ['key' => 'ai_crawlers_allow', 'value' => '1', 'label' => 'AI 크롤러 허용', 'type' => 'boolean'],
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
                'group'      => 'seo',
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
