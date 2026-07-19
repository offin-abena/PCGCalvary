<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Member;

class AssetController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'assets'; };
        $assets = $db->query(
            "SELECT a.*, CONCAT(m.first_name, ' ', m.last_name) as assigned_name 
             FROM assets a LEFT JOIN members m ON a.assigned_to = m.id ORDER BY a.name"
        );

        $stats = [
            'total' => count($assets),
            'total_value' => array_sum(array_column($assets, 'current_value')),
            'maintenance_due' => count(array_filter($assets, fn($a) => $a['next_maintenance_date'] && $a['next_maintenance_date'] <= date('Y-m-d', strtotime('+30 days')))),
        ];

        $this->render('assets/index', ['title' => 'Assets & Inventory', 'assets' => $assets, 'stats' => $stats]);
    }

    public function create(): void
    {
        $members = (new Member())->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");
        $this->render('assets/create', ['title' => 'Register Asset', 'members' => $members]);
    }

    public function store(): void
    {
        $db = new class extends Model { protected string $table = 'assets'; };
        $id = $db->create([
            'asset_code' => 'AST' . strtoupper(bin2hex(random_bytes(4))),
            'name' => $this->input('name'),
            'category' => $this->input('category'),
            'description' => $this->input('description'),
            'purchase_date' => $this->input('purchase_date') ?: null,
            'purchase_cost' => $this->input('purchase_cost') ?: null,
            'current_value' => $this->input('current_value') ?: null,
            'location' => $this->input('location'),
            'condition_status' => $this->input('condition_status', 'good'),
            'assigned_to' => $this->input('assigned_to') ?: null,
            'next_maintenance_date' => $this->input('next_maintenance_date') ?: null,
        ]);

        AuditLog::log('create', 'assets', $id);
        $this->flash('success', 'Asset registered successfully.');
        $this->redirect('assets/' . $id);
    }

    public function show(string $id): void
    {
        $db = new class extends Model { protected string $table = 'assets'; };
        $asset = $db->queryOne(
            "SELECT a.*, CONCAT(m.first_name, ' ', m.last_name) as assigned_name 
             FROM assets a LEFT JOIN members m ON a.assigned_to = m.id WHERE a.id = ?",
            [$id]
        );

        if (!$asset) {
            $this->redirect('assets');
        }

        $maintenance = $db->query("SELECT * FROM asset_maintenance WHERE asset_id = ? ORDER BY maintenance_date DESC", [$id]);

        $this->render('assets/show', ['title' => $asset['name'], 'asset' => $asset, 'maintenance' => $maintenance]);
    }

    public function addMaintenance(string $id): void
    {
        $db = new class extends Model { protected string $table = 'asset_maintenance'; };
        $db->create([
            'asset_id' => (int) $id,
            'maintenance_date' => $this->input('maintenance_date', date('Y-m-d')),
            'maintenance_type' => $this->input('maintenance_type'),
            'cost' => $this->input('cost') ?: 0,
            'performed_by' => $this->input('performed_by'),
            'notes' => $this->input('notes'),
            'next_due_date' => $this->input('next_due_date') ?: null,
        ]);

        if ($this->input('next_due_date')) {
            $assetDb = new class extends Model { protected string $table = 'assets'; };
            $assetDb->update((int) $id, [
                'last_maintenance_date' => $this->input('maintenance_date', date('Y-m-d')),
                'next_maintenance_date' => $this->input('next_due_date'),
            ]);
        }

        AuditLog::log('maintenance', 'assets', (int) $id);
        $this->flash('success', 'Maintenance record added.');
        $this->redirect('assets/' . $id);
    }

    public function bookings(): void
    {
        $db = new class extends Model { protected string $table = 'resource_bookings'; };
        $bookings = $db->query(
            "SELECT rb.*, a.name as asset_name, CONCAT(m.first_name, ' ', m.last_name) as booked_by_name
             FROM resource_bookings rb
             JOIN assets a ON rb.asset_id = a.id
             JOIN members m ON rb.booked_by = m.id
             ORDER BY rb.start_datetime DESC"
        );

        $assets = $db->query("SELECT id, name FROM assets WHERE is_active = 1 ORDER BY name");
        $members = (new Member())->query("SELECT id, first_name, last_name FROM members ORDER BY last_name");

        $this->render('assets/bookings', [
            'title' => 'Resource Bookings',
            'bookings' => $bookings,
            'assets' => $assets,
            'members' => $members,
        ]);
    }
}
