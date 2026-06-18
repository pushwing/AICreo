<?php

namespace Tests\Unit;

use App\Libraries\AiCategoryAdvisor;
use App\Libraries\AiProvider\ClaudeProvider;
use App\Libraries\AiProvider\GroqProvider;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * AI 카테고리 추천 기능 검증
 *
 * - AiCategoryAdvisor Factory: env 값에 따른 Provider 선택
 * - GroqProvider: flattenTree, parseResponse 순수 로직
 * - ClaudeProvider: flattenTree, parseResponse 순수 로직
 * - 실제 Groq API 통합 테스트 (GROQ_API_KEY 설정 시)
 */

// ── 테스트용 서브클래스 — protected 메서드 노출 ──────────────────────────────
class TestableGroqProvider extends GroqProvider
{
    public function exposeFlattenTree(array $tree): string
    {
        return $this->flattenTree($tree);
    }

    public function exposeParseResponse(string $raw): array
    {
        return $this->parseResponse($raw);
    }

    public function exposeBuildPrompt(string $name, string $desc, array $tree): string
    {
        return $this->buildPrompt($name, $desc, $tree);
    }
}

class TestableClaudeProvider extends ClaudeProvider
{
    public function exposeFlattenTree(array $tree): string
    {
        return $this->flattenTree($tree);
    }

    public function exposeParseResponse(string $raw): array
    {
        return $this->parseResponse($raw);
    }
}

