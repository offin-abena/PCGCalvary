<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Member;
use App\Services\AgeGroupService;

class YouthController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'youth_classes'; };
        $classes = $db->query(
            "SELECT yc.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
             (SELECT COUNT(*) FROM youth_class_members ycm WHERE ycm.class_id = yc.id AND ycm.is_active = 1) as student_count
             FROM youth_classes yc
             LEFT JOIN members t ON yc.teacher_id = t.id
             WHERE yc.ministry_type IN ('children', 'junior_youth')
             ORDER BY FIELD(yc.ministry_type, 'children', 'junior_youth'), yc.name"
        );

        $this->render('youth/index', [
            'title' => 'Children & Youth Ministry',
            'classes' => $classes,
        ]);
    }

    public function showClass(string $id): void
    {
        $db = new class extends Model { protected string $table = 'youth_classes'; };
        $classId = (int) $id;
        $class = $db->queryOne(
            "SELECT yc.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
             FROM youth_classes yc
             LEFT JOIN members t ON yc.teacher_id = t.id
             WHERE yc.id = ?",
            [$classId]
        );

        if (!$class) {
            $this->flash('danger', 'Youth ministry class not found.');
            $this->redirect('youth');
        }

        $students = $db->query(
            "SELECT m.id, m.membership_id, m.member_code, m.first_name, m.last_name, m.middle_name,
                    m.gender, m.date_of_birth, m.phone, m.membership_status,
                    ycm.enrolled_date, ycm.id as enrollment_id
             FROM youth_class_members ycm
             JOIN members m ON m.id = ycm.child_member_id
             WHERE ycm.class_id = ? AND ycm.is_active = 1
             ORDER BY m.last_name, m.first_name",
            [$classId]
        );

        $memberModel = new Member();
        $eligibleMembers = $memberModel->getEligibleForYouthClass($classId, $class['ministry_type']);

        $this->render('youth/class', [
            'title' => $class['name'],
            'class' => $class,
            'students' => $students,
            'eligibleMembers' => $eligibleMembers,
            'membersListUrl' => $this->membersListUrlForMinistry($class['ministry_type']),
        ]);
    }

    public function classes(): void
    {
        $db = new class extends Model { protected string $table = 'youth_classes'; };
        $members = (new Member())->getActiveForAgeGroup('adults');

        $this->render('youth/classes', [
            'title' => 'Youth Classes',
            'classes' => $db->query(
                "SELECT yc.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                 (SELECT COUNT(*) FROM youth_class_members ycm WHERE ycm.class_id = yc.id AND ycm.is_active = 1) as student_count
                 FROM youth_classes yc
                 LEFT JOIN members t ON yc.teacher_id = t.id
                 ORDER BY yc.name"
            ),
            'members' => $members,
        ]);
    }

    public function storeClass(): void
    {
        $db = new class extends Model { protected string $table = 'youth_classes'; };
        $id = $db->create([
            'name' => $this->input('name'),
            'age_group' => $this->input('age_group'),
            'ministry_type' => $this->input('ministry_type'),
            'teacher_id' => $this->input('teacher_id') ?: null,
            'room' => $this->input('room'),
        ]);

        AuditLog::log('create', 'youth_classes', $id);
        $this->flash('success', 'Class created.');
        $this->redirect('youth/classes');
    }

    public function enroll(): void
    {
        $classId = (int) $this->input('class_id');
        $memberIds = $this->input('child_member_ids');
        if (!is_array($memberIds)) {
            $single = (int) $this->input('child_member_id');
            $memberIds = $single > 0 ? [$single] : [];
        }
        $memberIds = array_values(array_filter(array_map('intval', $memberIds)));

        if ($classId <= 0 || empty($memberIds)) {
            $this->flash('danger', 'Select at least one member to enroll.');
            $this->redirect($classId > 0 ? 'youth/classes/' . $classId : 'youth');
        }

        $classDb = new class extends Model { protected string $table = 'youth_classes'; };
        $class = $classDb->find($classId);
        if (!$class) {
            $this->flash('danger', 'Class not found.');
            $this->redirect('youth');
        }

        $memberModel = new Member();
        $enrollmentDb = new class extends Model { protected string $table = 'youth_class_members'; };
        $ageService = new AgeGroupService();
        $ageGroupKey = $this->ageGroupKeyForMinistry($class['ministry_type']);
        $enrolled = 0;
        $skipped = 0;

        foreach ($memberIds as $memberId) {
            $member = $memberModel->getWithFamily($memberId);
            if (!$member) {
                $skipped++;
                continue;
            }

            if ($ageGroupKey && !$ageService->memberBelongsToGroup($member, $ageGroupKey)
                && $class['ministry_type'] !== 'youth') {
                $skipped++;
                continue;
            }

            $enrollmentDb->query(
                "UPDATE youth_class_members ycm
                 JOIN youth_classes yc ON yc.id = ycm.class_id
                 SET ycm.is_active = 0
                 WHERE ycm.child_member_id = ? AND ycm.is_active = 1
                   AND yc.ministry_type = ? AND ycm.class_id != ?",
                [$memberId, $class['ministry_type'], $classId]
            );

            $existing = $enrollmentDb->queryOne(
                'SELECT id FROM youth_class_members WHERE class_id = ? AND child_member_id = ?',
                [$classId, $memberId]
            );

            if ($existing) {
                $enrollmentDb->update((int) $existing['id'], [
                    'is_active' => 1,
                    'enrolled_date' => date('Y-m-d'),
                    'parent_member_id' => $this->input('parent_member_id') ?: null,
                ]);
            } else {
                $enrollmentDb->create([
                    'class_id' => $classId,
                    'child_member_id' => $memberId,
                    'parent_member_id' => $this->input('parent_member_id') ?: null,
                    'enrolled_date' => date('Y-m-d'),
                ]);
            }

            AuditLog::log('enroll', 'youth_class_members', $memberId);
            $enrolled++;
        }

        if ($enrolled > 0) {
            $this->flash('success', $enrolled === 1
                ? 'Member enrolled in class.'
                : "{$enrolled} members enrolled in class.");
        }
        if ($skipped > 0) {
            $this->flash('warning', "{$skipped} member(s) could not be enrolled (not found or wrong age group).");
        }

        $this->redirect('youth/classes/' . $classId);
    }

    public function unenroll(): void
    {
        $classId = (int) $this->input('class_id');
        $enrollmentId = (int) $this->input('enrollment_id');

        if ($classId <= 0 || $enrollmentId <= 0) {
            $this->flash('danger', 'Invalid enrollment.');
            $this->redirect('youth');
        }

        $enrollmentDb = new class extends Model { protected string $table = 'youth_class_members'; };
        $row = $enrollmentDb->queryOne(
            'SELECT id, child_member_id FROM youth_class_members WHERE id = ? AND class_id = ? AND is_active = 1',
            [$enrollmentId, $classId]
        );

        if (!$row) {
            $this->flash('warning', 'Enrollment not found.');
            $this->redirect('youth/classes/' . $classId);
        }

        $enrollmentDb->update((int) $row['id'], ['is_active' => 0]);
        AuditLog::log('unenroll', 'youth_class_members', (int) $row['child_member_id']);
        $this->flash('success', 'Member removed from class.');
        $this->redirect('youth/classes/' . $classId);
    }

    private function ageGroupKeyForMinistry(string $ministryType): ?string
    {
        return match ($ministryType) {
            'children' => 'children_service',
            'junior_youth' => 'junior_youth',
            default => null,
        };
    }

    private function membersListUrlForMinistry(string $ministryType): string
    {
        return match ($ministryType) {
            'children' => 'members/children-service',
            'junior_youth' => 'members/junior-youth',
            default => 'members/adults',
        };
    }
}
