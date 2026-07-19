<div class="page-header">
    <h1>Visitors</h1>
    <a href="<?= url('visitors/create') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register Visitor</a>
</div>
<?php if (!empty($pendingFollowups)): ?>
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <?= count($pendingFollowups) ?> visitors need follow-up</div>
<?php endif; ?>
<div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Name</th><th>Visit Date</th><th>Phone</th><th>Follow-up</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($visitors['data'] as $v): ?>
        <tr><td class="fw-semibold"><?= e($v['first_name'].' '.$v['last_name']) ?></td><td><?= format_date($v['visit_date']) ?></td><td><?= e($v['phone'] ?? '-') ?></td><td><?= format_date($v['follow_up_date']) ?></td><td><?= status_badge($v['follow_up_status']) ?></td>
        <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followup<?= $v['id'] ?>">Follow-up</button></td></tr>
        <div class="modal fade" id="followup<?= $v['id'] ?>"><div class="modal-dialog modal-sm"><div class="modal-content">
            <form method="POST" action="<?= url('visitors/'.$v['id'].'/followup') ?>">
            <?= church_csrf_field() ?>
            <div class="modal-header"><h6>Follow-up</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <select name="follow_up_status" class="form-select mb-2"><option value="contacted">Contacted</option><option value="converted">Converted</option><option value="not_interested">Not Interested</option></select>
                    <textarea name="follow_up_notes" class="form-control" rows="2" placeholder="Notes"></textarea>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Save</button></div>
            </form>
        </div></div></div>
    <?php endforeach; ?></tbody>
</table></div></div>
