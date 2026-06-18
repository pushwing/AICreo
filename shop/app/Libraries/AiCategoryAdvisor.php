<?php

namespace App\Libraries;

use App\Libraries\AiProvider\AiProviderInterface;
use App\Libraries\AiProvider\ClaudeProvider;
use App\Libraries\AiProvider\GroqProvider;

class AiCategoryAdvisor
{
    public static function create(): AiProviderInterface
    {
        return match (env('AI_PROVIDER', 'groq')) {
            'claude' => new ClaudeProvider(),
            default  => new GroqProvider(),
        };
    }
}
