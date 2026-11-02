<?php

namespace App\Services;

use App\Models\DailyQuote;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyQuoteService
{
    public const SESSION_KEY = 'dashboard_daily_quote_index';

    /**
     * Select a random active quote for this dashboard visit, plus the full
     * active list for prev/next browsing. Avoids repeating the previous quote
     * when more than one quote is available.
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

        $count = $quotes->count();
        $index = random_int(0, $count - 1);
        $previous = session(self::SESSION_KEY);

        if ($count > 1 && $previous !== null && (int) $previous === $index) {
            $index = ($index + random_int(1, $count - 1)) % $count;
        }

        session([self::SESSION_KEY => $index]);
        $selected = $quotes->get($index);

        return [
            'quote' => $selected['quote'],
            'author' => $selected['author'],
            'index' => $index,
            'total' => $count,
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
