@props([
    'title' => 'No se pudo cargar la información',
    'message' => 'El servicio no está disponible temporalmente. Intenta nuevamente en unos minutos.',
    'errorLabel' => 'Error detectado',
    'reference' => null,
    'retryUrl' => null,
    'homeUrl' => null,
    'badge' => 'Portal Operativo HSJ',
    'showRetry' => true,
])

<main class="hsj-error-page">
    <section class="hsj-error-card" role="alert" aria-labelledby="hsj-error-title">
        <div class="hsj-error-scene" aria-hidden="true">
            <span class="hsj-error-cloud hsj-error-cloud-one"></span>
            <span class="hsj-error-cloud hsj-error-cloud-two"></span>
            <span class="hsj-error-ground"></span>

            <div class="hsj-error-target">
                <span class="hsj-error-target-icon">!</span>
                <span>{{ $errorLabel }}</span>
            </div>

            <div class="hsj-error-dog-runner">
                <span class="hsj-error-dog-shadow"></span>
                <img
                    class="hsj-error-dog"
                    src="{{ asset('assets/brand/hsj-bullterrier-error.gif') }}"
                    alt=""
                    width="320"
                    height="320"
                >
            </div>
        </div>

        <div class="hsj-error-badge">
            <span class="hsj-error-badge-dot"></span>
            {{ $badge }}
        </div>

        <h1 id="hsj-error-title">{{ $title }}</h1>
        <p class="hsj-error-message">{{ $message }}</p>

        @if($reference)
            <p class="hsj-error-reference">
                Código de soporte
                <code>{{ $reference }}</code>
            </p>
        @endif

        <div class="hsj-error-actions">
            @if($showRetry)
                <a class="hsj-error-primary" href="{{ $retryUrl ?: url()->current() }}">
                    <span aria-hidden="true">↻</span>
                    Reintentar
                </a>
            @endif
            <a class="hsj-error-secondary" href="{{ $homeUrl ?: url('/') }}">
                <span aria-hidden="true">⌂</span>
                Volver al inicio
            </a>
        </div>
    </section>
</main>

