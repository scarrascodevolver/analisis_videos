<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Rugby Key Performance - Plataforma profesional de análisis de video y creación de jugadas para equipos de rugby. Mejora el rendimiento de tu equipo con herramientas avanzadas.">
    <meta name="keywords" content="rugby, análisis de video, jugadas, táctica, entrenamiento, equipo">
    <title>Rugby Key Performance - Análisis de Video y Jugadas para Rugby</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #005461;
            --primary-dark: #003d4a;
            --primary-light: #4A6274;
            --accent: #D4A017;
            --dark: #0a0a0a;
            --dark-lighter: #1a1a1a;
            --gray-900: #111111;
            --gray-800: #1f1f1f;
            --gray-700: #2d2d2d;
            --gray-600: #404040;
            --gray-400: #9ca3af;
            --gray-300: #d1d5db;
            --white: #ffffff;
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --gradient-accent: #D4A017;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--dark);
            color: var(--white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ========== NAVBAR ========== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0.4rem 2rem;
            transition: all 0.3s ease;
            background: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            background: rgba(10, 10, 10, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--white);
        }

        .logo-icon {
            width: 240px;
            height: 100px;
            overflow: hidden;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo-text span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--gray-300);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-ghost {
            background: transparent;
            color: var(--white);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background: var(--gradient-accent);
            color: var(--dark);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212, 160, 23, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: var(--accent);
            color: var(--dark);
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ========== HERO ========== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 8rem 2rem 4rem;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(212, 160, 23, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0, 84, 97, 0.2) 0%, transparent 40%),
                radial-gradient(ellipse at 60% 80%, rgba(74, 98, 116, 0.1) 0%, transparent 40%);
            z-index: 0;
        }

        /* ========== HERO BACKGROUND VIDEO ========== */
        .hero-video-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            overflow: hidden;
        }

        .hero-video-bg video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        .hero-video-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                135deg,
                rgba(0, 84, 97, 0.45) 0%,
                rgba(10, 10, 10, 0.35) 50%,
                rgba(0, 84, 97, 0.40) 100%
            );
            z-index: 1;
        }

        .hero-video-bg::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(to top, var(--dark) 0%, transparent 100%);
            z-index: 1;
        }

        .hero-grid {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 0;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content {
            max-width: 600px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(212, 160, 23, 0.1);
            border: 1px solid rgba(212, 160, 23, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-title .highlight {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.25rem;
            color: var(--gray-400);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-stats {
            display: flex;
            gap: 3rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-700);
        }

        .stat {
            text-align: left;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-400);
        }

        .hero-visual {
            position: relative;
        }

        .hero-image {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--gray-700);
        }

        .hero-float-card {
            position: absolute;
            background: var(--gray-800);
            border: 1px solid var(--gray-700);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .hero-float-card.card-1 {
            top: 10%;
            right: -20px;
            animation: float 6s ease-in-out infinite;
        }

        .hero-float-card.card-2 {
            bottom: 15%;
            left: -30px;
            animation: float 6s ease-in-out infinite 1s;
        }

        .float-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
        }

        .float-text {
            font-size: 0.875rem;
        }

        .float-text strong {
            display: block;
            font-weight: 600;
        }

        .float-text span {
            color: var(--gray-400);
            font-size: 0.75rem;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* ========== LIVE SCORE MOCKUP ========== */
        .live-score-mockup {
            background: var(--gray-800);
            border: 1px solid var(--gray-700);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
        }

        .mockup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-700);
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .match-time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }

        .mockup-teams {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .team-score {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .team-logo {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
        }

        .team-logo.home {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .team-logo.away {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .team-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .team-info.right {
            text-align: right;
        }

        .team-name {
            font-size: 0.875rem;
            color: var(--gray-300);
            font-weight: 500;
        }

        .score {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            line-height: 1;
        }

        .vs-divider {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        .mockup-events {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .event-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: var(--gray-900);
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .event-time {
            color: var(--gray-400);
            font-weight: 600;
            min-width: 30px;
        }

        .event-icon {
            font-size: 0.9rem;
        }

        .event-icon.try {
            color: #22c55e;
        }

        .event-icon.conversion {
            color: var(--accent);
        }

        .event-icon.sub {
            color: #f59e0b;
        }

        .event-text {
            color: var(--gray-300);
        }

        /* ========== FEATURES ========== */
        .features {
            padding: 8rem 2rem;
            background: var(--gray-900);
            position: relative;
        }

        .section-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(212, 160, 23, 0.1);
            border: 1px solid rgba(212, 160, 23, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray-400);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: var(--gray-800);
            border: 1px solid var(--gray-700);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(212, 160, 23, 0.1);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-accent);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .feature-description {
            color: var(--gray-400);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .feature-card.featured {
            border-color: rgba(212, 160, 23, 0.3);
            background: linear-gradient(135deg, var(--gray-800) 0%, rgba(0, 84, 97, 0.1) 100%);
        }

        .feature-badge {
            display: inline-block;
            background: var(--gradient-accent);
            color: var(--dark);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            margin-top: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========== HOW IT WORKS ========== */
        .how-it-works {
            padding: 8rem 2rem;
            background: var(--dark);
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 4rem;
        }

        .step {
            text-align: center;
            position: relative;
        }

        .step::after {
            content: '';
            position: absolute;
            top: 40px;
            right: -1rem;
            width: calc(100% - 80px);
            height: 2px;
            background: linear-gradient(90deg, var(--accent), transparent);
            display: none;
        }

        .step:not(:last-child)::after {
            display: block;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: var(--gray-800);
            border: 2px solid var(--gray-700);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 1;
        }

        .step-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        /* ========== PRICING ========== */
        .pricing {
            padding: 8rem 2rem;
            background: var(--gray-900);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
        }

        .pricing-card {
            background: var(--gray-800);
            border: 1px solid var(--gray-700);
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            transition: all 0.3s;
        }

        .pricing-card.featured {
            border-color: var(--accent);
            transform: scale(1.05);
        }

        .pricing-card.featured::before {
            content: 'Recomendado';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient-accent);
            color: var(--dark);
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .pricing-card:hover {
            border-color: var(--accent);
        }

        .pricing-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .pricing-description {
            color: var(--gray-400);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .pricing-price {
            margin-bottom: 2rem;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 700;
        }

        .price-currency {
            font-size: 1.5rem;
            vertical-align: top;
        }

        .price-period {
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-700);
            font-size: 0.95rem;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features i {
            color: var(--accent);
        }

        .pricing-card .btn {
            width: 100%;
            justify-content: center;
        }

        /* ========== FAQ ========== */
        .faq {
            padding: 8rem 2rem;
            background: var(--dark);
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 4rem;
        }

        .faq-item {
            background: var(--gray-800);
            border: 1px solid var(--gray-700);
            border-radius: 12px;
            overflow: hidden;
        }

        .faq-question {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .faq-question:hover {
            color: var(--accent);
        }

        .faq-question i {
            transition: transform 0.3s;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 200px;
        }

        .faq-answer-content {
            padding: 0 1.5rem 1.5rem;
            color: var(--gray-400);
            line-height: 1.7;
        }

        /* ========== CTA ========== */
        .cta {
            padding: 8rem 2rem;
            background: var(--gradient-primary);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 40%);
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-description {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta .btn-primary {
            background: var(--white);
            color: var(--primary);
        }

        .cta .btn-primary:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .cta .btn-outline {
            border-color: var(--white);
            color: var(--white);
        }

        .cta .btn-outline:hover {
            background: var(--white);
            color: var(--primary);
        }

        /* ========== FOOTER ========== */
        .footer {
            padding: 4rem 2rem 2rem;
            background: var(--gray-900);
            border-top: 1px solid var(--gray-800);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand p {
            color: var(--gray-400);
            margin-top: 1rem;
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .footer-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: color 0.3s;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid var(--gray-800);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-copyright {
            color: var(--gray-400);
            font-size: 0.875rem;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            background: var(--gray-800);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            transition: all 0.3s;
        }

        .footer-social a:hover {
            background: var(--accent);
            color: var(--dark);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .hero-visual {
                justify-self: center;
            }

            .hero-float-card {
                display: none;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .step::after {
                display: none !important;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }

            .pricing-card.featured {
                transform: none;
            }

            .faq-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav-buttons {
                display: none;
            }

            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps-container {
                grid-template-columns: 1fr;
            }

            .hero-stats {
                flex-direction: column;
                gap: 1.5rem;
            }

            .stat {
                text-align: center;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Mobile menu overlay */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 10, 10, 0.98);
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .mobile-menu.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu a {
            font-size: 1.5rem;
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s;
        }

        .mobile-menu a:hover {
            color: var(--accent);
        }

        .mobile-menu-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: none;
            border: none;
            color: var(--white);
            font-size: 2rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-container">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <img src="{{ asset('logo.png') }}" alt="Rugby Key Performance Logo">
                </div>
            </a>

            <ul class="nav-links">
                <li><a href="#features">Funciones</a></li>
                <li><a href="#how-it-works">Como funciona</a></li>
                <li><a href="#contacto">Contacto</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>

            <div class="nav-buttons">
                <a href="{{ route('login') }}" class="btn btn-ghost">Iniciar Sesion</a>
                <a href="#contacto" class="btn btn-primary">Contactar</a>
            </div>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="mobile-menu-close" onclick="toggleMobileMenu()">
            <i class="fas fa-times"></i>
        </button>
        <a href="#features" onclick="toggleMobileMenu()">Funciones</a>
        <a href="#how-it-works" onclick="toggleMobileMenu()">Como funciona</a>
        <a href="#contacto" onclick="toggleMobileMenu()">Contacto</a>
        <a href="#faq" onclick="toggleMobileMenu()">FAQ</a>
        <a href="{{ route('login') }}" class="btn btn-ghost">Iniciar Sesion</a>
        <a href="#contacto" class="btn btn-primary">Contactar</a>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-video-bg">
            <video autoplay muted loop playsinline>
                <source src="{{ asset('images/fondo.mp4') }}" type="video/mp4">
            </video>
        </div>
        <div class="hero-bg"></div>

        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-bolt"></i>
                    <span>Plataforma #1 de analisis para rugby</span>
                </div>

                <h1 class="hero-title">
                    Analiza cada jugada
                    <span class="highlight">como un profesional</span>
                </h1>

                <p class="hero-description">
                    Estudia el juego, domina la cancha. Crea clips, comenta jugadas clave y diseña tacticas animadas. Todo lo que tu club necesita en una sola plataforma.
                </p>

                <div class="hero-buttons">
                    <a href="#contacto" class="btn btn-primary btn-large">
                        <i class="fas fa-comments"></i>
                        Solicitar Demo
                    </a>
                    <a href="#features" class="btn btn-outline btn-large">
                        Ver funciones
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-number"><i class="fas fa-infinity"></i></div>
                        <div class="stat-label">Clips Ilimitados</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><i class="fas fa-sitemap"></i></div>
                        <div class="stat-label">Multi-Organizacion</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><i class="fas fa-cloud"></i></div>
                        <div class="stat-label">100% en la Nube</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-star"></i>
                    Funcionalidades
                </div>
                <h2 class="section-title">Todo lo que necesitas para el analisis</h2>
                <p class="section-description">
                    Herramientas profesionales disenadas especificamente para entrenadores y analistas de rugby.
                </p>
            </div>

            <div class="features-grid">
                <!-- Funciones REALES implementadas -->
                <div class="feature-card featured">
                    <div class="feature-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <h3 class="feature-title">Analisis de Video</h3>
                    <p class="feature-description">
                        Sube videos de partidos y entrenamientos. Marca momentos clave, crea clips y comparte con tu equipo.
                    </p>
                </div>

                <div class="feature-card featured">
                    <div class="feature-icon">
                        <i class="fas fa-scissors"></i>
                    </div>
                    <h3 class="feature-title">Clips Destacados</h3>
                    <p class="feature-description">
                        Crea clips de momentos importantes. Categoriza por tipo de jugada (try, tackle, lineout) y exporta clips individuales.
                    </p>
                </div>

                <div class="feature-card featured">
                    <div class="feature-icon">
                        <i class="fas fa-draw-polygon"></i>
                    </div>
                    <h3 class="feature-title">Editor de Jugadas</h3>
                    <p class="feature-description">
                        Crea jugadas tacticas con nuestro editor visual. Dibuja movimientos, posiciones y exporta a GIF o MP4 animado.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="feature-title">Comentarios en Video</h3>
                    <p class="feature-description">
                        Agrega comentarios en momentos especificos del video. Menciona jugadores con @ para notificarlos al instante.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <h3 class="feature-title">Anotaciones en Video</h3>
                    <p class="feature-description">
                        Dibuja directamente sobre el video. Marca jugadores, señala movimientos y guarda tus anotaciones con timestamp.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3 class="feature-title">Asignaciones</h3>
                    <p class="feature-description">
                        Asigna videos a jugadores para que estudien. Trackea quien ha visto cada contenido y por cuanto tiempo.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Evaluacion 360</h3>
                    <p class="feature-description">
                        Sistema de evaluacion entre pares. Los jugadores evaluan a sus companeros de forma anonima por periodos.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="feature-title">Tracking de Visualizaciones</h3>
                    <p class="feature-description">
                        Ve quien ha visto cada video, cuanto tiempo y cuantas veces. Ideal para verificar que estudien el material.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3 class="feature-title">Multi-Tenant</h3>
                    <p class="feature-description">
                        Cada club tiene su espacio aislado. Videos, usuarios y datos completamente separados por organizacion.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-cogs"></i>
                    Proceso
                </div>
                <h2 class="section-title">Como funciona</h2>
                <p class="section-description">
                    Comienza a analizar videos en minutos con estos simples pasos.
                </p>
            </div>

            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Crea tu club</h3>
                    <p class="step-description">Registra tu organizacion y configura tu equipo en minutos.</p>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Sube videos</h3>
                    <p class="step-description">Arrastra y suelta videos de partidos o entrenamientos.</p>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Analiza y marca</h3>
                    <p class="step-description">Crea clips, agrega comentarios y dibuja sobre el video.</p>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Comparte</h3>
                    <p class="step-description">Asigna contenido a jugadores y mide su progreso.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contacto Section -->
    <section class="pricing" id="contacto">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-phone"></i>
                    Contacto
                </div>
                <h2 class="section-title">Hablemos de tu proyecto</h2>
                <p class="section-description">
                    Contáctanos por WhatsApp y te mostraremos cómo Rugby Key Performance puede ayudar a tu club.
                </p>
            </div>

            <div class="pricing-grid" style="grid-template-columns: repeat(2, 1fr); max-width: 900px; margin: 4rem auto 0;">
                <!-- WhatsApp España -->
                <div class="pricing-card featured">
                    <div class="feature-icon" style="background: #25D366; margin: 0 auto 1.5rem;">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="pricing-name">España</h3>
                    <p class="pricing-description">Atención en horario europeo</p>
                    <div class="pricing-price">
                        <span class="price-amount" style="font-size: 1.8rem;">+34 614 065 223</span>
                    </div>
                    <a href="https://wa.me/34614065223?text=Hola,%20quiero%20información%20sobre%20Rugby Key Performance" target="_blank" class="btn btn-primary" style="background: #25D366; margin-top: 2rem;">
                        <i class="fab fa-whatsapp"></i> Escribir por WhatsApp
                    </a>
                </div>

                <!-- WhatsApp Chile -->
                <div class="pricing-card featured">
                    <div class="feature-icon" style="background: #25D366; margin: 0 auto 1.5rem;">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="pricing-name">Chile</h3>
                    <p class="pricing-description">Atención en horario chileno</p>
                    <div class="pricing-price">
                        <span class="price-amount" style="font-size: 1.8rem;">+56 9 8544 4418</span>
                    </div>
                    <a href="https://wa.me/56985444418?text=Hola,%20quiero%20información%20sobre%20Rugby Key Performance" target="_blank" class="btn btn-primary" style="background: #25D366; margin-top: 2rem;">
                        <i class="fab fa-whatsapp"></i> Escribir por WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <i class="fas fa-question-circle"></i>
                    FAQ
                </div>
                <h2 class="section-title">Preguntas frecuentes</h2>
                <p class="section-description">
                    Respuestas a las dudas mas comunes sobre Rugby Key Performance.
                </p>
            </div>

            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Que formatos de video soportan?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            Soportamos los formatos mas comunes: MP4, MOV, AVI y WebM. Los videos se procesan automaticamente para optimizar la reproduccion en cualquier dispositivo.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Puedo usar Rugby Key Performance en el celular?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            Si, la plataforma esta completamente optimizada para dispositivos moviles. Puedes ver videos, agregar comentarios y revisar jugadas desde cualquier smartphone o tablet.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Como funcionan las invitaciones?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            Cada club tiene un codigo de invitacion unico. Los entrenadores comparten este codigo con los jugadores para que se registren automaticamente en la organizacion correcta.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Mis videos estan seguros?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            Absolutamente. Cada organizacion tiene su espacio completamente aislado. Los videos se almacenan encriptados y solo los miembros autorizados de tu club pueden acceder a ellos.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Puedo exportar las jugadas?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            Si, puedes exportar las jugadas creadas en el editor como imagenes PNG o videos MP4 animados para compartir en presentaciones o redes sociales.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Ofrecen periodo de prueba?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">
                            El plan Starter es gratuito para siempre. Ademas, ofrecemos 14 dias de prueba del plan Pro sin necesidad de tarjeta de credito para que pruebes todas las funciones.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-container">
            <h2 class="cta-title">Listo para transformar tu equipo?</h2>
            <p class="cta-description">
                Solicita una demo personalizada y descubre todo lo que podemos hacer por tu club.
            </p>
            <div class="cta-buttons">
                <a href="https://wa.me/34614065223?text=Hola,%20quiero%20una%20demo%20de%20Rugby Key Performance" target="_blank" class="btn btn-primary btn-large" style="background: #25D366;">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp España
                </a>
                <a href="https://wa.me/56985444418?text=Hola,%20quiero%20una%20demo%20de%20Rugby Key Performance" target="_blank" class="btn btn-outline btn-large">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp Chile
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#" class="logo">
                        <div class="logo-icon">
                            <img src="{{ asset('logo.png') }}" alt="Rugby Key Performance Logo">
                        </div>
                    </a>
                    <p>
                        Plataforma profesional de analisis de video y creacion de jugadas para equipos de rugby de todos los niveles.
                    </p>
                </div>

                <div>
                    <h4 class="footer-title">Producto</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Funciones</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-title">Compania</h4>
                    <ul class="footer-links">
                        <li><a href="#">Sobre nosotros</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Terminos de uso</a></li>
                        <li><a href="#">Privacidad</a></li>
                        <li><a href="#">Cookies</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; {{ date('Y') }} Rugby Key Performance. Todos los derechos reservados.
                </p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        }

        // FAQ toggle
        function toggleFaq(element) {
            const item = element.parentElement;
            const wasActive = item.classList.contains('active');

            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(faq => {
                faq.classList.remove('active');
            });

            // Open clicked item if it wasn't already open
            if (!wasActive) {
                item.classList.add('active');
            }
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
