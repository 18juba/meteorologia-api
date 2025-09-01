<?php

namespace App\Services;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class AIService
{
    public static function hello(): string
    {
        $response = Prism::text()
            ->using(Provider::Ollama, 'gemma3:1b')
            ->withSystemPrompt(view('prompts.system-prompt'))
            ->withPrompt('Acabei de entrar no sistema, me dê saudações com uma mensagem de boas vindas, também faça uma lista de coisas que eu possa fazer')
            ->asText();

        return $response->text;
    }
}
