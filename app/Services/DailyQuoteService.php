<?php

namespace App\Services;

use App\Models\DailyQuote;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyQuoteService
{
    /**
     * Deterministically select one active quote for the given calendar day,
     * plus the full active list for dashboard prev/next browsing.
     *
     * @return array{
     *     quote: string,
     *     author: string|null,
     *     index: int,
     *     total: int,
     *     quotes: list<array{quote: string, author: string|null}>
     * }|null
     */
    public function forDate(?CarbonInterface $date = null): ?array
    {
        $quotes = $this->activeQuotes();

        if ($quotes->isEmpty()) {
            return null;
        }

        $index = ($date ?? now())->dayOfYear % $quotes->count();
        $selected = $quotes->get($index);

        return [
            'quote' => $selected['quote'],
            'author' => $selected['author'],
            'index' => $index,
            'total' => $quotes->count(),
            'quotes' => $quotes->all(),
        ];
    }

    /**
     * @return Collection<int, array{quote: string, author: string|null}>
     */
    public function activeQuotes(): Collection
    {
        return DailyQuote::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['quote', 'author'])
            ->map(fn (DailyQuote $quote): array => [
                'quote' => $quote->quote,
                'author' => $quote->author,
            ])
            ->values();
    }
}
