<link href="<?= church_asset('css/dashboard.css') ?>" rel="stylesheet">

<!-- Hero -->
<div class="admin-hero">
    <div class="row align-items-center">
        <div class="col-lg-7 d-flex align-items-center gap-3">
            <img src="<?= church_logo() ?>" alt="Presbyterian Church of Ghana Logo" class="church-logo church-logo-hero d-none d-md-block">
            <div>
                <h1>Welcome back, <?= e($user['username'] ?? 'Admin') ?>!</h1>
                <p class="subtitle mb-0">
                    <?= e($config['church_name']) ?> &mdash;
                    <?= date('l, F j, Y') ?>
                </p>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="d-flex gap-3 justify-content-lg-end mt-3 mt-lg-0 flex-wrap">
                <div class="hero-stat">
                    <div class="val"><?= number_format($attendance['today']) ?></div>
                    <div class="lbl">Today</div>
                </div>
                <div class="hero-stat">
                    <div class="val"><?= number_format($attendance['last_sunday']) ?></div>
                    <div class="lbl">Last Sunday</div>
                </div>
                <div class="hero-stat">
                    <div class="val"><?= number_format($operations['events_upcoming']) ?></div>
                    <div class="lbl">Events</div>
                </div>
                <div class="hero-stat">
                    <div class="val"><?= $user['role_name'] ?? 'Admin' ?></div>
                    <div class="lbl">Role</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($alerts)): ?>
