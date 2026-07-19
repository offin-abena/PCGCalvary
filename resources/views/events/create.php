<div class="page-header"><h1>Create Event</h1></div>
<div class="card" style="max-width: 700px;"><div class="card-body">
    <form method="POST" action="<?= url('events/store') ?>">
            <?= church_csrf_field() ?>
            <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Type</label><select name="event_type" class="form-select"><option value="service">Service</option><option value="conference">Conference</option><option value="meeting">Meeting</option><option value="outreach">Outreach</option><option value="other">Other</option></select></div>
            <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option></select></div>
            <div class="col-md-6"><label class="form-label">Start Date *</label><input type="datetime-local" name="start_date" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">End Date</label><input type="datetime-local" name="end_date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Max Attendees</label><input type="number" name="max_attendees" class="form-control"></div>
        </div>
        <div class="form-check mt-3"><input type="checkbox" name="registration_required" value="1" class="form-check-input" id="regReq"><label class="form-check-label" for="regReq">Registration Required</label></div>
        <button type="submit" class="btn btn-primary mt-3">Create Event</button>
    </form>
</div></div>
