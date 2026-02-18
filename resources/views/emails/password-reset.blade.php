<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Rugby Key Performance</title>
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
            background: linear-gradient(135deg, #005461, #003d4a);
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
            color: #005461;
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
            background: linear-gradient(135deg, #005461, #003d4a);
            color: white !important;
            text-decoration: none !important;
            border-radius: 50px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 84, 97, 0.3);
            transition: all 0.3s ease;
            border: none;
        }
        .reset-button:hover {
            background: linear-gradient(135deg, #003d4a, #005461);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 84, 97, 0.4);
            color: white !important;
            text-decoration: none !important;
        }
        .reset-button:visited {
            color: white !important;
            text-decoration: none !important;
        }
        .reset-button:active {
            color: white !important;
            text-decoration: none !important;
        }
        .security-note {
            background-color: #f8f9fa;
            border-left: 4px solid #005461;
            padding: 15px 20px;
            margin: 30px 0;
            border-radius: 0 5px 5px 0;
        }
        .security-note h4 {
            color: #005461;
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
            color: #005461;
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
            <div style="width: 80px; height: 80px; margin: 0 auto 15px; background-color: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 48px; color: white;">🏉</span>
            </div>
            <h1>RUGBYHUB</h1>
            <p>Sistema de Análisis de Video</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2>🔐 Recuperar Contraseña</h2>

            <p>¡Hola!</p>

            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Rugby Key Performance</strong>.</p>

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

            <p><strong>Saludos,<br>El equipo de Rugby Key Performance</strong></p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>Rugby Key Performance</strong></p>
            <p>Sistema de Análisis de Video</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
            <p>© {{ date('Y') }} Rugby Key Performance. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>