<?php
/**
 * Página principal del sistema
 * Sistema de Galerías con QR - Colores: #826948 #F89E9D #F7EEDE
 */

require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Álbum Digital - Eventos Fotográficos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '696579809644575');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=696579809644575&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
    <style>
:root {
            --primary-brown: #1a1a1a;
            --primary-pink: #F89E9D;
            --primary-cream: #2a2a2a;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
        }
        
        /* Navigation con efecto vidrio esmerilado */
/* Navigation con efecto vidrio esmerilado transparente y texto blanco */
.navbar {
    background: rgba(0, 0, 0, 0.25) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

/* Navbar al hacer scroll */
.navbar.scrolled {
    background: rgba(0, 0, 0, 0.35) !important;
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
}

/* Texto blanco para navbar */
.navbar-brand {
    color: white !important;
    font-weight: 700;
    font-size: 1.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.nav-link {
    color: white !important;
    font-weight: 500;
    margin: 0 10px;
    transition: all 0.3s ease;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.nav-link:hover {
    color: var(--primary-cream) !important;
    transform: translateY(-2px);
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
}

.navbar-brand:hover {
    color: var(--primary-cream) !important;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
}

/* Botón hamburguesa blanco en móviles */
.navbar-toggler {
    border-color: rgba(255, 255, 255, 0.3);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}
        
        /* Hero Section */
        .hero-section {
            padding: 120px 0 80px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            color: white;
        }
        
        .hero-content .lead {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
            color: white;
        }
        
        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        
        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: #000000;
            transform: translateY(-3px);
            text-decoration: none;
        }
        
        /* Feature Cards */
        .feature-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 40px 30px;
            margin-bottom: 30px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 25px;
            display: block;
        }
        
        .feature-card h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .feature-card p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Steps Section */
        .steps-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .step-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        
        .step-number {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
        }
        
        .step-icon {
            font-size: 2.5rem;
            color: var(--primary-pink);
            margin-bottom: 20px;
        }
        
        .step-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .step-card p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Pricing Section */
        .pricing-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            backdrop-filter: blur(15px);
        }
        
        .pricing-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-card.featured {
            border: 3px solid var(--primary-pink);
            transform: scale(1.05);
        }
        
        .pricing-card.featured::before {
            content: "MÁS POPULAR";
            position: absolute;
            top: 20px;
            left: -30px;
            background: var(--primary-pink);
            color: white;
            padding: 5px 40px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(-45deg);
        }
        
        .pricing-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-photos {
            color: var(--primary-pink);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }
        
        .pricing-features li {
            margin-bottom: 10px;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .pricing-features li i {
            color: var(--primary-pink);
            margin-right: 10px;
        }
        
        /* Estilos para plan único */
        .pricing-card.single-plan {
            max-width: 500px;
            margin: 0 auto;
            transform: scale(1.05);
            border: 3px solid var(--primary-pink);
            box-shadow: 0 20px 60px rgba(248, 158, 157, 0.3);
        }
        
        .pricing-card.single-plan:hover {
            transform: scale(1.08);
            box-shadow: 0 25px 70px rgba(248, 158, 157, 0.4);
        }
        
        .plan-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(45deg, var(--primary-pink), #ff6b9d);
            color: white;
            padding: 8px 25px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(248, 158, 157, 0.4);
        }
        
        .plan-badge i {
            margin-right: 5px;
            animation: sparkle 2s ease-in-out infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(180deg); }
        }
        
        .pricing-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 5px;
        }
        
        .pricing-price .currency {
            font-size: 2rem;
            font-weight: 600;
        }
        
        .pricing-price .period {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--primary-pink);
        }
        
        .price-description {
            margin-bottom: 30px;
        }
        
        .price-description small {
            color: #ccc;
            font-size: 0.9rem;
        }
        
        .pricing-features {
            text-align: left;
            margin-bottom: 40px;
        }
        
        .pricing-features li {
            padding: 8px 0;
            font-size: 1rem;
        }
        
        .pricing-features li strong {
            color: var(--primary-pink);
        }
        
        .pricing-cta {
            text-align: center;
        }
        
        .pricing-cta .btn-lg {
            padding: 18px 40px;
            font-size: 1.2rem;
            margin-bottom: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .guarantee-text {
            color: #ccc;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .guarantee-text i {
            color: var(--primary-pink);
            margin-right: 5px;
        }
        
        /* Beneficios adicionales */
        .benefit-item {
            padding: 30px 20px;
            transition: all 0.3s ease;
        }
        
        .benefit-item:hover {
            transform: translateY(-10px);
        }
        
        .benefit-item i {
            color: var(--primary-pink);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .benefit-item:hover i {
            transform: scale(1.1);
            filter: drop-shadow(0 0 10px rgba(248, 158, 157, 0.5));
        }
        
        .benefit-item h5 {
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }
        
        .benefit-item p {
            color: #ccc;
            line-height: 1.6;
        }
        
        /* FAQ Section */
        .faq-section {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
        }
        
        .faq-item {
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 25px;
        }
        
        .faq-question {
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .faq-answer {
            color: white;
            line-height: 1.6;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Contact Section */
        .contact-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            text-align: center;
            backdrop-filter: blur(15px);
        }
        
        .contact-section h2 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .contact-section p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border-radius: 50px;
            padding: 20px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            color: white;
            transform: translateY(-3px);
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
        }
        
        /* Admin Button */
        .admin-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #000000;
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .admin-btn:hover {
            background: var(--primary-pink);
            color: white;
            transform: scale(1.1);
            text-decoration: none;
        }
        
        /* Floating Elements */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            animation: float 8s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 20%; left: 80%; animation-delay: 2s; }
        .floating-element:nth-child(3) { top: 60%; left: 5%; animation-delay: 4s; }
        .floating-element:nth-child(4) { top: 70%; left: 85%; animation-delay: 1s; }
        .floating-element:nth-child(5) { top: 40%; left: 70%; animation-delay: 3s; }
        .floating-element:nth-child(6) { top: 80%; left: 30%; animation-delay: 5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        /* Section Titles */
        .section-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .section-title-dark {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        /* Texto general blanco */
        h1, h2, h3, h4, h5, h6 {
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        p, span, div {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .steps-section, .pricing-section, .faq-section, .contact-section {
                padding: 40px 20px;
                margin: 40px 0;
            }
            
            .pricing-card.featured {
                transform: none;
                margin-top: 20px;
            }
            
            .pricing-card.single-plan {
                transform: none !important;
                margin: 20px auto;
            }
            
            .pricing-card.single-plan:hover {
                transform: translateY(-5px) !important;
            }
            
            .plan-badge {
                font-size: 11px;
                padding: 6px 20px;
            }
            
            .pricing-price {
                font-size: 2.5rem;
            }
            
            .benefit-item {
                padding: 20px 15px;
                margin-bottom: 30px;
            }
            
            .benefit-item i {
                font-size: 2rem !important;
            }
            
            .admin-btn {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }
        }
        /* Ajustes específicos para 2 pasos centrados */
.steps-section .row.justify-content-center {
    max-width: 900px;
    margin: 0 auto;
    gap: 30px;
}

.steps-section .col-md-5 {
    flex: 0 0 auto;
    width: 45%;
}

/* Responsivo para móviles */
@media (max-width: 768px) {
    .steps-section .col-md-5 {
        width: 100%;
        margin-bottom: 30px;
    }
    
    .steps-section .row.justify-content-center {
        gap: 0;
    }
}
/* Logo con efecto resplandor */
.navbar-logo {
    height: 90px;
    width: auto;
    transition: all 0.4s ease;
    background: transparent;
    filter: contrast(1.1) brightness(1.05) drop-shadow(0 0 8px rgba(248, 158, 157, 0.3));
    mix-blend-mode: multiply;
}

.navbar-logo:hover {
    transform: scale(1.08);
    filter: contrast(1.2) brightness(1.1) drop-shadow(0 0 15px rgba(248, 158, 157, 0.6));
}

/* Ajuste responsivo para el logo */
@media (max-width: 768px) {
    .navbar-logo {
        height: 65px;
    }
}
/* Logo en el footer */
.footer-logo {
    height: 90px;
    width: auto;
    transition: all 0.3s ease;
    filter: brightness(1.2) contrast(1.1);
    margin-bottom: 10px;
}

.footer-logo:hover {
    transform: scale(1.05);
    filter: brightness(1.3) contrast(1.2) drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
}
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
<a class="navbar-brand" href="#inicio">
    <img src="images/logo.png" alt="Álbum Digital QR" class="navbar-logo">
</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#como-funciona">Cómo Funciona</a></li>
                    <li class="nav-item"><a class="nav-link" href="#planes">Planes</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="floating-elements">
        <i class="fas fa-camera floating-element fa-4x"></i>
        <i class="fas fa-qrcode floating-element fa-3x"></i>
        <i class="fas fa-images floating-element fa-4x"></i>
        <i class="fas fa-photo-video floating-element fa-3x"></i>
        <i class="fas fa-users floating-element fa-4x"></i>
        <i class="fas fa-mobile-alt floating-element fa-3x"></i>
    </div>

    <!-- Hero Section -->
    <section id="inicio" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto hero-content">
                    <i class="fas fa-camera fa-3x mb-4"></i>
                    <h1>Reúne los mejores momentos de tu evento en un álbum digital</h1>
                    <p class="lead">
                        Permite que tus invitados compartan fotos de manera fácil y organizada. 
                        Genera códigos QR únicos y recopila todas las fotos en un solo lugar.
                    </p>
                    <div class="hero-buttons">
                        <a href="https://albumdigital.online/galeria.php?token=c638cc97a0ef90f3793e86dc8d3f88885dbad9a1fde87a8d17b4705b0f07e6be" class="btn-primary-custom me-3">
                            <i class="fas fa-play"></i> Ver Demo
                        </a>
                        <a href="#como-funciona" class="btn-outline-custom">
                            <i class="fas fa-info-circle"></i> Cómo Funciona
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works - Usuario -->
    <section class="container" id="como-funciona">
        <div class="steps-section">
            <h2 class="section-title">
                <i class="fas fa-lightbulb"></i> Descubre todo lo que puedes hacer con tu álbum digital
            </h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="fas fa-camera step-icon"></i>
                        <h4 class="step-title">CAPTURA</h4>
                        <p>Fotografía los mejores momentos del evento. Cada invitado puede contribuir con sus mejores tomas.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="fas fa-qrcode step-icon"></i>
                        <h4 class="step-title">COMPARTE</h4>
                        <p>
Al obtener tu QR podrás compartirlo el día del evento para que los invitados puedan subir sus fotos y ver el álbum </p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <i class="fas fa-heart step-icon"></i>
                        <h4 class="step-title">DISFRUTA</h4>
                        <p>
Con tu álbum podrás visualizar todas las fotos del evento y los likes de las fotos más votadas </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<section class="container">
    <div class="steps-section">
        <h2 class="section-title">
            <i class="fas fa-shopping-cart"></i> ¿Cómo adquiero mi álbum digital?
        </h2>
        
        <!-- Contenedor centrado para 2 pasos -->
        <div class="row justify-content-center">
            <div class="col-md-5 mb-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <i class="fas fa-credit-card step-icon"></i>
                    <h4 class="step-title">COMPRA</h4>
                    <p>Al elegir tu plan se te otorgará tu QR y una contraseña para tu registro y puedas administrar a tu álbum digital</p>
                </div>
            </div>
            <div class="col-md-5 mb-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <i class="fas fa-user-plus step-icon"></i>
                    <h4 class="step-title">REGISTRO</h4>
                    <p>Comparte tu QR el día de tu evento para que los invitados puedan subir sus fotos y ver el álbum.</p>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- Pricing Plans -->
    <section class="container" id="planes">
        <div class="pricing-section">
            <h2 class="section-title">
                <i class="fas fa-crown"></i> Nuestro Plan Profesional
            </h2>
            
            <!-- Plan único centrado -->
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 mb-4">
                    <div class="pricing-card featured single-plan">
                        <div class="plan-badge">
                            <i class="fas fa-star"></i> PLAN RECOMENDADO
                        </div>
                        <h3 class="pricing-title">Profesional</h3>
                        <div class="pricing-photos">Fotos Ilimitadas</div>
                        <div class="pricing-price">
                            <span class="currency">$</span>599
                            <span class="period">MXN</span>
                        </div>
                        <div class="price-description">
                            <small>Pago único • Sin mensualidades</small>
                        </div>
                        
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> <strong>✨ USUARIOS COMPLETAMENTE ILIMITADOS</strong></li>
                            <li><i class="fas fa-check"></i> <strong>✨ FOTOS TOTALMENTE ILIMITADAS</strong> por usuario</li>
                            <li><i class="fas fa-check"></i> <strong>✨ CAPACIDAD ILIMITADA</strong> por galería</li>
                            <li><i class="fas fa-check"></i> Código QR personalizado</li>
                            <li><i class="fas fa-check"></i> Descarga en ZIP y PDF</li>
                            <li><i class="fas fa-check"></i> Diseño personalizado del QR</li>
                            <li><i class="fas fa-check"></i> Sistema de likes interactivo</li>
                            <li><i class="fas fa-check"></i> Álbum disponible para siempre</li>
                            <li><i class="fas fa-check"></i> Sin apps necesarias</li>
                            <li><i class="fas fa-check"></i> Soporte técnico incluido</li>
                        </ul>
                        
                        <div class="pricing-cta">
                            <a href="#contacto" class="btn-primary-custom btn-lg">
                                <i class="fab fa-whatsapp"></i> Comprar Ahora
                            </a>
                            <p class="guarantee-text">
                                <i class="fas fa-shield-alt"></i> Garantía de satisfacción
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Beneficios adicionales -->
            <div class="row mt-5">
                <div class="col-md-4 text-center">
                    <div class="benefit-item">
                        <i class="fas fa-infinity fa-3x mb-3"></i>
                        <h5>Sin Límites</h5>
                        <p>Fotos y usuarios ilimitados para tu evento</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="benefit-item">
                        <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                        <h5>Fácil de Usar</h5>
                        <p>Solo escanea el QR y sube fotos desde cualquier dispositivo</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="benefit-item">
                        <i class="fas fa-download fa-3x mb-3"></i>
                        <h5>Descarga Total</h5>
                        <p>Obtén todas las fotos en alta calidad cuando quieras</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="container" id="faq">
        <div class="faq-section">
            <h2 class="section-title-dark">
                <i class="fas fa-question-circle"></i> Preguntas Frecuentes
            </h2>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="faq-item">
                        <div class="faq-question">¿Cuánto tiempo está en línea el álbum?</div>
                        <div class="faq-answer">Indefinidamente, hasta que tu des autorizacion de borrarlo.</div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">¿Se puede descargar el álbum?</div>
                        <div class="faq-answer">Sí, puedes descargar todas las fotos en formato ZIP y tenerlo de recuerdo para siempre.</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="faq-item">
                        <div class="faq-question">¿Hacen diseños con mi QR para compartir?</div>
                        <div class="faq-answer">Sí, en los planes Profesional y Premium incluimos diseño personalizado para el día del evento.</div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">¿Necesito descargar alguna app?</div>
                        <div class="faq-answer">No, todo funciona desde el navegador web. Solo escanea el QR y listo.</div>
                    </div>
                    
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="container" id="contacto">
        <div class="contact-section">
            <h2 class="section-title">
                <i class="fas fa-comments"></i> ¿Listo para crear tu álbum digital?
            </h2>
            <p class="text-white mb-4 lead">
                Contáctanos por WhatsApp para adquirir tu plan y comenzar a crear recuerdos inolvidables
            </p>
            
            <div class="contact-buttons">
                <a href="https://wa.me/message/JGBPBAWAWEWQH1?text=Hola%2C%20me%20interesa%20el%20%C3%Un A1lbum%20Digital%20QR" 
                   class="whatsapp-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-white opacity-75">
                    <i class="fas fa-clock"></i> La Mejor Atencion Personalizada • 
                    <i class="fas fa-shield-alt"></i> Pago 100% seguro
                </small>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center text-white py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Álbum Digital. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Navbar transparency on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Animate cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card, .step-card, .pricing-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });

        // Pricing card interaction
        document.querySelectorAll('.pricing-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                if (this.classList.contains('featured')) {
                    this.style.transform = 'translateY(-10px) scale(1.05)';
                } else {
                    this.style.transform = 'translateY(0) scale(1)';
                }
            });
        });

        // WhatsApp button enhancement
        document.querySelectorAll('.whatsapp-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Add analytics tracking here if needed
                console.log('WhatsApp contact initiated');
            });
        });

        // Admin button hide/show on scroll
        let lastScrollTop = 0;
        const adminBtn = document.querySelector('.admin-btn');
        
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 200) {
                // Scrolling down
                adminBtn.style.transform = 'translateX(100px)';
            } else {
                // Scrolling up
                adminBtn.style.transform = 'translateX(0)';
            }
            lastScrollTop = scrollTop;
        });

        // FAQ accordion-like behavior
        document.querySelectorAll('.faq-question').forEach(question => {
            question.style.cursor = 'pointer';
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const isVisible = answer.style.display === 'block';
                
                // Hide all answers
                document.querySelectorAll('.faq-answer').forEach(ans => {
                    ans.style.display = 'none';
                });
                
                // Show clicked answer if it wasn't visible
                if (!isVisible) {
                    answer.style.display = 'block';
                    answer.style.animation = 'fadeIn 0.3s ease';
                }
            });
        });

        // Add fade in animation for FAQ
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);

        // Contact form enhancement (if added later)
        function trackContactClick(plan) {
            // Analytics tracking for different plans
            console.log(`Contact initiated for plan: ${plan}`);
        }

        // Add click tracking to plan buttons
        document.querySelectorAll('.pricing-card .btn-primary-custom').forEach((btn, index) => {
            const plans = ['Básico', 'Profesional', 'Premium'];
            btn.addEventListener('click', function() {
                trackContactClick(plans[index]);
            });
        });

        // Floating elements animation enhancement
        document.querySelectorAll('.floating-element').forEach((element, index) => {
            element.addEventListener('mouseenter', function() {
                this.style.animationPlayState = 'paused';
                this.style.color = 'rgba(255, 255, 255, 0.3)';
            });
            
            element.addEventListener('mouseleave', function() {
                this.style.animationPlayState = 'running';
                this.style.color = 'rgba(255, 255, 255, 0.1)';
            });
        });

        // Preload images for better performance
        function preloadImages() {
            const imageUrls = [
                // Add any background images or icons that need preloading
            ];
            
            imageUrls.forEach(url => {
                const img = new Image();
                img.src = url;
            });
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            preloadImages();
            
            // Add loading states for dynamic content
            const dynamicElements = document.querySelectorAll('[data-load]');
            dynamicElements.forEach(element => {
                element.style.opacity = '0';
                setTimeout(() => {
                    element.style.transition = 'opacity 0.5s ease';
                    element.style.opacity = '1';
                }, 100);
            });
        });

        // Performance optimization: Debounce scroll events
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Apply debouncing to scroll events
        const debouncedScrollHandler = debounce(() => {
            // Any heavy scroll processing here
        }, 16); // ~60fps

        window.addEventListener('scroll', debouncedScrollHandler);
        // Efecto vidrio esmerilado mejorado al hacer scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});
    </script>
</body>
</html>