<?php

namespace MyCompany\AiBlogGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use MyCompany\AiBlogGenerator\Helper\Data;
use MyCompany\AiBlogGenerator\Logger\Logger;

class BlogGenerator
{
    public function __construct(
        private readonly OpenRouterClient $openRouterClient,
        private readonly RagCatalogProvider $ragCatalogProvider,
        private readonly ProductSelectionResolver $productSelectionResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly Data $helper,
        private readonly Json $json,
        private readonly Logger $logger
    ) {
    }

    public function generate(array $payload): array
    {
        $storeId = (int) ($payload['store_id'] ?? 0);
        $store = $this->storeManager->getStore($storeId);
        $locale = (string) $store->getConfig('general/locale/code');
        $language = $this->detectLanguageFromLocale($locale);
        $wordCount = (int) ($payload['word_count'] ?? $this->helper->getDefaultWordCount($storeId));
        $resolvedModel = $this->helper->getDefaultModel($storeId);
        $productIds = $this->productSelectionResolver->resolveProductIds($payload, $storeId);
        $context = $productIds
            ? $this->ragCatalogProvider->getProductsContext($productIds, $storeId)
            : $this->ragCatalogProvider->getContext(
                !empty($payload['product_id']) ? (int) $payload['product_id'] : null,
                !empty($payload['category_id']) ? (int) $payload['category_id'] : null,
                $storeId
            );

        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $this->buildUserPrompt($payload, $language, $locale, $wordCount, $context),
            ],
        ];

        $response = $this->openRouterClient->chat(
            $messages,
            $resolvedModel,
            isset($payload['temperature']) ? (float) $payload['temperature'] : null,
            isset($payload['max_tokens']) ? (int) $payload['max_tokens'] : null,
            $storeId
        );

        $content = $this->extractResponseContent($response);
        if ($content === '') {
            $this->logger->error('OpenRouter returned empty content', [
                'top_level_keys' => array_keys($response),
                'has_choices' => isset($response['choices']) && is_array($response['choices']),
                'first_choice_keys' => isset($response['choices'][0]) && is_array($response['choices'][0]) ? array_keys($response['choices'][0]) : [],
                'message_keys' => isset($response['choices'][0]['message']) && is_array($response['choices'][0]['message']) ? array_keys($response['choices'][0]['message']) : [],
            ]);
            throw new LocalizedException(__('The AI response was empty.'));
        }

        $decoded = is_array($content) ? $content : $this->decodeJsonContent($content);
        return $this->normalizeOutput($decoded, $payload, $language, $wordCount, $resolvedModel);
    }

    private function buildSystemPrompt(): string
    {
        return 'You are a Senior SEO content strategist. Create long-form SEO blog content for Magento ecommerce websites. Use structured headings, semantic HTML, keyword variations, FAQ, internal linking suggestions, and conversion-aware copywriting. Return only valid JSON with keys: title, meta_title, meta_description, url_key, short_description, content_html, faq, tags, schema_markup.';
    }

    private function buildUserPrompt(array $payload, string $language, string $locale, int $wordCount, array $context): string
    {
        $instructions = [
            'Generate a blog post for Mageplaza Blog.',
            'Language: ' . $language,
            'Locale: ' . $locale,
            'Topic: ' . (string) ($payload['topic'] ?? ''),
            'Keywords: ' . (string) ($payload['keywords'] ?? ''),
            'Tone: ' . (string) ($payload['tone'] ?? 'professional'),
            'Word count target: ' . $wordCount,
            'Output must be SEO optimized and structured as JSON.',
            'content_html must include h1, h2, h3, paragraphs, lists, and an FAQ section.',
            'schema_markup must include BlogPosting and FAQPage JSON-LD.',
            'Include tags as an array of short SEO tags.',
            'faq must be an array of question/answer pairs.',
            'If context is provided, use it accurately and do not invent product or category facts.',
            'If CONTEXT contains multiple products, compare and reference only those products.',
            'Use only exact product or category URLs provided in CONTEXT when inserting links.',
            'Never invent, rewrite, guess, translate, or shorten URLs.',
            'If no exact URL is present in CONTEXT, do not add a direct product or category link.',
            'Also include internal linking suggestions naturally in content_html.',
            'CONTEXT: ' . $this->json->serialize($context),
        ];

        return implode("\n", $instructions);
    }

    private function normalizeOutput(array $decoded, array $payload, string $language, int $wordCount, string $resolvedModel): array
    {
        $schemaMarkup = $decoded['schema_markup'] ?? '';
        if (is_array($schemaMarkup)) {
            $schemaMarkup = $this->json->serialize($schemaMarkup);
        } else {
            $schemaMarkup = (string) $schemaMarkup;
        }

        return [
            'title' => (string) ($decoded['title'] ?? $payload['topic'] ?? ''),
            'meta_title' => (string) ($decoded['meta_title'] ?? $decoded['title'] ?? $payload['topic'] ?? ''),
            'meta_description' => (string) ($decoded['meta_description'] ?? ''),
            'url_key' => (string) ($decoded['url_key'] ?? ''),
            'short_description' => (string) ($decoded['short_description'] ?? ''),
            'content_html' => (string) ($decoded['content_html'] ?? ''),
            'faq' => is_array($decoded['faq'] ?? null) ? $decoded['faq'] : [],
            'tags' => is_array($decoded['tags'] ?? null) ? $decoded['tags'] : [],
            'schema_markup' => $schemaMarkup,
            'language' => $language,
            'word_count' => $wordCount,
            'model' => $resolvedModel,
            'raw' => $decoded,
        ];
    }

    private function detectLanguageFromLocale(string $locale): string
    {
        return match (strtolower(substr($locale, 0, 2))) {
            'uk' => 'Ukrainian',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
            'pl' => 'Polish',
            'it' => 'Italian',
            default => 'English',
        };
    }

    private function extractResponseContent(array $response): string|array
    {
        $choice = $response['choices'][0] ?? [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
        $content = $message['content'] ?? ($choice['text'] ?? ($response['output_text'] ?? ''));

        if (is_string($content)) {
            return trim($content);
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_string($part)) {
                    $parts[] = $part;
                    continue;
                }

                if (!is_array($part)) {
                    continue;
                }

                if (isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                    continue;
                }

                if (isset($part['content']) && is_string($part['content'])) {
                    $parts[] = $part['content'];
                }
            }

            $joined = trim(implode('', $parts));
            if ($joined !== '') {
                return $joined;
            }

            return $content;
        }

        return '';
    }

    private function decodeJsonContent(string $content): array
    {
        $normalized = trim($content);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $normalized, $matches)) {
            $normalized = trim((string) ($matches[1] ?? $normalized));
        }

        $extractedJson = $this->extractJsonObject($normalized);
        if ($extractedJson !== null) {
            $normalized = $extractedJson;
        }

        try {
            $decoded = $this->json->unserialize($normalized);
            return is_array($decoded) ? $decoded : [];
        } catch (\InvalidArgumentException $exception) {
            $sanitized = $this->sanitizeJsonContent($normalized);

            $sanitizedExtractedJson = $this->extractJsonObject($sanitized);
            if ($sanitizedExtractedJson !== null) {
                $sanitized = $sanitizedExtractedJson;
            }

            if ($sanitized !== $normalized) {
                try {
                    $decoded = $this->json->unserialize($sanitized);
                    if (is_array($decoded)) {
                        $this->logger->warning('AI JSON response required control-character sanitization before decode', [
                            'original_preview' => substr($normalized, 0, 500),
                            'sanitized_preview' => substr($sanitized, 0, 500),
                            'message' => $exception->getMessage(),
                        ]);

                        return $decoded;
                    }
                } catch (\InvalidArgumentException $sanitizedException) {
                    $this->logger->error('Unable to decode sanitized AI JSON response', [
                        'preview' => substr($sanitized, 0, 500),
                        'message' => $sanitizedException->getMessage(),
                    ]);
                }
            }

            if ($this->looksLikeTruncatedJson($sanitized) || $this->looksLikeTruncatedJson($normalized)) {
                $this->logger->error('AI JSON response appears truncated', [
                    'preview' => substr($normalized, 0, 500),
                    'sanitized_preview' => substr($sanitized, 0, 500),
                    'message' => $exception->getMessage(),
                ]);

                throw new LocalizedException(__('The AI response appears truncated. Increase Max Tokens or use a model that returns strict JSON reliably.'));
            }

            $this->logger->error('Unable to decode AI JSON response', [
                'preview' => substr($normalized, 0, 500),
                'message' => $exception->getMessage(),
            ]);
            throw new LocalizedException(__('The AI response was not valid JSON.'));
        }
    }

    private function sanitizeJsonContent(string $content): string
    {
        return (string) preg_replace_callback(
            '/"((?:\\\\.|[^"\\\\])*)"/s',
            static function (array $matches): string {
                $value = $matches[1] ?? '';
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
                $value = str_replace(
                    ["\r\n", "\r", "\n", "\t"],
                    ['\\n', '\\n', '\\n', '\\t'],
                    $value
                );

                return '"' . $value . '"';
            },
            $content
        );
    }

    private function extractJsonObject(string $content): ?string
    {
        $start = strpos($content, '{');
        if ($start === false) {
            return null;
        }

        $length = strlen($content);
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($index = $start; $index < $length; $index++) {
            $char = $content[$index];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $index - $start + 1);
                }
            }
        }

        return trim(substr($content, $start));
    }

    private function looksLikeTruncatedJson(string $content): bool
    {
        $trimmed = trim($content);
        if ($trimmed === '' || strpos($trimmed, '{') === false) {
            return false;
        }

        $length = strlen($trimmed);
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $trimmed[$index];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;
            }
        }

        return $depth > 0 || $inString;
    }
}
