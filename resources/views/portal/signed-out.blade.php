<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signed out · Good Servant</title>
    <link rel="icon" type="image/x-icon" href="{{ config('app.url') }}/favicon.ico">

    <style>
        /* Self-hosted Inter — same files the portal bundle uses, declared here
           so this page stands alone and needs no compiled CSS. */
        @font-face {
            font-family: 'Inter'; font-style: normal; font-weight: 400;
            font-display: swap;
            src: url('/fonts/inter/inter-latin-400-normal.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Inter'; font-style: normal; font-weight: 500;
            font-display: swap;
            src: url('/fonts/inter/inter-latin-500-normal.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Inter'; font-style: normal; font-weight: 600;
            font-display: swap;
            src: url('/fonts/inter/inter-latin-600-normal.woff2') format('woff2');
        }

        :root {
            --paper: #faf9f7;
            --ink:   #0b0b0c;
            --muted: #8a8780;
            --line:  #e7e4de;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            display: grid;
            place-items: center;
            padding: 2rem;
            /* Faint vignette of warmth — the only ornament on the page. */
            background-image:
                radial-gradient(120% 80% at 50% -10%, #ffffff 0%, rgba(255,255,255,0) 60%);
        }

        .card {
            width: 100%;
            max-width: 30rem;
            text-align: center;
            animation: rise 1s cubic-bezier(.16,1,.3,1) both;
        }

        /* The mark: an open ring whose seam sits at the top — a session, closed.
           It draws itself once on load. */
        .mark {
            width: 44px; height: 44px;
            margin: 0 auto 2.75rem;
            display: block;
            color: var(--ink);
        }
        .mark circle {
            fill: none;
            stroke: currentColor;
            stroke-width: 1.25;
            stroke-linecap: round;
            stroke-dasharray: 116 134;      /* 116 of a 125.6 circumference drawn */
            stroke-dashoffset: 116;
            transform: rotate(-90deg);
            transform-origin: center;
            animation: draw 1.4s cubic-bezier(.65,0,.35,1) .15s both;
        }
        .mark .dot {
            fill: currentColor; stroke: none;
            opacity: 0;
            animation: fade .6s ease .9s both;
        }

        .eyebrow {
            font-size: .6875rem;
            font-weight: 500;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--muted);
        }

        h1 {
            margin-top: 1.25rem;
            font-size: clamp(2.25rem, 7vw, 3.25rem);
            font-weight: 600;
            letter-spacing: -0.035em;
            line-height: 1.02;
        }
        h1 .stop { color: var(--muted); }

        p {
            margin: 1.25rem auto 0;
            max-width: 26rem;
            font-size: .9375rem;
            line-height: 1.65;
            color: var(--muted);
        }

        .rule {
            width: 2rem; height: 1px;
            margin: 2.75rem auto 0;
            background: var(--line);
        }

        .foot {
            margin-top: 1.5rem;
            font-size: .6875rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes draw { to { stroke-dashoffset: 0; } }
        @keyframes fade { to { opacity: 1; } }

        @media (prefers-reduced-motion: reduce) {
            .card, .mark circle, .mark .dot { animation: none; }
            .mark circle { stroke-dashoffset: 0; }
            .mark .dot { opacity: 1; }
        }
    </style>
</head>
<body>
    <main class="card">
        <svg class="mark" viewBox="0 0 44 44" aria-hidden="true">
            <circle cx="22" cy="22" r="20"></circle>
            <circle class="dot" cx="22" cy="22" r="2.1"></circle>
        </svg>

        <p class="eyebrow">Good&nbsp;Servant</p>
        <h1>Signed&nbsp;out<span class="stop">.</span></h1>
        <p>
            Your session has ended. To order again, open the ordering link
            we shared with you &mdash; or ask us for a fresh one.
        </p>

        <div class="rule" aria-hidden="true"></div>
        <p class="foot">See you soon</p>
    </main>
</body>
</html>
