<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait PatchReviewTrait
{
    /**
     * @param array{root:string,single_file:?string} $scope
     */
    private function readCurrentTargetContent(array $scope, string $destination): string
    {
        if (!\file_exists($destination)) {
            return '';
        }

        $resolvedDestination = \realpath($destination);
        if ($resolvedDestination === false || !\is_file($resolvedDestination) || !\str_starts_with($resolvedDestination, $scope['root'] . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        $size = \filesize($resolvedDestination);
        if ($size === false || $size > self::MAX_WRITE_BYTES) {
            throw new ValidationException('Patch review target is too large.');
        }

        $content = \file_get_contents($resolvedDestination);
        if ($content === false) {
            throw new StorageException('Patch review target read failed.');
        }

        return \str_replace("\r\n", "\n", \str_replace("\0", '', $content));
    }

    /**
     * @return list<array{type:string,left:int|string,right:int|string,left_text:string,right_text:string}>
     */
    private function buildSideBySideDiff(string $currentCode, string $proposedCode): array
    {
        $left = \explode("\n", \str_replace("\r\n", "\n", $currentCode));
        $right = \explode("\n", \str_replace("\r\n", "\n", $proposedCode));
        if ($currentCode === '') {
            $left = [];
        }
        if ($proposedCode === '') {
            $right = [];
        }

        $prefix = 0;
        $leftCount = \count($left);
        $rightCount = \count($right);
        while ($prefix < $leftCount && $prefix < $rightCount && \hash_equals($left[$prefix], $right[$prefix])) {
            $prefix++;
        }

        $suffix = 0;
        while (
            $suffix + $prefix < $leftCount
            && $suffix + $prefix < $rightCount
            && \hash_equals($left[$leftCount - 1 - $suffix], $right[$rightCount - 1 - $suffix])
        ) {
            $suffix++;
        }

        $diff = [];
        for ($i = 0; $i < $prefix; $i++) {
            $diff[] = $this->buildDiffRow('same', $i + 1, $i + 1, $left[$i], $right[$i]);
        }

        $leftMiddleEnd = $leftCount - $suffix;
        $rightMiddleEnd = $rightCount - $suffix;
        $middleLength = \max($leftMiddleEnd - $prefix, $rightMiddleEnd - $prefix);
        for ($i = 0; $i < $middleLength; $i++) {
            $leftIndex = $prefix + $i;
            $rightIndex = $prefix + $i;
            $hasLeft = $leftIndex < $leftMiddleEnd;
            $hasRight = $rightIndex < $rightMiddleEnd;
            $type = $hasLeft && $hasRight ? 'changed' : ($hasLeft ? 'removed' : 'added');
            $diff[] = $this->buildDiffRow(
                $type,
                $hasLeft ? $leftIndex + 1 : '',
                $hasRight ? $rightIndex + 1 : '',
                $hasLeft ? $left[$leftIndex] : '',
                $hasRight ? $right[$rightIndex] : ''
            );
        }

        for ($i = $suffix; $i > 0; $i--) {
            $leftIndex = $leftCount - $i;
            $rightIndex = $rightCount - $i;
            $diff[] = $this->buildDiffRow('same', $leftIndex + 1, $rightIndex + 1, $left[$leftIndex], $right[$rightIndex]);
        }

        return $diff;
    }

    /**
     * @return array{type:string,left:int|string,right:int|string,left_text:string,right_text:string}
     */
    private function buildDiffRow(string $type, $leftLine, $rightLine, string $leftText, string $rightText): array
    {
        return [
            'type' => $type,
            'left' => $leftLine,
            'right' => $rightLine,
            'left_text' => $leftText,
            'right_text' => $rightText,
        ];
    }

    private function hashReviewToken(string $proposalId, string $reviewToken): string
    {
        return \hash_hmac('sha256', $reviewToken, self::REVIEW_TOKEN_CONTEXT . ':' . $proposalId);
    }

    /**
     * @param array<string,mixed> $proposal
     */
    private function assertReviewToken(string $proposalId, string $reviewToken, array $proposal): void
    {
        if (\preg_match('/\A[a-f0-9]{64}\z/i', $reviewToken) !== 1) {
            throw new ValidationException('Diff review confirmation required before commit.');
        }

        $expectedHash = isset($proposal['review_token_hash']) ? (string) $proposal['review_token_hash'] : '';
        if ($expectedHash === '' || !\hash_equals($expectedHash, $this->hashReviewToken($proposalId, $reviewToken))) {
            throw new ValidationException('Diff review confirmation required before commit.');
        }
    }

    private function assertCommitGuard(string $pluginSlug, string $proposalId): void
    {
        $allowed = (bool) \apply_filters('vgta_throne_guard_commit_allowed', \current_user_can('manage_options'), $pluginSlug, $proposalId);
        if (!$allowed) {
            throw new ValidationException('Master mode is required before commit.');
        }
    }
}
