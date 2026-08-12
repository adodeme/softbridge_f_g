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
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

// Route pour l'erreur 401 JSON
Route::get('/unauthenticated', fn() => response()->json(['message' => 'Non authentifié'], 401));

// Routes PUBLIQUES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/step1', [AuthController::class, 'loginStep1']);
Route::post('/login/step2', [AuthController::class, 'loginStep2']);
Route::post('/otp/resend', [AuthController::class, 'resendOtp']);
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

    // Profil utilisateur (accessible à tous les rôles)
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/photo', [UserController::class, 'uploadPhoto']);

    // Authentifié, tous rôles
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::get('/appointments', [AppointmentController::class, 'index']);

    // Projets (lecture)
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show'])->where('id', '[0-9]+');

    // Factures (lecture de base, détails et téléchargement)
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/invoices/{id}/download', [InvoiceController::class, 'downloadPdf']);

    // Création de devis (vérification manuelle du rôle dans le contrôleur)
    Route::post('/create-quote', [QuoteController::class, 'store']);

    // Routes accessibles à tous les rôles (client, chef, admin)
    Route::middleware(['role:chef_projet,administrateur,client'])->group(function () {
        Route::get('/quotes', [QuoteController::class, 'index']);
        Route::get('/quotes/{id}', [QuoteController::class, 'show']);
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);
    });

    // Routes avec middleware license (vérifie licence active)
    Route::middleware(['license'])->group(function () {
        Route::get('/software/access/{license_id}', [SoftwareController::class, 'access']);
    });

    // Chef de projet ET Administrateur
    Route::middleware(['role:chef_projet,administrateur'])->group(function () {
        // Devis
        Route::put('/quotes/{quote}', [QuoteController::class, 'update']);
        Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy']);
        Route::post('/quotes/{id}/send', [QuoteController::class, 'send']);
        Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToInvoice']);

        // Clients
        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

        // Projets (mise à jour du statut)
        Route::match(['put', 'patch'], '/projects/{project}', [ProjectController::class, 'update']);

        // Rapports
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::put('/reports/{report}', [ReportController::class, 'update']);

        // Factures – liste complète (réservée chef/admin)
        Route::get('/invoices/all', [InvoiceController::class, 'all']);
    });

    // Client uniquement
    Route::middleware(['role:client'])->group(function () {
        Route::post('/quotes/{id}/validate', [QuoteController::class, 'validateQuote']);
        Route::post('/quotes/{id}/reject', [QuoteController::class, 'rejectQuote']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::post('/payments/simulate', [PaymentController::class, 'simulatePayment']);
        Route::get('/invoices', [InvoiceController::class, 'index']); // factures du client connecté
        Route::post('/software/verify-key', [SoftwareController::class, 'verifyKey']);
    });

    // Administrateur uniquement
    Route::middleware(['role:administrateur'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::apiResource('/catalog', SoftwareController::class)->except(['index', 'show']);

        Route::get('/reports/{id}/download', [ReportController::class, 'downloadPdf']);
        Route::post('/reports/{id}/ignore', [ReportController::class, 'ignore']);
    });
});
Route::get('/check-otp/{userId}', function ($userId) {
    $otp = \App\Models\Otp::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->first();

    if (!$otp) {
        return response()->json(['message' => 'Aucun OTP enregistré pour cet utilisateur.']);
    }

    return response()->json([
        'code' => $otp->code,
        'expires_at' => $otp->expires_at,
        'used_at' => $otp->used_at,
        'attempts' => $otp->attempts,
        'created_at' => $otp->created_at,
        'is_expired' => $otp->expires_at <= now(),
        'is_used' => !is_null($otp->used_at),
    ]);
});