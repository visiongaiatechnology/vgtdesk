<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait OpenAiGatewayTrait
{
    private function requestOpenAICompletion(string $apiKey, string $model, array $messages, string $reasoningEffort, int $maxTokens): array
    {
        $parts = \explode('/', $model);
        $realModel = $parts[1] ?? $model;

        $bodyPayload = [
            'model' => $realModel,
            'messages' => $messages,
            'temperature' => 0.6,
            'top_p' => 0.95,
            'max_completion_tokens' => $maxTokens,
            'stream' => false,
        ];

        if (\str_starts_with($realModel, 'o1') || \str_starts_with($realModel, 'o3')) {
            $bodyPayload['reasoning_effort'] = $reasoningEffort === 'none' ? 'medium' : $reasoningEffort;
            unset($bodyPayload['temperature']);
            unset($bodyPayload['top_p']);
        }

        $encodedBody = \json_encode($bodyPayload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        $response = \wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'method' => 'POST',
            'timeout' => 120,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'sslverify' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => $encodedBody,
            'data_format' => 'body',
        ]);

        if (\is_wp_error($response)) {
            $this->throwTypedException('OpenAI gateway connection failed.', 'storage');
        }

        $statusCode = (int) \wp_remote_retrieve_response_code($response);
        $responseBody = (string) \wp_remote_retrieve_body($response);
        if ($statusCode !== 200) {
            \error_log('[VGTA OpenAI Error Body] Code ' . $statusCode . ': ' . $responseBody);
            $message = $this->extractGatewayErrorMessage($responseBody);
            throw new ValidationException('OpenAI gateway HTTP ' . $statusCode . ': ' . $message);
        }

        $decoded = \json_decode($responseBody, true, 64, \JSON_THROW_ON_ERROR);
        $message = $decoded['choices'][0]['message'];
        $content = isset($message['content']) && \is_string($message['content']) ? \trim($message['content']) : '';
        
        $reasoning = '';
        if (isset($message['reasoning_content']) && \is_string($message['reasoning_content'])) {
            $reasoning = \trim($message['reasoning_content']);
        }

        return [
            'content' => $content,
            'reasoning' => $reasoning,
            'stage_content' => $content,
            'usage' => isset($decoded['usage']) && \is_array($decoded['usage']) ? $decoded['usage'] : [],
            'context_usage' => $this->estimateContextUsage($model, $messages, $maxTokens),
        ];
    }
}