@once
    <style>
        :root {
            color-scheme: light dark;
            --hsj-ink: #17324d;
            --hsj-muted: #63758a;
            --hsj-border: #d3dee8;
            --hsj-card: #ffffff;
            --hsj-page: #f3f7fa;
            --hsj-scene: #e8fbfb;
            --hsj-teal: #087f7b;
            --hsj-teal-dark: #066a67;
            --hsj-danger: #b42318;
            --hsj-danger-bg: #fff1ef;
            --hsj-danger-border: #fecdc8;
        }

        * { box-sizing: border-box; }

        .hsj-error-page {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(circle at 15% 10%, rgba(8, 127, 123, .07), transparent 28rem),
                var(--hsj-page);
            color: var(--hsj-ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .hsj-error-card {
            width: min(36rem, 100%);
            padding: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--hsj-border);
            border-radius: 1.25rem;
            background: var(--hsj-card);
            box-shadow: 0 1.25rem 3.25rem rgba(23, 50, 77, .12);
            text-align: center;
        }

        .hsj-error-scene {
            position: relative;
            height: 12rem;
            margin-bottom: 1.25rem;
            overflow: hidden;
            border-radius: 1rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .38), transparent 65%),
                var(--hsj-scene);
            isolation: isolate;
        }

        .hsj-error-ground {
            position: absolute;
            z-index: 1;
            right: 0;
            bottom: 1.15rem;
            left: 0;
            height: 1px;
            background: rgba(23, 50, 77, .15);
        }

        .hsj-error-cloud {
            position: absolute;
            z-index: 0;
            height: .7rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .92);
            filter: blur(.2px);
        }

        .hsj-error-cloud::before,
        .hsj-error-cloud::after {
            position: absolute;
            bottom: 0;
            content: "";
            border-radius: 50%;
            background: inherit;
        }

        .hsj-error-cloud::before { width: 1rem; height: 1rem; left: .5rem; }
        .hsj-error-cloud::after { width: .8rem; height: .8rem; right: .45rem; }
        .hsj-error-cloud-one { top: 1.3rem; width: 3.4rem; animation: hsj-cloud-drift 16s linear infinite; }
        .hsj-error-cloud-two { top: 3.5rem; width: 2.4rem; opacity: .72; animation: hsj-cloud-drift 22s linear -8s infinite; }

        .hsj-error-target {
            position: absolute;
            z-index: 4;
            top: 1.6rem;
            left: 68%;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            max-width: 11.5rem;
            padding: .45rem .72rem;
            border: 1px solid var(--hsj-danger-border);
            border-radius: 999px;
            background: var(--hsj-danger-bg);
            color: var(--hsj-danger);
            font-size: .72rem;
            font-weight: 750;
            line-height: 1;
            white-space: nowrap;
            box-shadow: 0 .4rem 1rem rgba(180, 35, 24, .1);
            transform-origin: center;
            animation: hsj-error-escape 7.2s cubic-bezier(.45, 0, .25, 1) infinite;
        }

        .hsj-error-target-icon {
            display: grid;
            width: 1.15rem;
            height: 1.15rem;
            place-items: center;
            border-radius: 50%;
            background: var(--hsj-danger);
            color: white;
            font-size: .72rem;
            font-weight: 900;
        }

        .hsj-error-dog-runner {
            position: absolute;
            z-index: 3;
            bottom: -.15rem;
            left: 1%;
            width: 9rem;
            height: 9rem;
            transform-origin: 50% 100%;
            animation: hsj-dog-chase 7.2s cubic-bezier(.42, 0, .2, 1) infinite;
        }

        .hsj-error-dog {
            position: relative;
            z-index: 2;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .hsj-error-dog-shadow {
            position: absolute;
            z-index: 1;
            right: 18%;
            bottom: 1.35rem;
            left: 18%;
            height: .55rem;
            border-radius: 50%;
            background: rgba(23, 50, 77, .13);
            filter: blur(2px);
            animation: hsj-shadow-pulse 1.85s ease-in-out infinite;
        }

        .hsj-error-badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .85rem;
            padding: .35rem .72rem;
            border-radius: 999px;
            background: #e9f8f4;
            color: var(--hsj-teal-dark);
            font-size: .75rem;
            font-weight: 750;
        }

        .hsj-error-badge-dot {
            width: .48rem;
            height: .48rem;
            border-radius: 50%;
            background: var(--hsj-teal);
            box-shadow: 0 0 0 .2rem rgba(8, 127, 123, .12);
        }

        .hsj-error-card h1 {
            margin: 0 0 .5rem;
            color: var(--hsj-ink);
            font-size: clamp(1.3rem, 4vw, 1.65rem);
            line-height: 1.2;
        }

        .hsj-error-message {
            max-width: 29rem;
            margin: 0 auto 1rem;
            color: var(--hsj-muted);
            font-size: .94rem;
            line-height: 1.6;
        }

        .hsj-error-reference {
            margin: 0 auto 1.15rem;
            color: var(--hsj-muted);
            font-size: .78rem;
        }

        .hsj-error-reference code {
            display: inline-block;
            margin-left: .3rem;
            padding: .25rem .45rem;
            border-radius: .35rem;
            background: #edf3f7;
            color: var(--hsj-ink);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 800;
        }

        .hsj-error-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .65rem;
        }

        .hsj-error-actions a {
            display: inline-flex;
            min-height: 2.55rem;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .65rem 1rem;
            border-radius: .65rem;
            font-size: .84rem;
            font-weight: 750;
            text-decoration: none;
            transition: transform .18s ease, background-color .18s ease, border-color .18s ease;
        }

        .hsj-error-actions a:hover { transform: translateY(-1px); }
        .hsj-error-primary { border: 1px solid var(--hsj-teal); background: var(--hsj-teal); color: #fff; }
        .hsj-error-primary:hover { background: var(--hsj-teal-dark); }
        .hsj-error-secondary { border: 1px solid var(--hsj-border); background: transparent; color: var(--hsj-ink); }
        .hsj-error-secondary:hover { border-color: #a8bac9; background: #f5f8fa; }

        @keyframes hsj-dog-chase {
            0%, 8% { left: 1%; opacity: 1; transform: translate3d(0, 0, 0) scale(.96); }
            17% { left: 9%; transform: translate3d(0, 3px, 0) scale(.98, .94); }
            30% { left: 24%; transform: translate3d(0, -4px, 0) rotate(-1deg) scale(1); }
            43% { left: 39%; transform: translate3d(0, -9px, 0) rotate(1deg) scale(1.02); }
            56% { left: 51%; transform: translate3d(0, -3px, 0) scale(1); }
            68% { left: 60%; transform: translate3d(0, -7px, 0) rotate(-1deg) scale(1); }
            74% { left: 64%; opacity: 1; transform: translate3d(0, 0, 0) scale(.98); }
            78% { left: 67%; opacity: 0; transform: translate3d(0, 2px, 0) scale(.96); }
            79% { left: 1%; opacity: 0; }
            86%, 100% { left: 1%; opacity: 1; transform: translate3d(0, 0, 0) scale(.96); }
        }

        @keyframes hsj-error-escape {
            0%, 17% { left: 68%; opacity: 1; transform: translate3d(0, 0, 0) rotate(0); }
            29% { left: 70%; transform: translate3d(8px, -5px, 0) rotate(2deg); }
            42% { left: 73%; transform: translate3d(-4px, 2px, 0) rotate(-1deg); }
            54% { left: 77%; transform: translate3d(5px, -8px, 0) rotate(2deg); }
            67% { left: 80%; transform: translate3d(-2px, 0, 0) rotate(-1deg); }
            74% { left: 82%; opacity: 1; transform: translate3d(3px, -4px, 0) scale(.9); }
            78% { left: 84%; opacity: 0; transform: translate3d(8px, -8px, 0) scale(.7); }
            79% { left: 68%; opacity: 0; transform: scale(.88); }
            86%, 100% { left: 68%; opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
        }

        @keyframes hsj-cloud-drift {
            from { left: -4rem; }
            to { left: calc(100% + 4rem); }
        }

        @keyframes hsj-shadow-pulse {
            0%, 100% { opacity: .9; transform: scaleX(1); }
            50% { opacity: .45; transform: scaleX(.72); }
        }

        @media (max-width: 32rem) {
            .hsj-error-page { padding: .75rem; }
            .hsj-error-card { padding: 1rem; border-radius: 1rem; }
            .hsj-error-scene { height: 10.5rem; }
            .hsj-error-dog-runner { width: 7.75rem; height: 7.75rem; }
            .hsj-error-target { left: 59%; max-width: 9.5rem; overflow: hidden; text-overflow: ellipsis; }
            @keyframes hsj-dog-chase {
                0%, 8% { left: 0; opacity: 1; transform: scale(.94); }
                17% { left: 7%; transform: translateY(3px) scale(.96, .92); }
                30% { left: 18%; transform: translateY(-4px) scale(.98); }
                43% { left: 31%; transform: translateY(-8px) rotate(1deg); }
                56% { left: 43%; transform: translateY(-3px); }
                68% { left: 52%; transform: translateY(-6px); }
                74% { left: 57%; opacity: 1; }
                78% { left: 60%; opacity: 0; }
                79% { left: 0; opacity: 0; }
                86%, 100% { left: 0; opacity: 1; transform: scale(.94); }
            }

            @keyframes hsj-error-escape {
                0%, 17% { left: 52%; opacity: 1; transform: translate3d(0, 0, 0); }
                29% { left: 53%; transform: translate3d(4px, -4px, 0) rotate(1deg); }
                42% { left: 54%; transform: translate3d(-2px, 2px, 0) rotate(-1deg); }
                54% { left: 56%; transform: translate3d(3px, -6px, 0) rotate(1deg); }
                67% { left: 57%; transform: translate3d(-2px, 0, 0); }
                74% { left: 58%; opacity: 1; transform: translate3d(2px, -3px, 0) scale(.9); }
                78% { left: 59%; opacity: 0; transform: translate3d(5px, -6px, 0) scale(.7); }
                79% { left: 52%; opacity: 0; transform: scale(.88); }
                86%, 100% { left: 52%; opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hsj-error-cloud,
            .hsj-error-target,
            .hsj-error-dog-runner,
            .hsj-error-dog-shadow {
                animation: none !important;
            }

            .hsj-error-dog-runner { left: 28%; }
            .hsj-error-target { left: 61%; }
            .hsj-error-actions a { transition: none; }
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --hsj-ink: #e5edf5;
                --hsj-muted: #a8b8c8;
                --hsj-border: #34495e;
                --hsj-card: #172433;
                --hsj-page: #0f1823;
                --hsj-scene: #163b3c;
                --hsj-danger: #ffaaa2;
                --hsj-danger-bg: #452421;
                --hsj-danger-border: #71342f;
            }

            .hsj-error-reference code { background: #26394b; }
            .hsj-error-secondary:hover { background: #203244; }
        }
    </style>
@endonce
