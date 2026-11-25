@php
    $watchUrl = $watchUrl ?? '';
    $mode = $mode ?? 'single'; // 'single' | 'feed'
    $enabled = $enabled ?? true;
    $initialEventId = $initialEventId ?? null;
@endphp

@if ($enabled && $watchUrl !== '')
    <div id="paymirRealtimeToast"
         class="position-fixed top-0 end-0 p-3 no-print"
         style="z-index: 1085; display: none; max-width: 390px;">
        <div class="alert alert-success shadow-lg border border-success-subtle rounded-4 mb-0 d-flex align-items-start gap-3 p-3">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                <i class="bi bi-check-circle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-dark mb-1" id="paymirToastTitle">PAYMIR Payment Confirmed!</div>
                <div class="small text-secondary" id="paymirToastBody">
                    Payment received and licence verified. Refreshing page...
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const watchUrl = @json($watchUrl);
            const mode = @json($mode);
            let baselineEventId = @json($initialEventId);
            let triggered = false;
            let timerId = null;

            function showRealtimeNotification(title, body) {
                const toast = document.getElementById('paymirRealtimeToast');
                const titleEl = document.getElementById('paymirToastTitle');
                const bodyEl = document.getElementById('paymirToastBody');

                if (titleEl) titleEl.textContent = title;
                if (bodyEl) bodyEl.textContent = body;
                if (toast) toast.style.display = 'block';

                if ('Notification' in window && Notification.permission === 'granted') {
                    try {
                        new Notification(title, { body: body });
                    } catch (e) {}
                }
            }

            async function checkPaymirStatus() {
                if (triggered || document.hidden) return;

                try {
                    const res = await fetch(watchUrl, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!res.ok) return;

                    const data = await res.json();

                    if (mode === 'single') {
                        if (data && data.paid === true) {
                            triggered = true;
                            if (timerId) clearInterval(timerId);
                            const licNo = data.license_no ? ' (Licence: ' + data.license_no + ')' : '';
                            showRealtimeNotification(
                                'PAYMIR Payment Confirmed!',
                                'Your payment has been received and the licence is verified' + licNo + '. Refreshing...'
                            );
                            setTimeout(() => window.location.reload(), 1200);
                        }
                    } else if (mode === 'feed') {
                        const evt = data ? data.latest_event : null;
                        if (evt && evt.event_id) {
                            if (baselineEventId === null) {
                                baselineEventId = evt.event_id;
                                return;
                            }
                            if (evt.event_id !== baselineEventId) {
                                triggered = true;
                                if (timerId) clearInterval(timerId);
                                const appInfo = evt.application_no ? ' for ' + evt.application_no : '';
                                const licInfo = evt.license_no ? ' — Licence ' + evt.license_no + ' issued.' : '.';
                                showRealtimeNotification(
                                    'PAYMIR Payment Received!',
                                    'Payment confirmed' + appInfo + licInfo + ' Refreshing...'
                                );
                                setTimeout(() => window.location.reload(), 1200);
                            }
                        }
                    }
                } catch (e) {
                    // Ignore transient network errors during polling
                }
            }

            timerId = setInterval(checkPaymirStatus, 2500);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    checkPaymirStatus();
                }
            });
        })();
    </script>
@endif

