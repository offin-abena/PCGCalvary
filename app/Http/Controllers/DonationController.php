<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Http\Controllers\Controller;
use App\Core\Model;
use App\Models\Finance;

class DonationController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'online_donations'; };
        $donations = $db->query(
            "SELECT od.*, it.name as type_name, CONCAT(m.first_name, ' ', m.last_name) as member_name, dc.name as campaign_name
             FROM online_donations od
             JOIN income_types it ON od.income_type_id = it.id
             LEFT JOIN members m ON od.member_id = m.id
             LEFT JOIN donation_campaigns dc ON od.campaign_id = dc.id
             ORDER BY od.donation_date DESC LIMIT 50"
        );

        $this->render('donations/index', ['title' => 'Online Donations', 'donations' => $donations]);
    }

    public function give(): void
    {
        $finance = new Finance();
        $db = new class extends Model { protected string $table = 'donation_campaigns'; };
        $campaigns = $db->query("SELECT * FROM donation_campaigns WHERE is_active = 1");

        $this->render('donations/give', [
            'title' => 'Give Online',
            'incomeTypes' => $finance->getIncomeTypes(),
            'campaigns' => $campaigns,
            'layout' => 'layouts/public',
        ]);
    }

    public function store(): void
    {
        $amount = (float) $this->input('amount');
        if ($amount <= 0 || $amount > 1000000) {
            $this->flash('danger', 'Please enter a valid donation amount.');
            $this->redirect('donations/give');
        }

        $db = new class extends Model { protected string $table = 'online_donations'; };
        $id = $db->create([
            'member_id' => $this->input('member_id') ?: null,
            'campaign_id' => $this->input('campaign_id') ?: null,
            'income_type_id' => $this->input('income_type_id'),
            'amount' => $amount,
            'is_recurring' => $this->input('is_recurring') ? 1 : 0,
            'recurring_frequency' => $this->input('recurring_frequency') ?: null,
            'payment_method' => $this->input('payment_method', 'online'),
            'transaction_ref' => 'TXN' . strtoupper(bin2hex(random_bytes(6))),
            'status' => 'pending',
            'receipt_sent' => 0,
        ]);

        AuditLog::log('create', 'donations', $id);
        $this->flash('success', 'Thank you! Your donation has been received and will be confirmed after payment verification.');
        $this->redirect('donations/give');
    }

    public function campaigns(): void
    {
        $db = new class extends Model { protected string $table = 'donation_campaigns'; };
        $this->render('donations/campaigns', [
            'title' => 'Fundraising Campaigns',
            'campaigns' => $db->query("SELECT * FROM donation_campaigns ORDER BY created_at DESC"),
        ]);
    }

    public function storeCampaign(): void
    {
        $db = new class extends Model { protected string $table = 'donation_campaigns'; };
        $id = $db->create([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'target_amount' => $this->input('target_amount') ?: null,
            'start_date' => $this->input('start_date') ?: null,
            'end_date' => $this->input('end_date') ?: null,
            'is_active' => 1,
        ]);

        AuditLog::log('create', 'campaigns', $id);
        $this->flash('success', 'Campaign created.');
        $this->redirect('donations/campaigns');
    }
}
