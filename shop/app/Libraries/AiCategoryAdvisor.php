<?php

namespace App\Libraries;

use App\Libraries\AiProvider\AiProviderInterface;
use App\Libraries\AiProvider\ClaudeProvider;
use App\Libraries\AiProvider\GroqProvider;

class AiCategoryAdvisor
{
    public static function create(): AiProviderInterface
    {
        $settings = model('SettingModel')->getAllAsMap();
        $provider = $settings['ai_provider'] ?? env('AI_PROVIDER', 'groq');

        return match ($provider) {
            'claude' => new ClaudeProvider(),
            default  => new GroqProvider(),
        };
    }
}
