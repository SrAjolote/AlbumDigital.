<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Acceso Administrador - Galerías QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
:root {
            --primary-brown: #1a1a1a;
            --primary-pink: #F89E9D;
            --primary-cream: #2a2a2a;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, rgba(248, 158, 157, 0.3) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000000;
        }
        
        .login-container {
            background: #2a2a2a;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 20px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            color: white;
            padding: 30px 30px 20px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .login-header h4 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px 20px;
            font-size: 16px; /* Evita zoom en iOS */
            transition: all 0.3s ease;
            margin-bottom: 20px;
            background: #1a1a1a;
            color: #000000;
        }
        
        .form-control:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.2rem rgba(248, 158, 157, 0.25);
            transform: translateY(-2px);
            background: #1a1a1a;
            color: #000000;
        }
        
        .form-label {
            color: #000000;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .btn-login {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, #333333, #f48b8a);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .alert {
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .alert-danger i {
            color: #dc3545;
            margin-right: 8px;
        }
        
        .login-footer {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-footer small {
            color: #666;
            font-size: 0.8rem;
        }
        
        .login-footer a {
            color: #000000;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            color: var(--primary-pink);
        }
        
        /* Animaciones de entrada */
        .login-container {
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Loading spinner */
        .btn-login.loading {
            position: relative;
            color: transparent;
        }
        
        .btn-login.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Efectos para móviles */
        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .login-header {
                padding: 25px 20px 15px;
            }
            
            .login-header i {
                font-size: 2.5rem;
                margin-bottom: 10px;
            }
            
            .login-header h4 {
                font-size: 1.3rem;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .form-control {
                padding: 12px 15px;
                font-size: 16px;
            }
            
            .btn-login {
                padding: 12px 25px;
                font-size: 14px;
            }
            
            .login-footer {
                padding: 15px 20px;
            }
        }
        
        /* Partículas de fondo decorativas */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: var(--primary-pink);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) {
            left: 20%;
            width: 80px;
            height: 80px;
            animation-delay: -0.2s;
        }
        
        .particle:nth-child(2) {
            left: 60%;
            width: 50px;
            height: 50px;
            animation-delay: -3.2s;
        }
        
        .particle:nth-child(3) {
            left: 80%;
            width: 30px;
            height: 30px;
            animation-delay: -1.8s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.1;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.3;
            }
        }
        
        /* Seguridad visual */
        .password-strength {
            margin-top: 5px;
            font-size: 0.75rem;
            color: #666;
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #000000;
            cursor: pointer;
            padding: 5px;
            font-size: 14px;
        }
        
        .show-password:hover {
            color: var(--primary-pink);
        }
        
        .password-container {
            position: relative;
        }
        
        /* Input con icono */
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #000000;
            z-index: 2;
        }
        
        .input-with-icon .form-control {
            padding-left: 50px;
        }
    </style>
</head>
<body>
    <!-- Partículas decorativas -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h4>Panel de Administración</h4>
            <p>Sistema de Galerías QR</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($auth_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($auth_error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-key me-2"></i>Contraseña de Administrador
                    </label>
                    <div class="password-container">
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Ingrese la contraseña"
                                   required 
                                   autocomplete="current-password"
                                   maxlength="100">
                            <button type="button" class="show-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordToggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="password-strength">
                        <i class="fas fa-info-circle"></i> 
                        Ingrese sus credenciales de administrador
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <small>
                <i class="fas fa-camera me-1"></i>
                Sistema de Galerías QR v2.0
                <br>
                <a href="index.php">
                    <i class="fas fa-home me-1"></i>Volver al inicio
                </a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Manejar envío del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const password = document.getElementById('password').value.trim();
            
            // Validación básica
            if (password.length < 5) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 5 caracteres');
                return false;
            }
            
            // Mostrar loading
            btn.classList.add('loading');
            btn.disabled = true;
            
            // Si hay errores, quitar loading después de un tiempo
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
            }, 5000);
        });

        // Enfocar campo de contraseña al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            
            // Enfocar con un pequeño retraso para animaciones
            setTimeout(() => {
                passwordInput.focus();
            }, 600);
            
            // Efecto de escritura en placeholder
            let placeholderText = 'Ingrese la contraseña';
            let currentText = '';
            let index = 0;
            
            function typePlaceholder() {
                if (index < placeholderText.length) {
                    currentText += placeholderText[index];
                    passwordInput.setAttribute('placeholder', currentText + '|');
                    index++;
                    setTimeout(typePlaceholder, 100);
                } else {
                    passwordInput.setAttribute('placeholder', placeholderText);
                }
            }
            
            // Iniciar efecto solo si no hay valor
            if (!passwordInput.value) {
                passwordInput.setAttribute('placeholder', '|');
                setTimeout(typePlaceholder, 1000);
            }
        });

        // Efectos adicionales
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de entrada para elementos
            const elements = document.querySelectorAll('.login-header, .login-body, .login-footer');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200 + 300);
            });

            // Efecto de partículas mejorado
            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                const delay = Math.random() * 2;
                particle.style.animationDelay = `-${delay}s`;
                
                // Posición aleatoria
                particle.style.top = Math.random() * 100 + '%';
                particle.style.left = Math.random() * 100 + '%';
            });
        });

        // Prevenir ataques de fuerza bruta básicos
        let loginAttempts = 0;
        const maxAttempts = 3;
        const lockoutTime = 30000; // 30 segundos

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (loginAttempts >= maxAttempts) {
                e.preventDefault();
                alert('Demasiados intentos fallidos. Espere 30 segundos antes de intentar nuevamente.');
                return false;
            }
        });

        // Incrementar intentos en caso de error
        <?php if (isset($auth_error)): ?>
        loginAttempts++;
        if (loginAttempts >= maxAttempts) {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-clock me-2"></i>Bloqueado temporalmente';
            
            setTimeout(() => {
                loginAttempts = 0;
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión';
            }, lockoutTime);
        }
        <?php endif; ?>

        // Limpiar formulario en caso de éxito
        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']): ?>
        // Redirigir automáticamente si ya está autenticado
        setTimeout(() => {
            window.location.href = 'admin.php';
        }, 1000);
        <?php endif; ?>

        console.log('🔐 Formulario de login cargado correctamente');
    </script>
</body>
</html>