// ── 테스트 클래스 ─────────────────────────────────────────────────────────────
final class AiCategoryAdvisorTest extends CIUnitTestCase
{
    private string $originalProvider = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalProvider = $_ENV['AI_PROVIDER'] ?? getenv('AI_PROVIDER') ?: '';
    }

    protected function tearDown(): void
    {
        $this->setProvider($this->originalProvider);
        parent::tearDown();
    }

    private function setProvider(string $value): void
    {
        putenv("AI_PROVIDER={$value}");
        $_ENV['AI_PROVIDER'] = $value;
    }

    private function sampleTree(): array
    {
        return [
            ['id' => 1, 'name' => '상의', 'children' => [
                ['id' => 11, 'name' => '티셔츠'],
                ['id' => 12, 'name' => '셔츠'],
            ]],
            ['id' => 2, 'name' => '하의', 'children' => [
                ['id' => 21, 'name' => '청바지'],
            ]],
        ];
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    public function testFactoryReturnsGroqProviderByDefault(): void
    {
        $this->setProvider('groq');
        $this->assertInstanceOf(GroqProvider::class, AiCategoryAdvisor::create());
    }

    public function testFactoryReturnsGroqProviderWhenEnvNotSet(): void
    {
        $this->setProvider('');
        $this->assertInstanceOf(GroqProvider::class, AiCategoryAdvisor::create());
    }

    public function testFactoryReturnsClaudeProviderWhenEnvIsClaude(): void
    {
        $this->setProvider('claude');
        $this->assertInstanceOf(ClaudeProvider::class, AiCategoryAdvisor::create());
    }

    public function testFactoryReturnsGroqForUnknownProvider(): void
    {
        $this->setProvider('unknown');
        $this->assertInstanceOf(GroqProvider::class, AiCategoryAdvisor::create());
    }

    // ── GroqProvider::flattenTree ─────────────────────────────────────────────

    public function testGroqFlattenTreeFormatsParentChildren(): void
    {
        $provider = new TestableGroqProvider();
        $result   = $provider->exposeFlattenTree($this->sampleTree());

        $this->assertStringContainsString('- 1: 상의', $result);
        $this->assertStringContainsString('  - 11: 티셔츠', $result);
        $this->assertStringContainsString('  - 12: 셔츠', $result);
        $this->assertStringContainsString('- 2: 하의', $result);
        $this->assertStringContainsString('  - 21: 청바지', $result);
    }

    public function testGroqFlattenTreeEmptyReturnsEmptyString(): void
    {
        $provider = new TestableGroqProvider();
        $this->assertSame('', $provider->exposeFlattenTree([]));
    }

    public function testGroqFlattenTreeNoChildrenSkipsChildLines(): void
    {
        $provider = new TestableGroqProvider();
        $tree     = [['id' => 5, 'name' => '잡화', 'children' => []]];
        $result   = $provider->exposeFlattenTree($tree);

        $this->assertStringContainsString('- 5: 잡화', $result);
        $this->assertStringNotContainsString('  -', $result);
    }

    // ── GroqProvider::parseResponse ───────────────────────────────────────────

    public function testGroqParseResponseExtractsIds(): void
    {
        $provider = new TestableGroqProvider();
        $raw = json_encode([
            'choices' => [[
                'message' => ['content' => '{"category_ids":[1,11]}'],
            ]],
        ]);

        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([1, 11], $ids);
    }

    public function testGroqParseResponseReturnsEmptyOnMissingChoices(): void
    {
        $provider = new TestableGroqProvider();
        $ids = $provider->exposeParseResponse('{}');
        $this->assertSame([], $ids);
    }

    public function testGroqParseResponseReturnsEmptyOnMalformedContent(): void
    {
        $provider = new TestableGroqProvider();
        $raw = json_encode([
            'choices' => [[
                'message' => ['content' => 'not json'],
            ]],
        ]);
        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([], $ids);
    }

    public function testGroqParseResponseFiltersZeroIds(): void
    {
        $provider = new TestableGroqProvider();
        $raw = json_encode([
            'choices' => [[
                'message' => ['content' => '{"category_ids":[0,5,0]}'],
            ]],
        ]);
        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([5], $ids);
    }

    public function testGroqParseResponseCastsToInt(): void
    {
        $provider = new TestableGroqProvider();
        $raw = json_encode([
            'choices' => [[
                'message' => ['content' => '{"category_ids":["3","7"]}'],
            ]],
        ]);
        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([3, 7], $ids);
        foreach ($ids as $id) {
            $this->assertIsInt($id);
        }
    }

    // ── GroqProvider::buildPrompt ─────────────────────────────────────────────

    public function testGroqBuildPromptContainsNameAndDesc(): void
    {
        $provider = new TestableGroqProvider();
        $prompt   = $provider->exposeBuildPrompt('남성 티셔츠', '면 100% 반팔 티셔츠', []);

        $this->assertStringContainsString('남성 티셔츠', $prompt);
        $this->assertStringContainsString('면 100% 반팔 티셔츠', $prompt);
    }

    public function testGroqBuildPromptStripsHtmlTags(): void
    {
        $provider = new TestableGroqProvider();
        $prompt   = $provider->exposeBuildPrompt('상품', '<p>설명 <b>굵게</b></p>', []);

        $this->assertStringNotContainsString('<p>', $prompt);
        $this->assertStringNotContainsString('<b>', $prompt);
        $this->assertStringContainsString('설명 굵게', $prompt);
    }

    public function testGroqBuildPromptTruncatesLongDescription(): void
    {
        $provider   = new TestableGroqProvider();
        $longDesc   = str_repeat('가', 1000);
        $prompt     = $provider->exposeBuildPrompt('상품', $longDesc, []);

        $this->assertLessThanOrEqual(600, mb_strlen($prompt));
    }

    // ── ClaudeProvider::flattenTree ───────────────────────────────────────────

    public function testClaudeFlattenTreeMatchesGroqFormat(): void
    {
        $groq   = new TestableGroqProvider();
        $claude = new TestableClaudeProvider();

        $this->assertSame(
            $groq->exposeFlattenTree($this->sampleTree()),
            $claude->exposeFlattenTree($this->sampleTree()),
            '두 Provider의 flattenTree 출력이 동일해야 한다'
        );
    }

    // ── ClaudeProvider::parseResponse ────────────────────────────────────────

    public function testClaudeParseResponseExtractsIds(): void
    {
        $provider = new TestableClaudeProvider();
        $raw = json_encode([
            'content' => [['text' => '{"category_ids":[2,21]}']],
        ]);
        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([2, 21], $ids);
    }

    public function testClaudeParseResponseExtractsJsonEmbeddedInText(): void
    {
        $provider = new TestableClaudeProvider();
        $raw = json_encode([
            'content' => [['text' => '추천 카테고리는 다음과 같습니다: {"category_ids":[3]} 참고하세요.']],
        ]);
        $ids = $provider->exposeParseResponse($raw);
        $this->assertSame([3], $ids);
    }

    public function testClaudeParseResponseReturnsEmptyOnMissingContent(): void
    {
        $provider = new TestableClaudeProvider();
        $ids = $provider->exposeParseResponse('{}');
        $this->assertSame([], $ids);
    }

    // ── 실제 Groq API 통합 테스트 ─────────────────────────────────────────────

    public function testGroqApiReturnsValidCategoryIds(): void
    {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: '';
        if (empty($apiKey)) {
            $this->markTestSkipped('GROQ_API_KEY 미설정 — 통합 테스트 건너뜀');
        }

        $this->setProvider('groq');
        $provider = AiCategoryAdvisor::create();
        $tree     = $this->sampleTree();

        $ids = $provider->suggestCategories('남성 반팔 티셔츠', '면 100% 반팔 라운드넥 티셔츠입니다.', $tree);

        $this->assertIsArray($ids, 'Groq API 응답이 배열이어야 한다');

        $validIds = [1, 11, 12, 2, 21];
        foreach ($ids as $id) {
            $this->assertIsInt($id);
            $this->assertContains($id, $validIds, "반환된 ID {$id}는 카테고리 트리에 존재해야 한다");
        }
    }
}
