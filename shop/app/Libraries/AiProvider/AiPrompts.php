<?php

namespace App\Libraries\AiProvider;

/**
 * AI 시스템 프롬프트 저장소.
 *
 * settings 테이블의 'ai_prompt_{key}' 값이 비어있지 않으면 그것을, 없으면 코드 기본값을 사용한다.
 * 두 Provider(Claude/Groq)가 공유하여 프롬프트 중복을 제거하고,
 * 관리자가 코드 수정 없이 톤·정책을 조정할 수 있게 한다.
 */
class AiPrompts
{
    /** settings 키 접두사 */
    public const PREFIX = 'ai_prompt_';

    /** 편집 가능한 프롬프트 키 목록 (설정 UI에서 사용) */
    public const KEYS = ['category', 'description', 'qna'];

    /**
     * 프롬프트를 반환하고 {placeholder}를 치환한다.
     *
     * @param string               $key  category|description|qna
     * @param array<string,string> $vars 치환할 변수 (예: ['categories' => '...'])
     */
    public static function render(string $key, array $vars = []): string
    {
        $tpl = self::get($key);
        foreach ($vars as $name => $value) {
            $tpl = str_replace('{' . $name . '}', $value, $tpl);
        }
        return $tpl;
    }

    /** 설정값 우선, 비어있으면 코드 기본값으로 폴백 */
    public static function get(string $key): string
    {
        $settings = model('SettingModel')->getAllAsMap();
        $custom   = trim((string) ($settings[self::PREFIX . $key] ?? ''));

        return $custom !== '' ? $custom : (self::defaults()[$key] ?? '');
    }

    /**
     * 코드 기본 프롬프트.
     *
     * @return array<string,string>
     */
    public static function defaults(): array
    {
        return [
            'category' => <<<'PROMPT'
당신은 쇼핑몰 상품 카테고리 분류 전문가입니다.
아래 카테고리 목록을 참고하여 상품에 가장 적합한 카테고리를 최대 3개 추천하세요.

카테고리 목록 (id: 이름):
{categories}

반드시 JSON 형식으로만 응답하세요: {"category_ids": [1, 2]}
카테고리 목록에 없는 ID는 절대 포함하지 마세요.
PROMPT,

            'description' => <<<'PROMPT'
당신은 쇼핑몰 상품 설명 작성 전문가입니다.
상품명과 기존 설명을 참고하여 고객의 구매욕을 자극하는 매력적인 상품 설명을 한국어로 작성하세요.

[중요] 출력은 반드시 HTML만 사용하세요. 마크다운 문법은 절대 사용하지 마세요.
허용 태그: <p> <strong> <ul> <li> <br>
금지 문법: ** ## -- ``` _ (마크다운 볼드·헤딩·리스트·코드블록 모두 금지)

올바른 출력 예시:
<p>이 상품은 <strong>고품질 면 소재</strong>로 제작된 티셔츠입니다.</p>
<ul>
<li>세탁기 세탁 가능한 편리함</li>
<li>통기성이 뛰어나 사계절 착용 가능</li>
</ul>
<p>일상복으로 완벽한 선택입니다.</p>

규칙:
- 상품의 특징과 장점을 명확하게 강조
- 자연스럽고 설득력 있는 문체 사용
- 300~500자 내외로 간결하게 작성
PROMPT,

            'qna' => <<<'PROMPT'
당신은 쇼핑몰 고객 서비스 담당자입니다.
상품 문의에 대해 친절하고 전문적인 답변을 한국어로 작성하세요.

규칙:
- 인사말로 시작하고 감사 인사로 마무리
- 문의 내용에 직접적으로 답변
- 확실하지 않은 정보는 "확인 후 안내드리겠습니다"로 처리
- 2~4문장의 간결한 답변
- 일반 텍스트로 작성 (HTML·마크다운 사용 금지)
PROMPT,
        ];
    }

    /** 설정 UI 라벨 */
    public static function labels(): array
    {
        return [
            'category'    => '카테고리 추천 프롬프트',
            'description' => '상품 설명 생성 프롬프트',
            'qna'         => '상품 문의 답변 프롬프트',
        ];
    }
}
