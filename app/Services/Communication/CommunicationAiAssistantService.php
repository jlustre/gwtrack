<?php

namespace App\Services\Communication;

use App\Models\AnnouncementTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CommunicationAiAssistantService
{
    public function isEnabled(?string $feature = null): bool
    {
        if (! config('communication-hub.ai.enabled', false)) {
            return false;
        }

        if ($feature === null) {
            return true;
        }

        return (bool) config("communication-hub.ai.features.{$feature}", false);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{title: string, summary: string|null, body: string, source: string}
     */
    public function generateDraft(string $type, array $context = [], ?User $author = null): array
    {
        $featureMap = [
            'announcement' => 'announcement_draft',
            'recognition' => 'recognition_draft',
            'event_summary' => 'event_summary',
            'newsletter_intro' => 'newsletter_intro',
            'leadership_message' => 'leadership_message',
            'campaign_update' => 'campaign_update',
        ];

        $feature = $featureMap[$type] ?? 'announcement_draft';

        if ($this->isEnabled($feature) && config('communication-hub.ai.use_llm', false)) {
            $llm = $this->generateViaLlm($type, $context, $author);

            if ($llm !== null) {
                return $llm;
            }
        }

        return $this->generateFromTemplate($type, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{title: string, summary: string|null, body: string, source: string}
     */
    private function generateFromTemplate(string $type, array $context): array
    {
        $templateCode = $context['template_code'] ?? $type;
        $dbTemplate = AnnouncementTemplate::query()
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        if ($dbTemplate) {
            $replacements = $this->buildReplacements($context);
            $rendered = $dbTemplate->render($replacements);

            return [
                'title' => $rendered['title'],
                'summary' => $rendered['summary'],
                'body' => $rendered['body'],
                'source' => 'template',
            ];
        }

        $configTemplate = config("communication-hub.ai.draft_templates.{$type}");

        if (! is_array($configTemplate)) {
            return [
                'title' => (string) ($context['title'] ?? 'Draft announcement'),
                'summary' => $context['summary'] ?? null,
                'body' => (string) ($context['body'] ?? 'Add your announcement content here.'),
                'source' => 'fallback',
            ];
        }

        $replacements = $this->buildReplacements($context);

        return [
            'title' => strtr((string) ($configTemplate['title'] ?? ''), $replacements),
            'summary' => isset($configTemplate['summary']) ? strtr((string) $configTemplate['summary'], $replacements) : null,
            'body' => strtr((string) ($configTemplate['body'] ?? ''), $replacements),
            'source' => 'template',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function buildReplacements(array $context): array
    {
        return collect($context)
            ->mapWithKeys(fn ($value, $key) => ['{{'.$key.'}}' => (string) $value])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{title: string, summary: string|null, body: string, source: string}|null
     */
    private function generateViaLlm(string $type, array $context, ?User $author): ?array
    {
        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');

        if (! $apiKey) {
            return null;
        }

        $prompt = config("communication-hub.ai.prompts.{$type}", 'Write a professional internal communication draft.');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('communication-hub.ai.model', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You write concise, professional communications for a financial services agency. Return JSON with keys: title, summary, body.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt."\n\nContext: ".json_encode($context),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = json_decode($response->json('choices.0.message.content', '{}'), true);

            if (! is_array($content)) {
                return null;
            }

            return [
                'title' => (string) ($content['title'] ?? 'Draft'),
                'summary' => isset($content['summary']) ? (string) $content['summary'] : null,
                'body' => (string) ($content['body'] ?? ''),
                'source' => 'llm',
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
