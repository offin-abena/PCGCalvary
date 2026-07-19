<?php

use App\Core\Csrf;

function church_url(string $path = ''): string
{
    return $path === '' ? url('/') : url(ltrim($path, '/'));
}

function church_asset(string $path): string
{
    return church_url('assets/' . ltrim($path, '/'));
}

function church_logo(): string
{
    $config = config('church');
    $candidates = array_filter([
        $config['church_logo'] ?? null,
        'images/pcg logo.jpeg',
        'images/pcg-logo.jpeg',
        'images/pcg-logo.png',
    ]);

    foreach ($candidates as $path) {
        $fullPath = public_path('assets/' . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/')));
        if (file_exists($fullPath)) {
            $dir = dirname($path);
            $file = rawurlencode(basename($path));

            return church_asset(($dir !== '.' ? $dir . '/' : '') . $file);
        }
    }

    return church_asset('images/pcg%20logo.jpeg');
}

function upload_url(string $path): string
{
    return church_url('files/serve?f=' . rawurlencode(ltrim($path, '/')));
}

function church_csrf_token(): string
{
    return Csrf::token();
}

function church_csrf_field(): string
{
    return Csrf::field();
}

function safe_url(?string $url): string
{
    if (!$url) {
        return '';
    }
    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);

    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (!$date) {
        return '-';
    }

    return date($format, strtotime($date));
}

function format_money(float $amount): string
{
    $config = config('church');

    return $config['currency_symbol'] . ' ' . number_format($amount, 2);
}

function allowed_per_page_options(): array
{
    return [50, 100, 150, 250];
}

function resolve_per_page(): int
{
    $perPage = (int) (request()->query('per_page', 50));

    return in_array($perPage, allowed_per_page_options(), true) ? $perPage : 50;
}

function transaction_filter_query_string(array $dateFilter, ?int $perPage = null): string
{
    return http_build_query(array_filter([
        'filter_type' => $dateFilter['filter_type'] ?? '',
        'filter_date' => $dateFilter['filter_date'] ?? '',
        'filter_month' => $dateFilter['filter_month'] ?? '',
        'per_page' => $perPage ?? resolve_per_page(),
    ]));
}

function resolve_report_financial_year(): ?array
{
    $fyModel = new \App\Models\FinancialYear();
    $fyId = (int) request()->query('financial_year_id', 0);

    if ($fyId > 0) {
        $year = $fyModel->find($fyId);
        if ($year) {
            return $year;
        }
    }

    return $fyModel->getCurrent();
}

function resolve_transaction_date_filter(): array
{
    $filterType = request()->query('filter_type', '');
    $filterDate = request()->query('filter_date', '');
    $filterMonth = request()->query('filter_month', '');

    $startDate = null;
    $endDate = null;
    $label = 'All records';

    if ($filterType === 'day' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
        $startDate = $endDate = $filterDate;
        $label = format_date($filterDate, 'l, F j, Y');
    } elseif ($filterType === 'month' && preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
        $startDate = $filterMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $label = date('F Y', strtotime($startDate));
    }

    return [
        'filter_type' => $filterType,
        'filter_date' => $filterDate,
        'filter_month' => $filterMonth,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'label' => $label,
        'active' => $startDate !== null && $endDate !== null,
    ];
}

function format_payment_method(?string $method): string
{
    $labels = [
        'cash' => 'Cash',
        'check' => 'Cheque',
        'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Bank Transfer',
        'online' => 'Online',
        'other' => 'Other',
    ];

    return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method ?? ''));
}

function flash_message(): ?array
{
    $flash = session('flash');
    session()->forget('flash');

    return $flash;
}

function status_badge(string $status): string
{
    $colors = [
        'active' => 'success', 'inactive' => 'secondary', 'pending' => 'warning',
        'completed' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary',
        'published' => 'primary', 'visitor' => 'info', 'new_convert' => 'warning',
        'paid' => 'success', 'registered' => 'info', 'attended' => 'success',
        'praying' => 'primary', 'answered' => 'success', 'scheduled' => 'info',
        'fulfilled' => 'success', 'approved' => 'success', 'rejected' => 'danger',
    ];

    $color = $colors[$status] ?? 'secondary';

    return '<span class="badge bg-' . $color . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

function generate_member_code(): string
{
    return 'MEM' . date('Y') . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

function generate_qr_code(): string
{
    return 'QR' . strtoupper(bin2hex(random_bytes(8)));
}

function generate_receipt_number(string $typeLabel = 'General', ?string $transactionDate = null): string
{
    $fyModel = new \App\Models\FinancialYear();
    $currentFy = $fyModel->getCurrent();
    $transactionDate = $transactionDate ?? date('Y-m-d');

    if ($currentFy) {
        return $fyModel->generateTransactionNumber(
            (int) $currentFy['id'],
            'income',
            $typeLabel,
            $transactionDate
        );
    }

    $typeCode = \App\Models\FinancialYear::deriveTypeCode($typeLabel);
    $yy = date('y', strtotime($transactionDate));
    $mm = date('m', strtotime($transactionDate));

    return sprintf('INC_%s_%s_%s_%04d', $typeCode, $yy, $mm, random_int(1, 9999));
}

function amount_in_words(float $amount): string
{
    $config = config('church');
    $whole = (int) floor($amount);
    $cents = (int) round(($amount - $whole) * 100);

    $words = number_to_words($whole);
    $currencyNames = [
        'GHS' => 'Ghana Cedis',
        'KES' => 'Kenya Shillings',
    ];
    $currency = $currencyNames[$config['currency']] ?? $config['currency'];
    $subunit = $config['currency'] === 'GHS' ? 'Pesewas' : 'Cents';

    $result = $words . ' ' . $currency;
    if ($cents > 0) {
        $result .= ' and ' . number_to_words($cents) . ' ' . $subunit;
    }
    $result .= ' Only';

    return $result;
}

function number_to_words(int $n): string
{
    if ($n === 0) {
        return 'Zero';
    }

    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    if ($n < 20) {
        return $ones[$n];
    }
    if ($n < 100) {
        return $tens[(int) floor($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
    }
    if ($n < 1000) {
        return $ones[(int) floor($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . number_to_words($n % 100) : '');
    }
    if ($n < 1000000) {
        return number_to_words((int) floor($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . number_to_words($n % 1000) : '');
    }
    if ($n < 1000000000) {
        return number_to_words((int) floor($n / 1000000)) . ' Million' . ($n % 1000000 ? ' ' . number_to_words($n % 1000000) : '');
    }

    return number_to_words((int) floor($n / 1000000000)) . ' Billion' . ($n % 1000000000 ? ' ' . number_to_words($n % 1000000000) : '');
}

function current_path(): string
{
    return trim(request()->path(), '/');
}

function app_base_url(): string
{
    return rtrim(config('app.url'), '/');
}
