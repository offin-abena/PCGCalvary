<div class="page-header"><h1>Attendance Report</h1></div>
<form method="GET" class="search-bar row g-2"><div class="col-auto"><input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>"></div><div class="col-auto"><input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>"></div><div class="col-auto"><button class="btn btn-primary">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Service</th><th>Unique Attendees</th><th>Total Check-ins</th></tr></thead>
    <tbody><?php foreach ($analytics as $a): ?><tr><td><?= e($a['service_name']) ?></td><td><?= $a['unique_attendees'] ?></td><td><?= $a['total_checkins'] ?></td></tr><?php endforeach; ?></tbody>
</table></div></div>
