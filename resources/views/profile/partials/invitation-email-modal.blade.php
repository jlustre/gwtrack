@php
    $useLivewire = $useLivewire ?? false;
@endphp

<template x-teleport="body">
    <div
        x-show="{{ $modalState }}"
        x-cloak
        x-effect="if ({{ $modalState }}) { $nextTick(() => window.gwInitRichText?.('{{ $inputPrefix }}_message')) }"
        class="fixed inset-0 z-[210] flex items-center justify-center bg-slate-950/60 p-4"
    >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl" x-on:click.outside="{{ $modalState }} = false">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Preview Email</p>
                    <h3 class="mt-1 text-xl font-semibold text-gw-primary">Mail Invitation Link</h3>
                </div>
                <button type="button" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-100" x-on:click="{{ $modalState }} = false">Close</button>
            </div>

            <form
                @if ($useLivewire)
                    x-on:submit.prevent="
                        window.tinymce?.triggerSave?.();
                        $wire.sendInvitationEmail({{ $invitation->id }}, {
                            recipient_email: $refs.recipientEmail.value,
                            subject: $refs.subject.value,
                            message: document.getElementById('{{ $inputPrefix }}_message')?.value ?? '',
                        }).then(() => { {{ $modalState }} = false; });
                    "
                @else
                    method="POST"
                    action="{{ route('profile.invitations.send', $invitation) }}"
                @endif
                class="mt-5 space-y-4"
            >
                @unless ($useLivewire)
                    @csrf
                @endunless

                <div>
                    <x-input-label for="{{ $inputPrefix }}_recipient_email" :value="__('To')" />
                    <x-text-input
                        id="{{ $inputPrefix }}_recipient_email"
                        name="recipient_email"
                        type="email"
                        class="mt-1 block w-full"
                        :value="old('recipient_email', $invitation->email)"
                        required
                        placeholder="new.member@example.com"
                        x-ref="recipientEmail"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('recipient_email')" />
                </div>

                <div>
                    <x-input-label for="{{ $inputPrefix }}_subject" :value="__('Subject')" />
                    <x-text-input
                        id="{{ $inputPrefix }}_subject"
                        name="subject"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('subject', $emailPreview['subject'])"
                        required
                        x-ref="subject"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                </div>

                <div>
                    <x-input-label for="{{ $inputPrefix }}_message" :value="__('Message')" />
                    <textarea
                        id="{{ $inputPrefix }}_message"
                        name="message"
                        rows="12"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        required
                    >{!! old('message', $emailPreview['body']) !!}</textarea>
                    <p class="mt-2 text-xs text-slate-500">The registration link must remain in the message. Formatting matches what the recipient will see in their inbox.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('message')" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" x-on:click="{{ $modalState }} = false">Cancel</button>
                    <x-primary-button>{{ __('Send Email') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</template>
