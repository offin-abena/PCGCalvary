<?php
/** @var array $member */
/** @var array $families */
/** @var string $formAction */
/** @var string $submitLabel */
/** @var bool $isEdit */

$member = $member ?? [];
$isEdit = $isEdit ?? false;

$val = static function (string $field, $default = '') use ($member): string {
    $old = $_SESSION['old'][$field] ?? null;
    if ($old !== null && $old !== '') {
        return e((string) $old);
    }
    return e((string) ($member[$field] ?? $default));
};

$selected = static function (string $field, $value) use ($member): string {
    $current = $_SESSION['old'][$field] ?? ($member[$field] ?? '');
    return (string) $current === (string) $value ? 'selected' : '';
};

$checked = static function (string $field) use ($member): string {
    $current = $_SESSION['old'][$field] ?? ($member[$field] ?? 0);
    return $current ? 'checked' : '';
};
?>

<form method="POST" action="<?= url($formAction) ?>" enctype="multipart/form-data">
            <?= church_csrf_field() ?>
            <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Membership ID</label>
                            <input type="text" name="membership_id" class="form-control" value="<?= $val('membership_id') ?>" placeholder="e.g. CPCN/MC/19">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Surname *</label>
                            <input type="text" name="last_name" class="form-control" value="<?= $val('last_name') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" value="<?= $val('first_name') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?= $val('middle_name') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= $val('date_of_birth') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sex</label>
                            <select name="gender" class="form-select">
                                <option value="male" <?= $selected('gender', 'male') ?>>Male</option>
                                <option value="female" <?= $selected('gender', 'female') ?>>Female</option>
                                <option value="other" <?= $selected('gender', 'other') ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hometown</label>
                            <input type="text" name="hometown" class="form-control" value="<?= $val('hometown') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control" value="<?= $val('nationality', 'Ghanaian') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Languages</label>
                            <input type="text" name="languages" class="form-control" value="<?= $val('languages') ?>" placeholder="e.g. Ga, Twi, English">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= $val('email') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact No</label>
                            <input type="text" name="phone" class="form-control" value="<?= $val('phone') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <?php foreach (['single' => 'Single', 'married' => 'Married', 'divorced' => 'Separated', 'widowed' => 'Widow'] as $k => $label): ?>
                                <option value="<?= $k ?>" <?= $selected('marital_status', $k) ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Residence</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Residential Address</label>
                            <input type="text" name="address" class="form-control" value="<?= $val('address') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Residential Place</label>
                            <input type="text" name="city" class="form-control" value="<?= $val('city') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Church Membership</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Generational Group</label>
                            <select name="family_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($families as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= $selected('family_id', $f['id']) ?>><?= e($f['family_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Age group (Children Service, Junior Youth, Adults) is determined from date of birth.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Membership Status</label>
                            <select name="membership_status" class="form-select">
                                <?php foreach (['active' => 'On', 'inactive' => 'Inactive', 'visitor' => 'Visitor', 'new_convert' => 'New Convert', 'transferred' => 'Transferred', 'deceased' => 'Deceased'] as $k => $label): ?>
                                <option value="<?= $k ?>" <?= $selected('membership_status', $k) ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Current Church Position</label>
                            <input type="text" name="current_church_position" class="form-control" value="<?= $val('current_church_position') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Previous Church Position</label>
                            <input type="text" name="previous_church_position" class="form-control" value="<?= $val('previous_church_position') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Years in Church</label>
                            <input type="text" name="years_in_church" class="form-control" value="<?= $val('years_in_church') ?>" placeholder="e.g. 10-15 years">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Registration Date</label>
                            <input type="text" name="registration_date" class="form-control" value="<?= $val('registration_date') ?>" placeholder="e.g. 09/06/23">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Family Members</label>
                            <textarea name="family_member" class="form-control" rows="2"><?= $val('family_member') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Marriage Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Marriage Type</label>
                            <input type="text" name="marriage_type" class="form-control" value="<?= $val('marriage_type') ?>" placeholder="e.g. Ordinance">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marriage Date</label>
                            <input type="date" name="marriage_date" class="form-control" value="<?= $val('marriage_date') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Officiating Minister</label>
                            <input type="text" name="marriage_officiating_minister" class="form-control" value="<?= $val('marriage_officiating_minister') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Spouse Name</label>
                            <input type="text" name="spouse_name" class="form-control" value="<?= $val('spouse_name') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Spouse Date of Birth</label>
                            <input type="date" name="spouse_dob" class="form-control" value="<?= $val('spouse_dob') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Spouse Contact No</label>
                            <input type="text" name="spouse_contact_no" class="form-control" value="<?= $val('spouse_contact_no') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Spouse Hometown</label>
                            <input type="text" name="spouse_hometown" class="form-control" value="<?= $val('spouse_hometown') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Spouse Place of Worship</label>
                            <input type="text" name="spouse_place_worship" class="form-control" value="<?= $val('spouse_place_worship') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Children & Parents</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">No. of Biological Children</label>
                            <input type="text" name="biological_children" class="form-control" value="<?= $val('biological_children') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Children Names</label>
                            <textarea name="biological_children_name" class="form-control" rows="2"><?= $val('biological_children_name') ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Children Dates of Birth</label>
                            <textarea name="biological_children_dob" class="form-control" rows="2"><?= $val('biological_children_dob') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Biological Father</label>
                            <input type="text" name="biological_father_name" class="form-control" value="<?= $val('biological_father_name') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father Status</label>
                            <select name="biological_father_status" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Living', 'Deceased'] as $s): ?>
                                <option value="<?= $s ?>" <?= $selected('biological_father_status', $s) ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Biological Mother</label>
                            <input type="text" name="biological_mother_name" class="form-control" value="<?= $val('biological_mother_name') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother Status</label>
                            <select name="biological_mother_status" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Living', 'Deceased'] as $s): ?>
                                <option value="<?= $s ?>" <?= $selected('biological_mother_status', $s) ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Education & Occupation</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Currently Schooling</label>
                            <select name="currently_schooling" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Yes', 'No'] as $s): ?>
                                <option value="<?= $s ?>" <?= $selected('currently_schooling', $s) ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">School Name</label>
                            <input type="text" name="school_name" class="form-control" value="<?= $val('school_name') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Completion Date</label>
                            <input type="date" name="completion_date" class="form-control" value="<?= $val('completion_date') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Educational Level</label>
                            <input type="text" name="educational_level" class="form-control" value="<?= $val('educational_level') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Qualification</label>
                            <input type="text" name="qualification" class="form-control" value="<?= $val('qualification') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control" value="<?= $val('occupation') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profession</label>
                            <input type="text" name="profession" class="form-control" value="<?= $val('profession') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Baptism & Confirmation</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Baptized</label>
                            <select name="baptism_status" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Yes', 'No'] as $s): ?>
                                <option value="<?= $s ?>" <?= $selected('baptism_status', $s) ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Baptism Date</label>
                            <input type="date" name="baptism_date" class="form-control" value="<?= $val('baptism_date') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Baptism Place / Date (legacy)</label>
                            <input type="text" name="baptism_place" class="form-control" value="<?= $val('baptism_place') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Baptism Minister</label>
                            <input type="text" name="baptism_minister" class="form-control" value="<?= $val('baptism_minister') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Confirmed</label>
                            <select name="confirmation_status" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['Yes', 'No'] as $s): ?>
                                <option value="<?= $s ?>" <?= $selected('confirmation_status', $s) ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Confirmation Date</label>
                            <input type="date" name="confirmation_date" class="form-control" value="<?= $val('confirmation_date') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmation Place</label>
                            <input type="text" name="confirmation_place" class="form-control" value="<?= $val('confirmation_place') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmation Minister</label>
                            <input type="text" name="confirmation_minister" class="form-control" value="<?= $val('confirmation_minister') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Next of Kin & Notes</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Next of Kin</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="<?= $val('emergency_contact_name') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_contact_relation" class="form-control" value="<?= $val('emergency_contact_relation') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Next of Kin Phone</label>
                            <input type="text" name="emergency_contact_phone" class="form-control" value="<?= $val('emergency_contact_phone') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Next of Kin Residence</label>
                            <input type="text" name="next_of_kin_residence" class="form-control" value="<?= $val('next_of_kin_residence') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?= $val('notes') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-body">
                    <?php if ($isEdit && !empty($member['member_code'])): ?>
                    <p class="small text-muted mb-2">System Code: <code><?= e($member['member_code']) ?></code></p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check-lg"></i> <?= e($submitLabel) ?>
                    </button>
                    <a href="<?= url($isEdit ? 'members/' . $member['id'] : 'members/adults') ?>" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
