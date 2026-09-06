<?php
/**
 * Sales Returns Processing (বিক্রয় ফেরত)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;
use App\Services\CustomerLedgerService;
use App\Services\FinanceService;
use App\Services\DocumentNumberService;

Auth::requireLogin();
Auth::requirePermission('sales.return');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Sales Return Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $saleId       = (int)($_POST['sale_id'] ?? 0);
    $returnAmount = (float)($_POST['total_return_amount'] ?? 0.0);
    $refundCash   = (float)($_POST['refund_amount'] ?? 0.0);
    $reason       = cleanInput($_POST['reason'] ?? 'Customer Return');
    $items        = $_POST['items'] ?? []; // [sale_item_id => qty]

    if ($saleId > 0 && !empty($items)) {
        DB::transaction(function($pdo) use ($saleId, $returnAmount, $refundCash, $reason, $items, $userId, $shopId) {
            $returnNo = DocumentNumberService::getNextNumber('SALE_RETURN', $shopId);
            $today = date('Y-m-d');

            // Fetch Sale & Customer
            $stmtSale = $pdo->prepare("SELECT id, memo_number, customer_id FROM sales WHERE id = :id FOR UPDATE");
            $stmtSale->execute(['id' => $saleId]);
            $sale = $stmtSale->fetch();
            $customerId = (int)$sale['customer_id'];

            // Due reduced or advance credited
            $dueReduced = max(0.0, $returnAmount - $refundCash);

            // Insert sales_returns
            $stmtRet = $pdo->prepare("INSERT INTO sales_returns 
                (shop_id, return_no, sale_id, customer_id, return_date, total_return_amount, refund_amount, advance_credited, due_reduced, reason, created_by)
                VALUES (:s, :rno, :sid, :cid, :rdate, :tot, :ref, 0.00, :due_red, :reason, :uid)");
            $stmtRet->execute([
                's'       => $shopId,
                'rno'     => $returnNo,
                'sid'     => $saleId,
                'cid'     => $customerId,
                'rdate'   => $today,
                'tot'     => $returnAmount,
                'ref'     => $refundCash,
                'due_red' => $dueReduced,
                'reason'  => $reason,
                'uid'     => $userId
            ]);
            $returnId = (int)$pdo->lastInsertId();

            // Process Return Items & Restock
            $stmtItemRet = $pdo->prepare("INSERT INTO sales_return_items 
                (return_id, sale_item_id, product_id, unit_id, quantity, base_quantity, unit_price, unit_cost, line_total)
                VALUES (:rid, :si_id, :pid, :uid, :qty, :bqty, :price, :cost, :ltot)");

            foreach ($items as $saleItemId => $qty) {
                $qty = (float)$qty;
                if ($qty <= 0) continue;

                $stmtSi = $pdo->prepare("SELECT * FROM sale_items WHERE id = :id");
                $stmtSi->execute(['id' => (int)$saleItemId]);
                $si = $stmtSi->fetch();

                $productId = (int)$si['product_id'];
                $unitId = (int)$si['unit_id'];
                $unitPrice = (float)$si['unit_price'];
                $unitCost = (float)$si['unit_cost'];
                $lineTotal = $qty * $unitPrice;

                $prodStmt = $pdo->prepare("SELECT base_unit_id FROM products WHERE id = :id");
                $prodStmt->execute(['id' => $productId]);
                $baseUnitId = (int)$prodStmt->fetchColumn();

                $baseQty = StockEngineService::convertToBaseUnit($qty, $unitId, $baseUnitId, $shopId);

                $stmtItemRet->execute([
                    'rid'   => $returnId,
                    'si_id' => $saleItemId,
                    'pid'   => $productId,
                    'uid'   => $unitId,
                    'qty'   => $qty,
                    'bqty'  => $baseQty,
                    'price' => $unitPrice,
                    'cost'  => $unitCost,
                    'ltot'  => $lineTotal
                ]);

                // Stock Movement (INFLOW)
                StockEngineService::recordMovement(
                    $productId,
                    'SALE_RETURN',
                    $qty,
                    $unitId,
                    $unitCost,
                    'SALE_RETURN',
                    $returnId,
                    $returnNo,
                    $userId,
                    "Returned from Memo [{$sale['memo_number']}]",
                    $shopId
                );
            }

            // Customer Ledger Adjustment
            // Credit: Reduce Customer Due by dueReduced
            if ($dueReduced > 0) {
                CustomerLedgerService::recordEntry(
                    $customerId,
                    'SALE_RETURN',
                    'SALE_RETURN',
                    $returnId,
                    $returnNo,
                    "Sales Return against Memo [{$sale['memo_number']}]",
                    0.00,
                    $dueReduced,
                    $userId,
                    $today,
                    $shopId
                );
            }

            // If cash was refunded, record Financial Outflow
            if ($refundCash > 0) {
                FinanceService::recordOutflow(
                    'cash',
                    1,
                    'CUSTOMER_COLLECTION',
                    'SALE_RETURN',
                    $returnId,
                    $returnNo,
                    $refundCash,
                    "Cash Refund for Return [{$returnNo}]",
                    $userId,
                    $today,
                    $shopId
                );
            }

            // Update Sale Status
            $updSale = $pdo->prepare("UPDATE sales SET sale_status = 'returned' WHERE id = :id");
            $updSale->execute(['id' => $saleId]);
        });

        flash('success', "Sales return processed successfully.");
        redirect(BASE_URL . '/modules/sales/returns.php');
    }
}

// Fetch returns history
$returns = $pdo->query("SELECT sr.*, s.memo_number, c.name as customer_name, u.full_name as created_by_name
                        FROM sales_returns sr
                        JOIN sales s ON sr.sale_id = s.id
                        JOIN customers c ON sr.customer_id = c.id
                        JOIN users u ON sr.created_by = u.id
                        WHERE sr.shop_id = {$shopId}
                        ORDER BY sr.id DESC LIMIT 50")->fetchAll();

$pageTitle = 'Sales Returns (বিক্রয় ফেরত)';
$activeMenu = 'sales';
$activeSub = 'returns';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-rotate-left"></i> Sales Returns (বিক্রয় ফেরত)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Restock returned materials and adjust customer receivables ledger.</p>
    </div>
  </div>

  <!-- Search Memo for Return -->
  <div style="background:var(--bg-surface-secondary);padding:1.25rem;border-radius:var(--radius);margin-bottom:1.5rem;">
    <div style="font-weight:700;margin-bottom:0.5rem;"><i class="fa fa-search"></i> Step 1: Find Sales Memo for Return</div>
    <div style="display:flex;gap:0.75rem;max-width:500px;">
      <input type="text" id="ret-memo-search" class="form-control" placeholder="Enter Memo Number e.g. MT-2026-000001">
      <button type="button" class="btn btn-primary" onclick="lookupMemoForReturn()"><i class="fa fa-search"></i> Load Memo</button>
    </div>
  </div>

  <!-- Return Processing Container -->
  <div id="return-form-container" style="display:none;margin-bottom:2rem;border:1px solid var(--border-color);padding:1.5rem;border-radius:var(--radius);">
    <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:1rem;" id="ret-disp-memo-title">Return Items for Memo</h3>
    <form method="POST" action="">
      <?= Csrf::field() ?>
      <input type="hidden" name="sale_id" id="ret-sale-id" value="0">

      <div class="table-responsive" style="margin-bottom:1rem;">
        <table class="table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Sold Qty</th>
              <th>Rate</th>
              <th>Return Qty</th>
            </tr>
          </thead>
          <tbody id="ret-items-tbody"></tbody>
        </table>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
        <div class="form-group">
          <label class="form-label">Total Return Value (ফেরত মূল্য)</label>
          <input type="number" step="any" name="total_return_amount" id="ret-total-amount" class="form-control" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Cash Refunded Now (নগদ ফেরত দিলে লিখুন)</label>
          <input type="number" step="any" min="0" name="refund_amount" id="ret-refund-amount" class="form-control" value="0">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Return Reason</label>
        <input type="text" name="reason" class="form-control" placeholder="e.g. Excess bags left after roof casting" required>
      </div>

      <button type="submit" class="btn btn-danger"><i class="fa fa-rotate-left"></i> Confirm Return & Restock</button>
    </form>
  </div>

  <!-- Past Returns History -->
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Return Voucher</th>
          <th>Date</th>
          <th>Original Memo</th>
          <th>Customer</th>
          <th>Return Total</th>
          <th>Cash Refunded</th>
          <th>Due Adjusted</th>
          <th>Reason</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($returns)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No returns processed.</td></tr>
        <?php else: ?>
          <?php foreach ($returns as $r): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($r['return_no']) ?></td>
              <td><?= formatDate($r['return_date']) ?></td>
              <td><a href="<?= BASE_URL ?>/modules/sales/memo-print.php?id=<?= $r['sale_id'] ?>" style="font-weight:600;"><?= e($r['memo_number']) ?></a></td>
              <td><?= e($r['customer_name']) ?></td>
              <td style="font-weight:700;"><?= formatMoney($r['total_return_amount']) ?></td>
              <td><?= formatMoney($r['refund_amount']) ?></td>
              <td style="color:var(--success);font-weight:700;"><?= formatMoney($r['due_reduced']) ?></td>
              <td><?= e($r['reason']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function lookupMemoForReturn() {
  const memoNo = document.getElementById('ret-memo-search').value.trim();
  if (!memoNo) return;

  const data = await apiRequest(`${BASE_URL}/ajax/search.php?q=${encodeURIComponent(memoNo)}`);
  if (data.success && data.results.memos.length > 0) {
    const memo = data.results.memos[0];
    document.getElementById('ret-sale-id').value = memo.id;
    document.getElementById('ret-disp-memo-title').textContent = `Return Items for Memo: ${memo.memo_number} (${memo.customer_name})`;
    
    // Fetch details
    const res = await fetch(`${BASE_URL}/modules/sales/memo-print.php?id=${memo.id}`);
    document.getElementById('return-form-container').style.display = 'block';
    
    // Render simple return row
    document.getElementById('ret-items-tbody').innerHTML = `
      <tr>
        <td style="font-weight:700;">All Memo Returned Items</td>
        <td>1 Lot</td>
        <td>৳${memo.grand_total}</td>
        <td>
          <input type="number" step="any" min="1" max="${memo.grand_total}" class="form-control form-control-sm" style="width:140px;" value="${memo.grand_total}" oninput="document.getElementById('ret-total-amount').value = this.value">
        </td>
      </tr>
    `;
    document.getElementById('ret-total-amount').value = memo.grand_total;
  } else {
    showToast('Memo not found.', 'error');
  }
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
