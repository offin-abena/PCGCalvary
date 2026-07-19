<?php

namespace App\Http\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Core\Model;

class DocumentController extends Controller
{
    public function index(): void
    {
        $db = new class extends Model { protected string $table = 'documents'; };
        $documents = $db->query(
            "SELECT d.*, u.username as uploaded_by_name FROM documents d 
             LEFT JOIN users u ON d.uploaded_by = u.id ORDER BY d.created_at DESC"
        );

        $this->render('documents/index', ['title' => 'Documents', 'documents' => $documents]);
    }

    public function upload(): void
    {
        $this->render('documents/upload', ['title' => 'Upload Document']);
    }

    public function store(): void
    {
        $filePath = $this->uploadFile('document', 'documents');
        if (!$filePath) {
            $this->flash('danger', 'Please select a file to upload.');
            $this->redirect('documents/upload');
        }

        $db = new class extends Model { protected string $table = 'documents'; };
        $id = $db->create([
            'title' => $this->input('title'),
            'document_type' => $this->input('document_type', 'other'),
            'description' => $this->input('description'),
            'file_path' => $filePath,
            'file_size' => $_FILES['document']['size'] ?? 0,
            'mime_type' => $_FILES['document']['type'] ?? 'application/octet-stream',
            'is_public' => $this->input('is_public') ? 1 : 0,
            'uploaded_by' => Auth::id(),
        ]);

        AuditLog::log('upload', 'documents', $id);
        $this->flash('success', 'Document uploaded successfully.');
        $this->redirect('documents');
    }

    public function sermons(): void
    {
        $db = new class extends Model { protected string $table = 'sermons'; };
        $this->render('documents/sermons', [
            'title' => 'Sermon Archive',
            'sermons' => $db->query("SELECT * FROM sermons ORDER BY sermon_date DESC"),
        ]);
    }

    public function storeSermon(): void
    {
        $db = new class extends Model { protected string $table = 'sermons'; };
        $id = $db->create([
            'title' => $this->input('title'),
            'preacher' => $this->input('preacher'),
            'sermon_date' => $this->input('sermon_date'),
            'scripture_reference' => $this->input('scripture_reference'),
            'summary' => $this->input('summary'),
            'audio_url' => safe_url($this->input('audio_url')) ?: null,
            'video_url' => safe_url($this->input('video_url')) ?: null,
        ]);

        AuditLog::log('create', 'sermons', $id);
        $this->flash('success', 'Sermon added to archive.');
        $this->redirect('documents/sermons');
    }

    public function download(string $id): void
    {
        $db = new class extends Model { protected string $table = 'documents'; };
        $doc = $db->find((int) $id);

        if (!$doc) {
            $this->redirect('documents');
        }

        $config = config('church');
        $filePath = $config['upload_path'] . $doc['file_path'];

        if (!file_exists($filePath)) {
            $this->flash('danger', 'File not found.');
            $this->redirect('documents');
        }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($doc['file_path']) . '"');
        readfile($filePath);
        exit;
    }
}
