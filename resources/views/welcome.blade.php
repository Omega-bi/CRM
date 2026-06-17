<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Добро пожаловать</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f0f4f8;
            color: #1f2937;
        }
        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 720px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            padding: 36px;
        }
        h1 {
            margin: 0 0 16px;
            font-size: 2.5rem;
            line-height: 1.1;
        }
        p {
            margin: 0 0 24px;
            font-size: 1rem;
            line-height: 1.75;
            color: #475569;
        }
        .features {
            display: grid;
            gap: 16px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .features li {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.95rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <section class="page">
        <div class="card">
            <h1>Добро пожаловать в SentinelCRM</h1>
            <p>Эта страница поможет вам начать работу с вашей системой CRM. Управляйте контактами, задачами и продажами в одном месте.</p>
            <ul class="features">
                <li>Быстрый старт: добавляйте клиентов и создавайте сделки.</li>
                <li>Простое управление задачами и напоминаниями.</li>
                <li>Анализ продаж и мониторинг эффективности.</li>
            </ul>
            <div class="footer">Начните с добавления первой записи или перейдите в панель управления.</div>
        </div>
    </section>
</body>
</html>