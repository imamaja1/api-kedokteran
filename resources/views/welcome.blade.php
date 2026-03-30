<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>API SISKA Kedokteran - Sistem Informasi Akademik Kedokteran</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1135d6 0%, #4927ba 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .logo svg {
            width: 60px;
            height: 60px;
            fill: #fff;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem 1rem;
            border-radius: 12px;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }

        .info-item .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .info-item .label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }

        .info-item .value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #fff;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        footer {
            margin-top: 3rem;
            opacity: 0.7;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path
                    d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 2.18l8 3.6v8.72c0 4.55-3.14 8.8-8 9.95-4.86-1.15-8-5.4-8-9.95V7.78l8-3.6zM11 10v6h2v-6h-2zm0-4v2h2V6h-2z" />
            </svg>
        </div>

        <h1>API SISKA Kedokteran</h1>
        <p class="subtitle">Sistem Informasi Akademik Kedokteran</p>

        <footer>
            &copy; {{ date('Y') }} API SISKA Kedokteran. All rights reserved.
        </footer>
    </div>
</body>

</html>