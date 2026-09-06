<?php
/**
 * AJAX Controller: Sales Memo Engine
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

use App\Auth\Auth;
use App\Services\MemoService;
use App\Helpers\Csrf;

// Require Authentication
Auth::requireLogin();

$action = $_GET['action'] ?? '';
$shopId = Auth::shopId();
$userId = (int)Auth::id();

try {
    switch ($action) {
        case 'create':
            Auth::requirePermission('sales.create');
            Csrf::verifyOrDie();
            
            // Read JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                jsonResponse(['success' => false, 'message' => 'Invalid JSON payload received.'], 400);
            }

            $customerId     = (int)($input['customer_id'] ?? 0);
            $items          = (array)($input['items'] ?? []);
            $subtotal       = (float)($input['subtotal'] ?? 0.0);
            $discountType   = (string)($input['discount_type'] ?? 'fixed');
            $discountValue  = (float)($input['discount_value'] ?? 0.0);
            $discountAmount = (float)($input['discount_amount'] ?? 0.0);
            $grandTotal     = (float)($input['grand_total'] ?? 0.0);
            $paidAmount     = (float)($input['paid_amount'] ?? 0.0);
            $useAdvance     = (bool)($input['use_advance'] ?? false);
            $accountType    = (string)($input['account_type'] ?? 'cash');
            $accountId      = (int)($input['account_id'] ?? 1);
            $notes          = !empty($input['notes']) ? cleanInput($input['notes']) : null;
            $saleDate       = !empty($input['sale_date']) ? cleanInput($input['sale_date']) : date('Y-m-d');

            $result = MemoService::createMemo(
                $customerId,
                $items,
                $subtotal,
                $discountType,
                $discountValue,
                $discountAmount,
                $grandTotal,
                $paidAmount,
                $useAdvance,
                $accountType,
                $accountId,
                $notes,
                $userId,
                $saleDate,
                $shopId
            );

            jsonResponse([
                'success'             => true,
                'message'             => 'Sales memo generated successfully.',
                'sale_id'             => $result['sale_id'],
                'memo_number'         => $result['memo_number'],
                'grand_total'         => $result['grand_total'],
                'paid_amount'         => $result['paid_amount'],
                'due_amount'          => $result['due_amount'],
                'overpayment_advance' => $result['overpayment_advance']
            ]);
            break;

        case 'top_products':
            $periodDays = (int)($_GET['days'] ?? 30);
            $products = MemoService::getTop20Products($periodDays, $shopId);
            jsonResponse(['success' => true, 'products' => $products]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action requested.'], 400);
    }
} catch (\Throwable $e) {
    error_log('[Memo API] ' . $e->getMessage());
    $status = $e instanceof \RuntimeException ? 422 : 500;
    jsonResponse(['success' => false, 'message' => $status === 422 ? $e->getMessage() : 'Unable to save the memo. Please try again or contact an administrator.'], $status);
}
