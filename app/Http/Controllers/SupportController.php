<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DocumentationPdfService;
use App\Support\DocumentationMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupportController extends Controller
{
    public function __construct(
        private readonly DocumentationPdfService $documentationPdf,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing([
            'profile',
            'rank',
            'team',
            'sponsor',
        ]);

        return view('support.index', [
            'user' => $user,
        ]);
    }

    public function documentation(string $guide): View
    {
        $entry = collect(config('support-documentation.modules', []))
            ->first(fn (array $module): bool => ($module['slug'] ?? null) === $guide);

        if (! $entry || empty($entry['file'])) {
            throw new NotFoundHttpException();
        }

        $path = base_path('docs/'.$entry['file']);

        if (! File::isFile($path)) {
            throw new NotFoundHttpException();
        }

        return view('support.documentation', [
            'guide' => $guide,
            'title' => $entry['title'] ?? $entry['module'],
            'summary' => $entry['summary'] ?? '',
            'content' => DocumentationMarkdown::toHtml(File::get($path)),
            'hasPdf' => true,
        ]);
    }

    public function documentationPdf(string $guide): Response
    {
        $pdf = $this->documentationPdf->pdf($guide);
        $filename = str($guide)->slug().'.pdf';

        return $pdf->download($filename);
    }

    public function documentationPdfInline(string $guide): Response
    {
        $pdf = $this->documentationPdf->pdf($guide);
        $filename = str($guide)->slug().'.pdf';

        return $pdf->stream($filename);
    }
}
