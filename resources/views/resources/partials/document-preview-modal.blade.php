<template x-teleport="body">
    <div
        x-show="showPreview"
        x-cloak
        x-effect="document.documentElement.classList.toggle('overflow-hidden', showPreview)"
        class="fixed inset-0 z-[200] h-[100dvh] w-screen overflow-hidden bg-gw-primary"
        @keydown.escape.window="closePreview()"
        role="dialog"
        aria-modal="true"
        aria-label="Document preview"
    >
        <div class="relative h-full w-full">
            <div class="pointer-events-none absolute inset-x-0 top-0 z-20 flex items-start justify-end gap-2 p-3 sm:p-4">
                <div
                    x-show="previewData?.has_rtf && previewData?.has_pdf"
                    x-cloak
                    class="pointer-events-auto inline-flex rounded-lg border border-white/20 bg-gw-primary/90 p-1 shadow-lg backdrop-blur-sm"
                >
                    <button
                        type="button"
                        x-show="previewData?.has_rtf"
                        @click="previewView = 'rtf'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition sm:px-4 sm:py-2"
                        :class="previewView === 'rtf' ? 'bg-gw-accent text-gw-primary' : 'text-slate-200 hover:bg-white/10'"
                    >
                        Rich Text
                    </button>
                    <button
                        type="button"
                        x-show="previewData?.has_pdf"
                        @click="previewView = 'pdf'"
                        class="rounded-md px-3 py-1 text-xs font-semibold transition sm:px-4 sm:py-2"
                        :class="previewView === 'pdf' ? 'bg-gw-accent text-gw-primary' : 'text-slate-200 hover:bg-white/10'"
                    >
                        PDF
                    </button>
                </div>

                <a
                    x-show="previewData?.download_url"
                    :href="previewData?.download_url"
                    class="pointer-events-auto inline-flex rounded-md border border-white/20 bg-gw-primary/90 px-3 py-1.5 text-xs font-semibold text-white shadow-lg backdrop-blur-sm transition hover:bg-white/10 sm:px-4 sm:py-2"
                >
                    Download
                </a>

                <button
                    type="button"
                    @click="closePreview()"
                    class="gw-icon-btn-overlay text-xl leading-none sm:h-8 sm:w-8"
                    aria-label="Close preview"
                >&times;</button>
            </div>

            <div class="absolute inset-0">
                <div x-show="previewLoading" class="flex h-full items-center justify-center bg-slate-100">
                    <div class="text-center">
                        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gw-accent border-t-transparent"></div>
                        <p class="mt-4 text-sm font-medium text-slate-600">Loading document…</p>
                    </div>
                </div>

                <div x-show="previewError && ! previewLoading" class="flex h-full items-center justify-center bg-slate-100 px-6">
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="previewError"></div>
                </div>

                <template x-if="previewData && ! previewLoading && previewView === 'rtf' && previewData.has_rtf">
                    <div class="h-full overflow-y-auto bg-white pt-14 sm:pt-16">
                        <div
                            class="mx-auto max-w-4xl px-6 py-8 text-sm leading-7 text-slate-800 [&_a]:text-gw-primary [&_a]:underline [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:mt-4 [&_h3]:font-semibold [&_li]:ml-5 [&_ol]:list-decimal [&_p+p]:mt-4 [&_ul]:list-disc"
                            x-html="previewData.rtf_html"
                        ></div>
                    </div>
                </template>

                <template x-if="previewData && ! previewLoading && previewView === 'pdf' && previewData.has_pdf">
                    <iframe
                        :src="previewData.pdf_url"
                        class="h-full w-full border-0 bg-white"
                        title="PDF document preview"
                    ></iframe>
                </template>

                <template x-if="previewData && ! previewLoading && previewData.default_view === 'summary' && ! previewData.has_rtf && ! previewData.has_pdf">
                    <div class="flex h-full items-center justify-center bg-slate-100 px-6 pt-14 sm:pt-16">
                        <div class="max-w-lg rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                            <p class="text-sm leading-6 text-slate-600" x-text="previewData.description || 'This document is available through an external link.'"></p>
                            <a
                                x-show="previewData.external_url"
                                :href="previewData.external_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#13345f]"
                            >
                                Open document
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
