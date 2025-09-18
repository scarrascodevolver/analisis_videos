<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Los Troncos Rugby Club</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #1e4d2b, #2d5a3a);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .email-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            padding: 10px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .email-header p {
            margin: 5px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .email-body {
            padding: 40px 30px;
            color: #333333;
        }
        .email-body h2 {
            color: #1e4d2b;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        .email-body p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555555;
        }
        .reset-button {
            display: block;
            width: 280px;
            margin: 30px auto;
            padding: 15px 30px;
            background: linear-gradient(135deg, #1e4d2b, #2d5a3a);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(30, 77, 43, 0.3);
            transition: all 0.3s ease;
        }
        .reset-button:hover {
            background: linear-gradient(135deg, #2d5a3a, #1e4d2b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 77, 43, 0.4);
        }
        .security-note {
            background-color: #f8f9fa;
            border-left: 4px solid #1e4d2b;
            padding: 15px 20px;
            margin: 30px 0;
            border-radius: 0 5px 5px 0;
        }
        .security-note h4 {
            color: #1e4d2b;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .security-note p {
            margin: 0;
            font-size: 14px;
            color: #666666;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .email-footer a {
            color: #1e4d2b;
            text-decoration: none;
        }
        .alternative-link {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
            color: #666666;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <img src="{{ asset('logo_lt.png') }}" alt="Los Troncos Logo">
            <h1>LOS TRONCOS</h1>
            <p>Rugby Club</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2>🔐 Recuperar Contraseña</h2>

            <p>¡Hola!</p>

            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el <strong>Sistema de Análisis de Video Rugby Los Troncos</strong>.</p>

            <p>Si solicitaste este cambio, haz clic en el botón de abajo para crear una nueva contraseña:</p>

            <a href="{{ $actionUrl }}" class="reset-button">
                🔑 Restablecer Contraseña
            </a>

            <div class="security-note">
                <h4>🛡️ Nota de Seguridad</h4>
                <p>Este enlace expirará en <strong>60 minutos</strong> por tu seguridad. Si no solicitaste este cambio, puedes ignorar este email de forma segura.</p>
            </div>

            <p>Si tienes problemas haciendo clic en el botón, copia y pega el siguiente enlace en tu navegador:</p>

            <div class="alternative-link">
                {{ $actionUrl }}
            </div>

            <p>Si no solicitaste este restablecimiento de contraseña, no es necesario que hagas nada. Tu contraseña actual seguirá siendo válida.</p>

            <p><strong>Saludos,<br>El equipo de Los Troncos Rugby Club</strong></p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>Los Troncos Rugby Club</strong></p>
            <p>Sistema de Análisis de Video Rugby</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
            <p>© {{ date('Y') }} Los Troncos Rugby Club. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>