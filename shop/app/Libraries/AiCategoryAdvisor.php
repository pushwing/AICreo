<?php

namespace App\Libraries;

use App\Exceptions\AiKeyMissingException;
use App\Libraries\AiProvider\AiProviderInterface;
use App\Libraries\AiProvider\ClaudeProvider;
use App\Libraries\AiProvider\GroqProvider;

class AiCategoryAdvisor
{
    public static function create(): AiProviderInterface
    {
        $settings = model('SettingModel')->getAllAsMap();
        $provider = $settings['ai_provider'] ?? env('AI_PROVIDER', 'groq');

        if ($provider === 'claude') {
            $key = $settings['anthropic_api_key'] ?? env('ANTHROPIC_API_KEY', '');
            if ($key === '') {
                throw new AiKeyMissingException('Claude');
            }
            return new ClaudeProvider();
        }

        $key = $settings['groq_api_key'] ?? env('GROQ_API_KEY', '');
        if ($key === '') {
            throw new AiKeyMissingException('Groq');
        }
        return new GroqProvider();
    }
}
