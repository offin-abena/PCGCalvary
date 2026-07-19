<?php

namespace App\Models;

use App\Core\Model;

class Visitor extends Model
{
    protected string $table = 'visitors';

    public function getWithInviter(int $page = 1): array
    {
        return $this->paginate($page, 15, '1=1', [], 'visit_date DESC');
    }

    public function getPendingFollowups(): array
    {
        return $this->query(
            "SELECT v.*, CONCAT(m.first_name, ' ', m.last_name) as inviter_name 
             FROM visitors v LEFT JOIN members m ON v.invited_by = m.id 
             WHERE v.follow_up_status = 'pending' ORDER BY v.visit_date DESC"
        );
    }
}
