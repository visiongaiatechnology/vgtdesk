<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait GeminiGatewayTrait
{
    private function requestGeminiCompletion(string $apiKey, string $model, array $messages, string $reasoningEffort, int $maxTokens): array
    {
        $parts = \explode('/', $model);
        $realModel = $parts[1] ?? $model;

        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content']]
                ]
            ];
        }

        $thinkingBudget = 0;
        if ($reasoningEffort === 'low') {
            $thinkingBudget = 2048;
        } elseif ($reasoningEffort === 'medium') {
            $thinkingBudget = 4096;
        } elseif ($reasoningEffort === 'high') {
            $thinkingBudget = 16384;
        }

        $generationConfig = [
            'maxOutputTokens' => $maxTokens,
            'temperature' => 0.6,
        ];

        if ($thinkingBudget > 0) {
            $generationConfig['thinkingConfig'] = [
                'thinkingBudget' => $thinkingBudget
            ];
        }

        $bodyPayload = [
            'contents' => $contents,
            'generationConfig' => $generationConfig
        ];

        $encodedBody = \json_encode($bodyPayload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $realModel . ':generateContent?key=' . $apiKey;
        $response = \wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 120,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => $encodedBody,
            'data_format' => 'body',
        ]);

        if (\is_wp_error($response)) {
            $this->throwTypedException('Gemini gateway connection failed.', 'storage');
        }

        $statusCode = (int) \wp_remote_retrieve_response_code($response);
        $responseBody = (string) \wp_remote_retrieve_body($response);
        if ($statusCode !== 200) {
            \error_log('[VGTA Gemini Error Body] Code ' . $statusCode . ': ' . $responseBody);
            $message = $this->extractGatewayErrorMessage($responseBody);
            throw new ValidationException('Gemini gateway HTTP ' . $statusCode . ': ' . $message);
        }

        $decoded = \json_decode($responseBody, true, 64, \JSON_THROW_ON_ERROR);
        
        $content = '';
        $reasoning = '';
        if (isset($decoded['candidates'][0]['content']['parts'])) {
            $candidateParts = $decoded['candidates'][0]['content']['parts'];
            foreach ($candidateParts as $part) {
                if (isset($part['thought']) && $part['thought'] === true) {
                    $reasoning .= (string) $part['text'];
                } else {
                    $content .= (string) $part['text'];
                }
            }
        }
        $content = \trim($content);
        $reasoning = \trim($reasoning);

        $usage = [];
        if (isset($decoded['usageMetadata'])) {
            $meta = $decoded['usageMetadata'];
            $usage = [
                'prompt_tokens' => $meta['promptTokenCount'] ?? 0,
                'completion_tokens' => $meta['candidatesTokenCount'] ?? 0,
                'total_tokens' => $meta['totalTokenCount'] ?? 0,
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
