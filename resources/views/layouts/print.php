<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title ?? 'Print') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= church_asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= church_asset('css/receipt.css') ?>" rel="stylesheet">
    <style>
        body { padding: 1.5rem; background: #f0f0f0; }
        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .receipt-slip { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>
    <?= $content ?>
</body>
</html>
