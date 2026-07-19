<?php

namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    protected string $table = 'events';

    public function getUpcoming(int $limit = 10): array
    {
        return $this->query(
            "SELECT e.*, (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registration_count
             FROM events e WHERE e.start_date >= NOW() AND e.status IN ('published','ongoing')
             ORDER BY e.start_date ASC LIMIT ?",
            [$limit]
        );
    }

    public function getAll(int $page = 1): array
    {
        $offset = ($page - 1) * 15;
        $total = (int) $this->db->query("SELECT COUNT(*) FROM events")->fetchColumn();

        $data = $this->query(
            "SELECT e.*, (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registration_count
             FROM events e ORDER BY e.start_date DESC LIMIT 15 OFFSET ?",
            [$offset]
        );

        return ['data' => $data, 'total' => $total, 'page' => $page, 'total_pages' => (int) ceil($total / 15)];
    }

    public function getRegistrations(int $eventId): array
    {
        return $this->query(
            "SELECT er.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
             FROM event_registrations er LEFT JOIN members m ON er.member_id = m.id 
             WHERE er.event_id = ? ORDER BY er.registration_date DESC",
            [$eventId]
        );
    }

    public function getVolunteers(int $eventId): array
    {
        return $this->query(
            "SELECT ev.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
             FROM event_volunteers ev JOIN members m ON ev.member_id = m.id 
             WHERE ev.event_id = ?",
            [$eventId]
        );
    }
}
