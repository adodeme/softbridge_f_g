<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ForgotPasswordController;

// Route pour l'erreur 401 JSON
Route::get('/unauthenticated', fn() => response()->json(['message' => 'Non authentifié'], 401));

// Routes PUBLIQUES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/catalog', [SoftwareController::class, 'index']);
Route::get('/catalog/{id}', [SoftwareController::class, 'show']);
Route::post('/appointments', [AppointmentController::class, 'store']);
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::get('/appointments/{id}/download', [AppointmentController::class, 'downloadPdf']);
Route::post('/webhooks/fedapay', [PaymentController::class, 'handleWebhook']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

// Routes PROTÉGÉES (authentification requise)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{id}/download', [InvoiceController::class, 'downloadPdf']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

    // --- ROUTE DE CRÉATION DE DEVIS (NOUVELLE ROUTE, HORS DE TOUT GROUPE ROLE) ---
    Route::post('/create-quote', [QuoteController::class, 'store']);

    // --- ROUTES ACCESSIBLES AU CHEF DE PROJET, À L'ADMIN ET AU CLIENT (POUR CONSULTATION) ---
    // Fusion des deux routes GET /quotes
    Route::middleware(['role:chef_projet,administrateur,client'])->group(function () {
        Route::get('/quotes', [QuoteController::class, 'index']);
        Route::get('/quotes/{id}', [QuoteController::class, 'show']);
    });

    Route::middleware(['license'])->group(function () {
        // Route de téléchargement ou d'accès au logiciel protégé par licence
        Route::get('/download-software/{license_id}', [SoftwareController::class, 'download']);
    });

    // --- ROUTES RÉSERVÉES AU CHEF DE PROJET ET À L'ADMIN ---
    Route::middleware(['role:chef_projet,administrateur'])->group(function () {
        Route::put('/quotes/{quote}', [QuoteController::class, 'update']);
        Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy']);
        Route::post('/quotes/{id}/send', [QuoteController::class, 'send']);
        Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice']);
        
        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
        Route::post('/projects/{project}', [ProjectController::class, 'update']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::get('/invoices/all', [InvoiceController::class, 'all']);
        Route::match(['put', 'patch'], '/projects/{project}', [ProjectController::class, 'update']);
        Route::put('/reports/{report}', [ReportController::class, 'update']);
    });
    Route::middleware(['role:chef_projet,administrateur,client'])->group(function () {
        // ... autres routes
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);
        // ... 
    });

    // --- ROUTES RÉSERVÉES AU CLIENT ---
    Route::middleware(['role:client'])->group(function () {
        // Retrait de GET /quotes et GET /quotes/{id} (déjà fusionnées)
        Route::post('/quotes/{id}/validate', [QuoteController::class, 'validateQuote']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('/invoices', [InvoiceController::class, 'index']); // Uniquement pour le client
        Route::post('/quotes/{id}/reject', [QuoteController::class, 'rejectQuote']);
    });

    // --- ROUTES RÉSERVÉES À L'ADMINISTRATEUR SEUL ---
    Route::middleware(['role:administrateur'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::apiResource('/catalog', SoftwareController::class)->except(['index', 'show']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/reports/{id}/download', [ReportController::class, 'downloadPdf']);
        Route::post('/reports/{id}/ignore', [ReportController::class, 'ignore']);
    });
});

