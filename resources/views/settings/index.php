<div class="page-header"><h1>Settings</h1></div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general">General</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#change-password" id="change-password-tab"><i class="bi bi-key"></i> Change Password</button></li>
    <?php if ($canManageFinancialYear): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#financial-years" id="financial-years-tab"><i class="bi bi-calendar-range"></i> Financial Years</button></li>
    <?php endif; ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#income-types"><i class="bi bi-arrow-down-circle text-success"></i> Income Types</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#expense-types"><i class="bi bi-arrow-up-circle text-danger"></i> Expense Types</button></li>
</ul>

<div class="tab-content">
    <!-- General Settings -->
    <div class="tab-pane fade show active" id="general">
        <div class="row g-4">
            <div class="col-lg-6"><div class="card"><div class="card-header">Church Information</div><div class="card-body">
                <p><strong>Name:</strong> <?= e($config['church_name']) ?></p>
                <p><strong>Address:</strong> <?= e($config['church_address']) ?></p>
                <p><strong>Phone:</strong> <?= e($config['church_phone']) ?></p>
                <p><strong>Email:</strong> <?= e($config['church_email']) ?></p>
                <p class="text-muted small">Edit <code>config/app.php</code> to update church details.</p>
            </div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-header">System Info</div><div class="card-body">
                <p><strong>Version:</strong> <?= e($config['version']) ?></p>
                <p><strong>Currency:</strong> <?= e($config['currency']) ?> (<?= e($config['currency_symbol']) ?>)</p>
                <p><strong>Timezone:</strong> <?= e($config['timezone']) ?></p>
                <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
            </div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-header"><i class="bi bi-phone"></i> SMS Integration</div><div class="card-body">
                <p><strong>Provider:</strong> <?= e(ucfirst($smsConfig['provider'])) ?></p>
                <p><strong>Status:</strong> <?= $smsConfig['enabled'] ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></p>
                <p><strong>Auto-send tithe receipt:</strong> <?= $smsConfig['auto_send_tithe_receipt'] ? 'Yes' : 'No' ?></p>
                <p class="text-muted small mt-2">Configure SMS credentials in <code>.env</code> (see <code>.env.example</code>).</p>
                <a href="<?= url('finance/tithe-reminders') ?>" class="btn btn-sm btn-outline-primary">Manage Tithe SMS</a>
            </div></div></div>
        </div>
    </div>

    <?php if ($canManageFinancialYear): ?>
    <!-- Financial Years -->
    <div class="tab-pane fade" id="financial-years">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle"></i> Create Financial Year</div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('settings/financial-years/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="Financial Year 2026" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year Label *</label>
                                <input type="number" name="year_label" class="form-control" min="2000" max="2099" value="<?= date('Y') ?>" required>
                                <small class="text-muted">Used in receipt/voucher numbers (e.g. INC_TH_26_06_0003)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y') ?>-01-01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Date *</label>
                                <input type="date" name="end_date" class="form-control" value="<?= date('Y') ?>-12-31" required>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="set_current" value="1" class="form-check-input" id="setCurrentFy" checked>
                                <label class="form-check-label" for="setCurrentFy">Set as current financial year</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Financial Year</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Financial Years</span>
                        <?php if (!empty($currentFinancialYear)): ?>
                        <span class="badge bg-success">Current: <?= e($currentFinancialYear['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Year</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Sequences</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($financialYears)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No financial years configured</td></tr>
                                <?php else: foreach ($financialYears as $fy): ?>
                                <tr class="<?= $fy['is_current'] ? 'table-primary' : '' ?>">
                                    <td>
                                        <?= e($fy['name']) ?>
                                        <?php if ($fy['is_current']): ?><span class="badge bg-primary ms-1">Current</span><?php endif; ?>
                                    </td>
                                    <td><?= e($fy['year_label']) ?></td>
                                    <td><?= format_date($fy['start_date']) ?> — <?= format_date($fy['end_date']) ?></td>
                                    <td>
                                        <?= $fy['status'] === 'active'
                                            ? '<span class="badge bg-success">Active</span>'
                                            : '<span class="badge bg-secondary">Closed</span>' ?>
                                    </td>
                                    <td class="small text-muted">
                                        INC: <?= (int) $fy['last_income_seq'] ?> · EXP: <?= (int) $fy['last_expense_seq'] ?> · AST: <?= (int) ($fy['last_asset_seq'] ?? 0) ?> · LIA: <?= (int) ($fy['last_liability_seq'] ?? 0) ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($fy['status'] === 'active' && !$fy['is_current']): ?>
                                        <form method="POST" action="<?= url('settings/financial-years/' . $fy['id'] . '/set-current') ?>" class="d-inline">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-primary" title="Set as current"><i class="bi bi-check-circle"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($fy['status'] === 'active'): ?>
                                        <form method="POST" action="<?= url('settings/financial-years/' . $fy['id'] . '/close') ?>" class="d-inline" onsubmit="return confirm('Close this financial year? New income/expense cannot be recorded against it.');">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-warning" title="Close year"><i class="bi bi-lock"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer small text-muted">
                        Closing a financial year prevents new income, expense, asset, and liability entries for that period. Income and expense reference numbers use type, year, and month (e.g. INC_TH_26_06_0003, EXP_UT_26_06_0001). Asset and liability numbers use the year label (e.g. AST_2026_0001, LIA_2026_0001).
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Income Types -->
    <div class="tab-pane fade" id="income-types">
        <?php if (!empty($duplicateIncomeTypeNames)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Duplicate income type names found. Please rename or deactivate the highlighted entries so each name is unique.
        </div>
        <?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-success"><i class="bi bi-plus-circle"></i> Add Income Type</div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('settings/income-types/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Tithe, Offering" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Add Income Type</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Income Types</span>
                        <span class="badge bg-success"><?= count(array_filter($incomeTypes, fn($t) => $t['is_active'])) ?> active</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <?php if (empty($incomeTypes)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No income types configured</td></tr>
                                <?php else: foreach ($incomeTypes as $type):
                                    $isDuplicateIncome = in_array(strtolower(trim($type['name'])), $duplicateIncomeTypeNames ?? [], true);
                                ?>
                                <tr class="<?= !$type['is_active'] ? 'table-secondary' : ($isDuplicateIncome ? 'table-warning' : '') ?>">
                                    <td>
                                        <input type="text" name="name" form="income-form-<?= $type['id'] ?>" class="form-control form-control-sm <?= $isDuplicateIncome ? 'border-warning' : '' ?>" value="<?= e($type['name']) ?>" required>
                                        <?php if ($isDuplicateIncome): ?><small class="text-warning">Duplicate name</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="description" form="income-form-<?= $type['id'] ?>" class="form-control form-control-sm" value="<?= e($type['description'] ?? '') ?>" placeholder="Description">
                                    </td>
                                    <td>
                                        <?= $type['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="submit" form="income-form-<?= $type['id'] ?>" class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check"></i></button>
                                        <form method="POST" action="<?= url('settings/income-types/' . $type['id'] . '/toggle') ?>" class="d-inline">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-<?= $type['is_active'] ? 'warning' : 'success' ?>" title="<?= $type['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                <i class="bi bi-<?= $type['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php foreach ($incomeTypes as $type): ?>
                <form id="income-form-<?= $type['id'] ?>" method="POST" action="<?= url('settings/income-types/' . $type['id'] . '/update') ?>" class="d-none"></form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Expense Types -->
    <div class="tab-pane fade" id="expense-types">
        <?php if (!empty($duplicateExpenseTypeNames)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Duplicate expense type names found. Please rename or deactivate the highlighted entries so each name is unique.
        </div>
        <?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-danger"><i class="bi bi-plus-circle"></i> Add Expense Type</div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('settings/expense-types/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Utilities, Salaries" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Add Expense Type</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Expense Types</span>
                        <span class="badge bg-danger"><?= count(array_filter($expenseTypes, fn($t) => $t['is_active'])) ?> active</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <?php if (empty($expenseTypes)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No expense types configured</td></tr>
                                <?php else: foreach ($expenseTypes as $type):
                                    $isDuplicateExpense = in_array(strtolower(trim($type['name'])), $duplicateExpenseTypeNames ?? [], true);
                                ?>
                                <tr class="<?= !$type['is_active'] ? 'table-secondary' : ($isDuplicateExpense ? 'table-warning' : '') ?>">
                                    <td>
                                        <input type="text" name="name" form="expense-form-<?= $type['id'] ?>" class="form-control form-control-sm <?= $isDuplicateExpense ? 'border-warning' : '' ?>" value="<?= e($type['name']) ?>" required>
                                        <?php if ($isDuplicateExpense): ?><small class="text-warning">Duplicate name</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="description" form="expense-form-<?= $type['id'] ?>" class="form-control form-control-sm" value="<?= e($type['description'] ?? '') ?>" placeholder="Description">
                                    </td>
                                    <td>
                                        <?= $type['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="submit" form="expense-form-<?= $type['id'] ?>" class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check"></i></button>
                                        <form method="POST" action="<?= url('settings/expense-types/' . $type['id'] . '/toggle') ?>" class="d-inline">
            <?= church_csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-<?= $type['is_active'] ? 'warning' : 'success' ?>" title="<?= $type['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                <i class="bi bi-<?= $type['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php foreach ($expenseTypes as $type): ?>
                <form id="expense-form-<?= $type['id'] ?>" method="POST" action="<?= url('settings/expense-types/' . $type['id'] . '/update') ?>" class="d-none"></form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="tab-pane fade" id="change-password">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-key"></i> Change Password</div>
                    <div class="card-body">
                        <p class="text-muted small">After changing your password you will be signed out and must log in again with your new password.</p>
                        <form method="POST" action="<?= url('settings/change-password') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const hash = window.location.hash;
    const tabIds = {
        '#change-password': 'change-password-tab',
        '#financial-years': 'financial-years-tab',
    };
    const tabId = tabIds[hash];
    if (tabId) {
        const tab = document.getElementById(tabId);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    }
})();
</script>
