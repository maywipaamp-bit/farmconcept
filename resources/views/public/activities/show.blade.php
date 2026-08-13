@extends('public.activities.layout')

@section('title', $activity['title'])

@section('content')
    <article class="detail-card">
        <div class="detail-hero{{ $activity['image'] ? '' : ' has-fallback' }}" @if($activity['image']) style="background-image:url('{{ $activity['image'] }}')" @endif>
            <a class="detail-back-overlay" href="{{ route('public.activities') }}" aria-label="กลับไปหน้ากิจกรรม">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m15 18-6-6 6-6"/></svg>
                <span>กลับ</span>
            </a>
            <span class="activity-badge detail-category-badge">{{ strtoupper($activity['category'] ?: $activity['type']) }}</span>
        </div>
        <div class="detail-body">
            <h1>{{ $activity['title'] }}</h1>

            @if($activity['description'])
                <p class="detail-description">{{ $activity['description'] }}</p>
            @endif

            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                <span>{{ $activity['scheduleLabel'] ?: '-' }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $activity['location'] }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg>
                <span>{{ $activity['speaker'] }}</span>
            </div>
            <div class="detail-meta detail-price">
                <span class="baht-icon">฿</span>
                <span>{{ $activity['priceLabel'] }}</span>
            </div>

            @if($activity['registrationDeadlineLabel'])
                <div class="deadline-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span>{{ $activity['registrationDeadlineLabel'] }}</span>
                </div>
            @endif

            @if(!$checkin['requested'] && !$postSurvey['requested'] && $registration['enabled'])
                <a class="registration-cta" href="#registration-form">ลงทะเบียนเข้าร่วม</a>
            @endif
        </div>
    </article>

    @if($postSurvey['requested'] && $postSurvey['enabled'])
        <section class="registration-card survey-card" id="post-survey-form">
            <div class="registration-heading">
                <span class="registration-step">✓</span>
                <div>
                    <h2>{{ $postSurvey['form']->name }}</h2>
                    <p>{{ $postSurvey['form']->description ?: 'ความคิดเห็นของคุณจะช่วยให้เราพัฒนากิจกรรมครั้งต่อไป' }}</p>
                </div>
            </div>

            <form id="public-post-survey-form" novalidate>
                @csrf
                @foreach($postSurvey['form']->questions as $question)
                    @if($question->question_type === 'section')
                        <h3 class="survey-section-title">{{ $question->text }}</h3>
                    @else
                        <fieldset class="survey-question" data-question-id="{{ $question->id }}" data-question-type="{{ $question->question_type }}">
                            <legend>{{ $question->text }} @if($question->is_required)<span>*</span>@endif</legend>

                            @if($question->question_type === 'rating')
                                <div class="survey-rating">
                                    @foreach([1 => 'น้อยที่สุด', 2 => 'น้อย', 3 => 'ปานกลาง', 4 => 'มาก', 5 => 'มากที่สุด'] as $score => $label)
                                        <label>
                                            <input type="radio" name="answer_{{ $question->id }}" value="{{ $score }}" @required($question->is_required)>
                                            <span>{{ $score }}</span>
                                            <small>{{ $label }}</small>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($question->question_type === 'text')
                                <textarea name="answer_{{ $question->id }}" rows="4" maxlength="5000" placeholder="พิมพ์คำตอบ…" @required($question->is_required)></textarea>
                            @elseif($question->question_type === 'dropdown')
                                <select name="answer_{{ $question->id }}" @required($question->is_required)>
                                    <option value="">เลือกคำตอบ</option>
                                    @foreach($question->options as $option)
                                        <option value="{{ $option->id }}">{{ $option->label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <div class="survey-options{{ $question->question_type === 'chips' ? ' is-chips' : '' }}">
                                    @foreach($question->options as $option)
                                        <label>
                                            <input
                                                type="{{ in_array($question->question_type, ['multi', 'chips'], true) ? 'checkbox' : 'radio' }}"
                                                name="answer_{{ $question->id }}{{ in_array($question->question_type, ['multi', 'chips'], true) ? '[]' : '' }}"
                                                value="{{ $option->id }}"
                                                @required($question->is_required && !in_array($question->question_type, ['multi', 'chips'], true))
                                            >
                                            <span>{{ $option->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </fieldset>
                    @endif
                @endforeach

                <p class="registration-message" id="post-survey-message" aria-live="polite"></p>
                <button type="submit" class="registration-submit" id="post-survey-submit">ส่งแบบประเมิน</button>

                <div class="registration-success" id="post-survey-success" hidden role="status">
                    <span class="registration-success-icon">✓</span>
                    <h2>ส่งแบบประเมินแล้ว</h2>
                    <p>ขอบคุณสำหรับความคิดเห็นของคุณ</p>
                </div>
            </form>
        </section>
    @elseif($postSurvey['requested'])
        <section class="registration-card is-closed">
            <h2>ยังไม่เปิดแบบประเมิน</h2>
            <p>กิจกรรมนี้ยังไม่อยู่ในช่วงรับคำตอบ หรือแบบประเมินปิดรับคำตอบแล้ว</p>
        </section>
    @elseif($checkin['requested'] && $checkin['enabled'])
        <section class="registration-card checkin-card" id="checkin-form">
            <div class="registration-heading">
                <span class="registration-step">1</span>
                <div>
                    <h2>ยืนยันเบอร์โทรศัพท์เพื่อ Check-in</h2>
                    <p>ใช้เบอร์เดียวกับที่ลงทะเบียน แล้วเลือกรายชื่อผู้เข้าร่วม</p>
                </div>
            </div>

            <form id="public-checkin-form" novalidate>
                @csrf
                <div class="registration-field">
                    <label for="checkin-phone">เบอร์โทรศัพท์ <span>*</span></label>
                    <div class="registration-phone-row">
                        <input id="checkin-phone" name="phone" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="08X-XXX-XXXX" required>
                        <button type="button" id="checkin-lookup">ค้นหารายชื่อ</button>
                    </div>
                    <p class="registration-message" id="checkin-message" aria-live="polite"></p>
                </div>

                <div class="checkin-results" id="checkin-results" hidden>
                    <div class="registration-heading is-sub">
                        <span class="registration-step">2</span>
                        <div>
                            <h2>เลือกรายชื่อเพื่อ Check-in</h2>
                            <p>กรณีจองหลายที่นั่ง ระบบจะแสดงรายชื่อทั้งหมดของเบอร์นี้</p>
                        </div>
                    </div>
                    <div class="checkin-name-list" id="checkin-name-list"></div>
                </div>
            </form>
        </section>
    @elseif($checkin['requested'])
        <section class="registration-card is-closed">
            <h2>ยังไม่เปิด Check-in</h2>
            <p>กิจกรรมนี้ยังไม่อยู่ในช่วง Check-in หรือสิ้นสุดช่วง Check-in แล้ว</p>
        </section>
    @elseif($registration['enabled'])
        <section class="registration-card" id="registration-form" data-open="{{ $registration['open'] ? 'true' : 'false' }}">
            <div class="registration-heading">
                <span class="registration-step">1</span>
                <div>
                    <h2>ตรวจสอบเบอร์โทรศัพท์</h2>
                    <p>ใช้ตรวจสอบว่าคุณยังไม่ได้ลงทะเบียนกิจกรรมนี้</p>
                </div>
            </div>

            <form id="public-registration-form" novalidate>
                @csrf
                <div class="registration-field">
                    <label for="registration-phone">เบอร์โทรศัพท์ <span>*</span></label>
                    <div class="registration-phone-row">
                        <input id="registration-phone" name="phone" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="08X-XXX-XXXX" required>
                        <button type="button" id="registration-check-phone">ตรวจสอบ</button>
                    </div>
                    <p class="registration-message" id="registration-phone-message" aria-live="polite"></p>
                </div>

                <div class="registration-details" id="registration-details" hidden>
                    <div class="registration-heading is-sub">
                        <span class="registration-step">2</span>
                        <div>
                            <h2>ข้อมูลผู้เข้าร่วม</h2>
                            <p>กรอกชื่อให้ครบตามจำนวนที่นั่งที่ต้องการจอง</p>
                        </div>
                    </div>

                    @if($registration['rounds']->count() > 1)
                        <div class="registration-field">
                            <label for="registration-round">รอบที่ต้องการสมัคร <span>*</span></label>
                            <select id="registration-round" name="activity_round_id" required>
                                <option value="">เลือกรอบกิจกรรม</option>
                                @foreach($registration['rounds'] as $round)
                                    <option value="{{ $round['id'] }}" @disabled($round['seatsLeft'] === 0)>
                                        {{ $round['label'] }}{{ $round['seatsLeft'] !== null ? ' · เหลือ '.$round['seatsLeft'].' ที่นั่ง' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($registration['rounds']->count() === 1)
                        <input type="hidden" name="activity_round_id" value="{{ $registration['rounds']->first()['id'] }}">
                    @endif

                    @if($registration['maxSeats'] > 1)
                        <div class="registration-field">
                            <label for="registration-seat-count">จำนวนที่นั่ง <span>*</span></label>
                            <select id="registration-seat-count" name="seat_count">
                                @for($seat = 1; $seat <= $registration['maxSeats']; $seat++)
                                    <option value="{{ $seat }}">{{ $seat }} ที่นั่ง</option>
                                @endfor
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="registration-seat-count" name="seat_count" value="1">
                    @endif

                    <div id="registration-names"></div>

                    <label class="registration-consent">
                        <input type="checkbox" name="pdpa" value="1" required>
                        <span>ยอมรับเงื่อนไขการเข้าร่วมกิจกรรมและนโยบาย PDPA <b>*</b></span>
                    </label>

                    <p class="registration-message" id="registration-form-message" aria-live="polite"></p>
                    <button type="submit" class="registration-submit" id="registration-submit">ยืนยันการลงทะเบียน</button>
                </div>

                <div class="registration-success" id="registration-success" hidden role="status">
                    <span class="registration-success-icon">✓</span>
                    <h2>ลงทะเบียนสำเร็จ</h2>
                    <p id="registration-success-message"></p>
                </div>
            </form>
        </section>
    @elseif($activity['requiresRegistration'])
        <section class="registration-card is-closed">
            <h2>ยังไม่เปิดรับลงทะเบียน</h2>
            <p>กิจกรรมนี้ยังไม่อยู่ในช่วงรับสมัคร หรือจำนวนที่นั่งเต็มแล้ว</p>
        </section>
    @endif
@endsection

@push('page-script')
    @if($postSurvey['requested'] && $postSurvey['enabled'])
        <script>
            window.TFC_PUBLIC_POST_SURVEY = @json([
                'storeUrl' => $postSurvey['storeUrl'],
            ]);
        </script>
        <script src="@assetv('assets/js/public-post-survey.js')" defer></script>
    @elseif($checkin['requested'] && $checkin['enabled'])
        <script>
            window.TFC_PUBLIC_CHECKIN = @json($checkin);
        </script>
        <script src="@assetv('assets/js/public-checkin.js')" defer></script>
    @elseif($registration['enabled'])
        <script>
            window.TFC_PUBLIC_REGISTRATION = @json($registration);
        </script>
        <script src="@assetv('assets/js/public-registration.js')" defer></script>
    @endif
@endpush