<div class="alert-strip">
    <?php foreach ($alerts as $alert): ?>
    <a href="<?= url($alert['link']) ?>" class="alert-chip <?= e($alert['type']) ?>">
        <i class="bi bi-<?= e($alert['icon']) ?>"></i>
        <?= e($alert['message']) ?>
        <i class="bi bi-chevron-right small"></i>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <a href="<?= url('members/children-service') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-balloon-heart"></i></div>
        <div class="kpi-value"><?= number_format($members['children_service']) ?></div>
        <div class="kpi-label">Children Service</div>
        <div class="kpi-trend text-muted">Age 12 and below</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('members/junior-youth') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-emoji-smile"></i></div>
        <div class="kpi-value"><?= number_format($members['junior_youth']) ?></div>
        <div class="kpi-label">Junior Youth</div>
        <div class="kpi-trend text-muted">Age 13–18</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('members/adults') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person"></i></div>
        <div class="kpi-value"><?= number_format($members['adults']) ?></div>
        <div class="kpi-label">Adults</div>
        <div class="kpi-trend text-muted">Above 18 years</div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <a href="<?= url('members/adults') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
        <div class="kpi-value"><?= number_format($members['total']) ?></div>
        <div class="kpi-label">Total Members</div>
        <div class="kpi-trend text-success"><i class="bi bi-arrow-up"></i> +<?= $members['new_this_month'] ?> this month</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('members/adults?status=active') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-check"></i></div>
        <div class="kpi-value"><?= number_format($members['active']) ?></div>
        <div class="kpi-label">Active Members</div>
        <div class="kpi-trend text-muted"><?= $members['families'] ?> generational groups</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('finance') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-value"><?= format_money($finance['total_income']) ?></div>
        <div class="kpi-label">Income (Month)</div>
        <div class="kpi-trend text-muted">Tithe: <?= format_money($finance['tithe_month']) ?></div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('finance') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-wallet2"></i></div>
        <div class="kpi-value"><?= format_money($finance['total_expense']) ?></div>
        <div class="kpi-label">Expenditure (Month)</div>
        <div class="kpi-trend text-muted">Balance: <?= format_money($finance['balance']) ?></div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('attendance/adults') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
        <div class="kpi-value"><?= number_format($attendance['week']) ?></div>
        <div class="kpi-label">Attendance (Week)</div>
        <div class="kpi-trend text-muted"><?= $attendance['sessions_month'] ?> sessions this month</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('visitors') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-person-plus"></i></div>
        <div class="kpi-value"><?= number_format($operations['pending_visitors']) ?></div>
        <div class="kpi-label">Visitors Pending</div>
        <div class="kpi-trend text-muted"><?= $members['new_converts'] ?> new converts</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('finance/tithe-reminders') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-bell"></i></div>
        <div class="kpi-value"><?= number_format($finance['tithe_pending']) ?></div>
        <div class="kpi-label">Tithe Pending</div>
        <div class="kpi-trend text-warning">No tithe this month</div>
    </div>
    <div class="kpi-card">
        <a href="<?= url('ministries') ?>" class="kpi-link"><i class="bi bi-arrow-right"></i></a>
        <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-diagram-3"></i></div>
        <div class="kpi-value"><?= number_format($operations['ministries']) ?></div>
        <div class="kpi-label">Active Ministries</div>
        <div class="kpi-trend text-muted"><?= $operations['active_pledges'] ?> active pledges</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="chart-card mb-4">
    <div class="chart-header">Quick Actions</div>
    <div class="chart-body py-3">
        <div class="quick-actions">
            <a href="<?= url('members/create') ?>" class="quick-action-btn"><i class="bi bi-person-plus"></i><span>Add Member</span></a>
            <a href="<?= url('finance/income') ?>" class="quick-action-btn"><i class="bi bi-cash"></i><span>Record Tithe</span></a>
            <a href="<?= url('attendance/create') ?>" class="quick-action-btn"><i class="bi bi-calendar-check"></i><span>New Session</span></a>
            <a href="<?= url('attendance/scan') ?>" class="quick-action-btn"><i class="bi bi-qr-code-scan"></i><span>QR Check-in</span></a>
            <a href="<?= url('visitors/create') ?>" class="quick-action-btn"><i class="bi bi-person-badge"></i><span>Register Visitor</span></a>
            <a href="<?= url('events/create') ?>" class="quick-action-btn"><i class="bi bi-calendar-event"></i><span>Create Event</span></a>
            <a href="<?= url('communication/messages') ?>" class="quick-action-btn"><i class="bi bi-send"></i><span>Send SMS</span></a>
            <a href="<?= url('reports') ?>" class="quick-action-btn"><i class="bi bi-bar-chart"></i><span>View Reports</span></a>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="bi bi-graph-up"></i> Financial Overview</span>
                <small class="text-muted">Last 6 months</small>
            </div>
            <div class="chart-body">
                <canvas id="financeChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="bi bi-pie-chart"></i> Income Breakdown</span>
                <small class="text-muted">This month</small>
            </div>
            <div class="chart-body d-flex align-items-center justify-content-center">
                <canvas id="incomeChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="bi bi-people"></i> Member Growth</span>
                <small class="text-muted">Last 6 months</small>
            </div>
            <div class="chart-body">
                <canvas id="memberChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="bi bi-bar-chart"></i> Attendance by Service</span>
                <small class="text-muted">This month</small>
            </div>
            <div class="chart-body">
                <canvas id="attendanceChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="chart-card">
            <div class="chart-header">
                <span>Recent Transactions</span>
                <a href="<?= url('finance') ?>" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead><tr><th>Date</th><th>Type</th><th>Member</th><th>Amount</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($recent['transactions'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet</td></tr>
                        <?php else: foreach ($recent['transactions'] as $t): ?>
                        <tr>
                            <td><?= format_date($t['transaction_date']) ?></td>
                            <td>
                                <span class="badge bg-<?= $t['transaction_type'] === 'income' ? 'success' : 'danger' ?>"><?= e($t['type_name']) ?></span>
                            </td>
                            <td><?= e($t['member_name'] ?? '—') ?></td>
                            <td class="fw-semibold <?= $t['transaction_type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                <?= format_money($t['amount']) ?>
                            </td>
                            <td>
                                <?php if ($t['transaction_type'] === 'income'): ?>
                                <a href="<?= url('finance/receipt/'.$t['id']) ?>" class="btn btn-sm btn-outline-secondary py-0">Receipt</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card">
            <div class="chart-header">
                <span>System Activity</span>
                <a href="<?= url('audit-logs') ?>" class="small text-decoration-none">Audit Logs</a>
            </div>
            <div class="px-3 py-2" style="max-height: 340px; overflow-y: auto;">
                <?php if (empty($recent['audit'])): ?>
                    <p class="text-muted text-center py-4">No recent activity</p>
                <?php else: foreach ($recent['audit'] as $log): ?>
                <div class="activity-item">
                    <div class="activity-dot system"></div>
                    <div class="flex-grow-1">
                        <div class="small"><strong><?= e($log['username'] ?? 'System') ?></strong> <?= e($log['action']) ?> <span class="text-muted"><?= e($log['module'] ?? '') ?></span></div>
                        <small class="text-muted"><?= format_date($log['created_at'], 'M d, h:i A') ?></small>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Widgets -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header"><i class="bi bi-calendar-event"></i> Upcoming Events</div>
            <div class="widget-list">
                <?php if (empty($lists['events'])): ?>
                    <div class="list-item text-muted justify-content-center">No upcoming events</div>
                <?php else: foreach ($lists['events'] as $event): ?>
                <a href="<?= url('events/'.$event['id']) ?>" class="list-item">
                    <i class="bi bi-calendar text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= e($event['title']) ?></div>
                        <small class="text-muted"><?= format_date($event['start_date'], 'M d, h:i A') ?></small>
                    </div>
                    <span class="badge bg-info"><?= $event['registration_count'] ?></span>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header"><i class="bi bi-gift"></i> Birthdays This Week</div>
            <div class="widget-list">
                <?php if (empty($lists['birthdays'])): ?>
                    <div class="list-item text-muted justify-content-center">No birthdays this week</div>
                <?php else: foreach ($lists['birthdays'] as $b): ?>
                <div class="list-item">
                    <div class="avatar-placeholder"><?= strtoupper(substr($b['first_name'], 0, 1)) ?></div>
                    <div>
                        <div class="fw-semibold small"><?= e($b['first_name'].' '.$b['last_name']) ?></div>
                        <small class="text-muted"><?= format_date($b['date_of_birth'], 'M d') ?></small>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header"><i class="bi bi-diagram-3"></i> Top Ministries</div>
            <div class="chart-body">
                <?php if (empty($lists['ministries'])): ?>
                    <p class="text-muted text-center">No ministries yet</p>
                <?php else:
                    $memberCounts = array_column($lists['ministries'], 'members');
                    $maxMinistry = !empty($memberCounts) ? max($memberCounts) : 1;
                    foreach ($lists['ministries'] as $mn): ?>
                <div class="ministry-bar">
                    <div class="bar-header">
                        <span><?= e($mn['name']) ?></span>
                        <span class="text-muted"><?= $mn['members'] ?> members</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary" style="width: <?= $maxMinistry > 0 ? ($mn['members'] / $maxMinistry) * 100 : 0 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Attendance -->
<div class="chart-card mt-4">
    <div class="chart-header">
        <span>Recent Attendance Sessions</span>
        <a href="<?= url('attendance/adults') ?>" class="small text-decoration-none">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr><th>Date</th><th>Service</th><th>Title</th><th>Attendees</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($recent['sessions'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No sessions recorded</td></tr>
                <?php else: foreach ($recent['sessions'] as $s): ?>
                <tr>
                    <td><?= format_date($s['session_date']) ?></td>
                    <td><?= e($s['service_name']) ?></td>
                    <td><?= e($s['title'] ?? '—') ?></td>
                    <td><span class="badge bg-primary"><?= $s['attendee_count'] ?></span></td>
                    <td><a href="<?= url('attendance/'.$s['id']) ?>" class="btn btn-sm btn-outline-primary py-0">Manage</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Prayer Requests & Operations Summary -->
<div class="row g-4 mt-4">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header"><i class="bi bi-heart"></i> Prayer Requests</div>
            <div class="widget-list">
                <?php if (empty($lists['prayers'])): ?>
                    <div class="list-item text-muted justify-content-center">No pending requests</div>
                <?php else: foreach ($lists['prayers'] as $p): ?>
                <div class="list-item">
                    <i class="bi bi-chat-heart text-danger"></i>
                    <div class="flex-grow-1">
                        <div class="small"><?= e(strlen($p['request_text']) > 60 ? substr($p['request_text'], 0, 60) . '...' : $p['request_text']) ?></div>
                        <small class="text-muted"><?= e($p['member_name'] ?? $p['requester_name'] ?? 'Anonymous') ?></small>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-header">Operations Summary</div>
            <div class="chart-body py-2">
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">YTD Income</span><strong class="text-success"><?= format_money($finance['income_ytd']) ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">YTD Expenditure</span><strong class="text-danger"><?= format_money($finance['expense_ytd']) ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">SMS Sent (Month)</span><strong><?= $operations['sms_month'] ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Active Users</span><strong><?= $operations['users'] ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Assets</span><strong><?= $operations['assets'] ?></strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">Maintenance Due</span><strong class="<?= $operations['maintenance_due'] > 0 ? 'text-warning' : '' ?>"><?= $operations['maintenance_due'] ?></strong></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colors = { primary: '#1e3a5f', accent: '#c9a227', success: '#10b981', danger: '#ef4444', info: '#3b82f6' };
    const chartDefaults = { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { font: { size: 11 }, boxWidth: 12 } } } };

    // Financial chart
    const financeData = <?= json_encode($charts['monthly_finance']) ?>;
    if (document.getElementById('financeChart') && financeData.length) {
        new Chart(document.getElementById('financeChart'), {
            type: 'bar',
            data: {
                labels: financeData.map(d => d.label),
                datasets: [
                    { label: 'Income', data: financeData.map(d => parseFloat(d.income)), backgroundColor: colors.success + '99', borderRadius: 4 },
                    { label: 'Expenditure', data: financeData.map(d => parseFloat(d.expense)), backgroundColor: colors.danger + '99', borderRadius: 4 }
                ]
            },
            options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { callback: v => '<?= e($config['currency_symbol']) ?> ' + v.toLocaleString() } } } }
        });
    }

    // Income pie chart
    const incomeData = <?= json_encode($charts['income_by_type']) ?>.filter(d => parseFloat(d.total) > 0);
    const pieColors = [colors.primary, colors.accent, colors.success, colors.info, colors.danger, '#8b5cf6', '#ec4899'];
    if (document.getElementById('incomeChart') && incomeData.length) {
        new Chart(document.getElementById('incomeChart'), {
            type: 'doughnut',
            data: {
                labels: incomeData.map(d => d.name),
                datasets: [{ data: incomeData.map(d => parseFloat(d.total)), backgroundColor: pieColors, borderWidth: 0 }]
            },
            options: { ...chartDefaults, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Member growth
    const memberData = <?= json_encode($charts['member_growth']) ?>;
    if (document.getElementById('memberChart') && memberData.length) {
        new Chart(document.getElementById('memberChart'), {
            type: 'line',
            data: {
                labels: memberData.map(d => d.label),
                datasets: [{
                    label: 'New Members', data: memberData.map(d => parseInt(d.count)),
                    borderColor: colors.primary, backgroundColor: colors.primary + '20',
                    fill: true, tension: 0.3, pointRadius: 4
                }]
            },
            options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }

    // Attendance by service
    const attData = <?= json_encode($charts['attendance_by_service']) ?>;
    if (document.getElementById('attendanceChart') && attData.length) {
        new Chart(document.getElementById('attendanceChart'), {
            type: 'bar',
            data: {
                labels: attData.map(d => d.name),
                datasets: [{ label: 'Attendees', data: attData.map(d => parseInt(d.attendees)), backgroundColor: colors.info + '99', borderRadius: 4 }]
            },
            options: { ...chartDefaults, indexAxis: 'y', scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
    }
});
</script>
