<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait ClaudeGatewayTrait
{
    private function requestClaudeCompletion(string $apiKey, string $model, array $messages, string $reasoningEffort, int $maxTokens): array
    {
        $parts = \explode('/', $model);
        $realModel = $parts[1] ?? $model;

        $claudeMessages = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $claudeMessages[] = [
                'role' => $role,
                'content' => $msg['content']
            ];
        }

        $thinkingBudget = 0;
        if ($reasoningEffort === 'low') {
            $thinkingBudget = 2048;
        } elseif ($reasoningEffort === 'medium') {
            $thinkingBudget = 4096;
        } elseif ($reasoningEffort === 'high') {
            $thinkingBudget = 8192;
        }

        $bodyPayload = [
            'model' => $realModel,
            'messages' => $claudeMessages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.6,
        ];

        if ($thinkingBudget > 0) {
            $bodyPayload['temperature'] = 1.0;
            $bodyPayload['thinking'] = [
                'type' => 'enabled',
                'budget_tokens' => $thinkingBudget
            ];
            if ($maxTokens <= $thinkingBudget) {
                $bodyPayload['max_tokens'] = $thinkingBudget + 2048;
            }
        }

        $encodedBody = \json_encode($bodyPayload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        $response = \wp_remote_post('https://api.anthropic.com/v1/messages', [
            'method' => 'POST',
            'timeout' => 120,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'sslverify' => true,
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
                'accept' => 'application/json',
            ],
            'body' => $encodedBody,
            'data_format' => 'body',
        ]);

        if (\is_wp_error($response)) {
            $this->throwTypedException('Claude gateway connection failed.', 'storage');
        }

        $statusCode = (int) \wp_remote_retrieve_response_code($response);
        $responseBody = (string) \wp_remote_retrieve_body($response);
        if ($statusCode !== 200) {
            \error_log('[VGTA Claude Error Body] Code ' . $statusCode . ': ' . $responseBody);
            $message = $this->extractGatewayErrorMessage($responseBody);
            throw new ValidationException('Claude gateway HTTP ' . $statusCode . ': ' . $message);
        }

        $decoded = \json_decode($responseBody, true, 64, \JSON_THROW_ON_ERROR);
        
        $content = '';
        $reasoning = '';
        if (isset($decoded['content']) && \is_array($decoded['content'])) {
            foreach ($decoded['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'thinking' && isset($block['thinking'])) {
                    $reasoning .= (string) $block['thinking'];
                } elseif (isset($block['type']) && $block['type'] === 'text' && isset($block['text'])) {
                    $content .= (string) $block['text'];
                }
            }
        }
        $content = \trim($content);
        $reasoning = \trim($reasoning);

        $usage = [];
        if (isset($decoded['usage'])) {
            $meta = $decoded['usage'];
            $usage = [
                'prompt_tokens' => $meta['input_tokens'] ?? 0,
                'completion_tokens' => $meta['output_tokens'] ?? 0,
                'total_tokens' => ($meta['input_tokens'] ?? 0) + ($meta['output_tokens'] ?? 0),
            ];
        }

        return [
            'content' => $content,
            'reasoning' => $reasoning,
            'stage_content' => $content,
            'usage' => $usage,
            'context_usage' => $this->estimateContextUsage($model, $messages, $maxTokens),
        ];
    }
}
