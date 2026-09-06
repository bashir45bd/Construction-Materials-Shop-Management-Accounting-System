<?php
/**
 * AJAX Controller: Customer Operations
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Services\CustomerLedgerService;
use App\Services\CustomerAdvanceService;
use App\Services\DocumentNumberService;
use App\Helpers\Csrf;

Auth::requireLogin();

$action = $_GET['action'] ?? '';
$shopId = Auth::shopId();
$userId = (int)Auth::id();

try {
    $pdo = DB::pdo();

    switch ($action) {
        case 'search':
            $q = cleanInput($_GET['q'] ?? '');
            if (strlen($q) < 1) {
                jsonResponse(['success' => true, 'customers' => []]);
            }

            $stmt = $pdo->prepare("SELECT id, customer_code, name, mobile, address, current_due, current_advance, status 
                                   FROM customers 
                                   WHERE shop_id = :s AND (name LIKE :q_name OR mobile LIKE :q_mobile OR customer_code LIKE :q_code OR address LIKE :q_address)
                                   ORDER BY name ASC LIMIT 15");
            $searchTerm = "%{$q}%";
            $stmt->execute([
                's' => $shopId,
                'q_name' => $searchTerm,
                'q_mobile' => $searchTerm,
                'q_code' => $searchTerm,
                'q_address' => $searchTerm,
            ]);
            $customers = $stmt->fetchAll();

            jsonResponse(['success' => true, 'customers' => $customers]);
            break;

        case 'create_inline':
            Auth::requirePermission('customer.manage');
            Csrf::verifyOrDie();
            $input = json_decode(file_get_contents('php://input'), true);

            $name    = cleanInput($input['name'] ?? '');
            $mobile  = cleanInput($input['mobile'] ?? '');
            $address = cleanInput($input['address'] ?? '');
            $type    = cleanInput($input['customer_type'] ?? 'retail');
            $notes   = cleanInput($input['notes'] ?? '');

            if (empty($name) || empty($mobile)) {
                jsonResponse(['success' => false, 'message' => 'Customer Name and Mobile Number are required.'], 422);
            }

            // Check if mobile already exists
            $checkStmt = $pdo->prepare("SELECT id, customer_code, name, mobile, address, current_due, current_advance FROM customers WHERE shop_id = :s AND mobile = :m LIMIT 1");
            $checkStmt->execute(['s' => $shopId, 'm' => $mobile]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Return existing customer immediately
                jsonResponse([
                    'success'  => true,
                    'customer' => $existing,
                    'is_new'   => false,
                    'message'  => 'Existing customer found with this mobile number.'
                ]);
            }

            // Generate Customer Code
            $custCountStmt = $pdo->prepare("SELECT COUNT(id) FROM customers WHERE shop_id = :s");
            $custCountStmt->execute(['s' => $shopId]);
            $nextCode = 'CUST-' . str_pad((string)((int)$custCountStmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

            $insStmt = $pdo->prepare("INSERT INTO customers 
                (shop_id, customer_code, name, mobile, address, customer_type, credit_limit, opening_due, opening_advance, current_due, current_advance, status, notes)
                VALUES (:s, :code, :name, :mobile, :addr, :type, 0.00, 0.00, 0.00, 0.00, 0.00, 'active', :notes)");
            
            $insStmt->execute([
                's'      => $shopId,
                'code'   => $nextCode,
                'name'   => $name,
                'mobile' => $mobile,
                'addr'   => $address,
                'type'   => $type,
                'notes'  => $notes
            ]);

            $newCustId = (int)$pdo->lastInsertId();

            $newCustomer = [
                'id'              => $newCustId,
                'customer_code'   => $nextCode,
                'name'            => $name,
                'mobile'          => $mobile,
                'address'         => $address,
                'current_due'     => '0.00',
                'current_advance' => '0.00'
            ];

            jsonResponse([
                'success'  => true,
                'customer' => $newCustomer,
                'is_new'   => true,
                'message'  => 'New customer created and selected.'
            ]);
            break;

        case 'receive_payment':
            Auth::requirePermission('customer.payment');
            Csrf::verifyOrDie();
            $input = json_decode(file_get_contents('php://input'), true);

            $customerId = (int)($input['customer_id'] ?? 0);
            $amount     = (float)($input['amount'] ?? 0.0);
            $method     = (string)($input['payment_method'] ?? 'Cash');
            $accType    = (string)($input['account_type'] ?? 'cash');
            $accId      = (int)($input['account_id'] ?? 1);
            $notes      = cleanInput($input['notes'] ?? '');
            $payDate    = cleanInput($input['payment_date'] ?? date('Y-m-d'));

            if ($customerId <= 0 || $amount <= 0) {
                jsonResponse(['success' => false, 'message' => 'Valid customer and payment amount are required.'], 422);
            }

            $res = CustomerLedgerService::receivePayment(
                $customerId,
                $amount,
                $method,
                $accType,
                $accId,
                $notes,
                $userId,
                $payDate,
                null,
                $shopId
            );

            jsonResponse([
                'success'    => true,
                'message'    => "Payment voucher {$res['payment_no']} recorded successfully.",
                'payment_no' => $res['payment_no'],
                'new_due'    => $res['new_due']
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
    }
} catch (\Throwable $e) {
    error_log('[Customer API] ' . $e->getMessage());
    $status = $e instanceof \RuntimeException ? 422 : 500;
    jsonResponse(['success' => false, 'message' => $status === 422 ? $e->getMessage() : 'Unable to complete the customer operation.'], $status);
}
