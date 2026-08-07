{{--
    Student ID card with a live verification QR, plus issued certificates.

    The QR encodes the same /verify/student/{id}?tenant={id} URL that is printed
    on the physical card, so scanning this screen and scanning the card give the
    same result.
--}}
<x-filament-panels::page>
@include('filament.student-portal.partials.styles')

    @php
        $student = $this->student();
        $qr = $this->qrDataUri;
        $url = $this->verifyUrl;
    @endphp

    @if (! $student)
        <x-filament::section>
            <p class="sp-empty">No student record is linked to this login.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Student identification</x-slot>
            <x-slot name="description">Present this at the front office or when asked on campus.</x-slot>

            <div class="sp-card">
                <div class="sp-card__head">
                    <div style="font-size:.9375rem;font-weight:700;letter-spacing:.01em;">{{ $this->schoolName() }}</div>
                    <div style="font-size:.6875rem;opacity:.8;text-transform:uppercase;letter-spacing:.08em;">Student Identification Card</div>
                </div>

                <div class="sp-card__body">
                    <div class="sp-card__photo">{{ $this->initials() }}</div>
                    <div style="min-width:0;flex:1;">
                        <div class="sp-card__field">
                            <div class="sp-card__key">Name</div>
                            <div class="sp-card__val">{{ trim($student->first_name . ' ' . $student->last_name) }}</div>
                        </div>
                        <div class="sp-card__field">
                            <div class="sp-card__key">Student ID</div>
                            <div class="sp-card__val">{{ $student->admission_number ?: $student->registration_number ?: '—' }}</div>
                        </div>
                        <div class="sp-card__field">
                            <div class="sp-card__key">Grade / Section</div>
                            <div class="sp-card__val">
                                {{ $student->schoolClass?->name ?? '—' }}
                                @if ($student->section) · {{ $student->section->name }} @endif
                            </div>
                        </div>
                        @if ($student->blood_group)
                            <div class="sp-card__field">
                                <div class="sp-card__key">Blood group</div>
                                <div class="sp-card__val">{{ \App\Support\EnumLabel::raw($student->blood_group) }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="sp-card__foot">
                    @if ($qr)
                        <div class="sp-card__qr">
                            <img src="{{ $qr }}" alt="Verification QR code for this student ID">
                        </div>
                    @endif
                    <div style="min-width:0;">
                        <div style="font-size:.6875rem;text-transform:uppercase;letter-spacing:.08em;opacity:.75;">Verify this card</div>
                        <div style="font-size:.75rem;opacity:.9;margin-top:.125rem;">
                            Scan the code, or visit the link below. It confirms this ID against the school's records.
                        </div>
                        @if ($url)
                            <div style="font-size:.625rem;opacity:.7;margin-top:.25rem;word-break:break-all;font-family:ui-monospace,monospace;">{{ $url }}</div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($url)
                <div style="margin-top:1rem;">
                    <x-filament::link :href="$url" target="_blank" rel="noopener" icon="heroicon-m-arrow-top-right-on-square">
                        Open the verification page
                    </x-filament::link>
                </div>
            @endif
        </x-filament::section>

        {{-- ── Certificates ────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">My certificates</x-slot>
            <x-slot name="description">Each one carries its own verification code.</x-slot>

            @forelse ($this->certificates as $cert)
                <div class="sp-row" style="align-items:center;">
                    <div style="min-width:0;">
                        <div class="sp-row__title">{{ $cert->template?->name ?? 'Certificate' }}</div>
                        <div class="sp-row__meta">
                            {{ $cert->certificate_number }}
                            @if ($cert->issued_date)
                                · issued {{ \Illuminate\Support\Carbon::parse($cert->issued_date)->format('M j, Y') }}
                            @endif
                        </div>
                    </div>
                    <x-filament::link
                        :href="$cert->verify_url"
                        target="_blank"
                        rel="noopener"
                        size="sm"
                        icon="heroicon-m-shield-check"
                    >
                        Verify
                    </x-filament::link>
                </div>
            @empty
                <p class="sp-empty">No certificates have been issued to you yet.</p>
            @endforelse
        </x-filament::section>
    @endif
</x-filament-panels::page>
