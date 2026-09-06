/**
 * New Memo & Fast Sales Point of Sale Controller
 * Construction Materials Shop Management & Accounting System
 */

let selectedCustomer = null;
let cartItems = [];
let discountType = 'fixed';
let discountValue = 0.0;
let paidAmount = 0.0;
let useAdvance = false;

document.addEventListener('DOMContentLoaded', () => {
  initCustomerSearch();
  initProductSearch();
  renderCart();
  calculateTotals();

  // Discount Type Switcher
  const discTypeSelect = document.getElementById('memo-discount-type');
  if (discTypeSelect) {
    discTypeSelect.addEventListener('change', (e) => {
      discountType = e.target.value;
      calculateTotals();
    });
  }

  // Discount Value Input
  const discValInput = document.getElementById('memo-discount-val');
  if (discValInput) {
    discValInput.addEventListener('input', (e) => {
      discountValue = parseFloat(e.target.value) || 0.0;
      calculateTotals();
    });
  }

  // Paid Now Input
  const paidInput = document.getElementById('memo-paid-amount');
  if (paidInput) {
    paidInput.addEventListener('input', (e) => {
      paidAmount = parseFloat(e.target.value) || 0.0;
      calculateTotals();
    });
  }

  // Advance Checkbox
  const advCheckbox = document.getElementById('memo-use-advance');
  if (advCheckbox) {
    advCheckbox.addEventListener('change', (e) => {
      useAdvance = e.target.checked;
      calculateTotals();
    });
  }
});

/**
 * Customer Search & Select
 */
function initCustomerSearch() {
  const input = document.getElementById('customer-search-input');
  const resultsBox = document.getElementById('customer-search-results');
  if (!input || !resultsBox) return;

  let debounceTimer;
  input.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    const q = e.target.value.trim();
    if (q.length < 2) {
      resultsBox.style.display = 'none';
      return;
    }

    debounceTimer = setTimeout(async () => {
      const data = await apiRequest(`${BASE_URL}/ajax/customers.php?action=search&q=${encodeURIComponent(q)}`);
      if (data.success && data.customers.length > 0) {
        resultsBox.innerHTML = data.customers.map(c => `
          <div class="customer-result-item" onclick="selectCustomer(${JSON.stringify(c).replace(/"/g, '&quot;')})">
            <div style="font-weight:700;">${c.name} (${c.customer_code})</div>
            <div style="font-size:0.8rem;color:var(--text-secondary);">${c.mobile} | ${c.address || 'No Address'}</div>
            <div style="font-size:0.8rem;margin-top:2px;">
              <span style="color:var(--danger);font-weight:600;">Due: ৳${parseFloat(c.current_due).toLocaleString()}</span> | 
              <span style="color:var(--success);font-weight:600;">Advance: ৳${parseFloat(c.current_advance).toLocaleString()}</span>
            </div>
          </div>
        `).join('');
        resultsBox.style.display = 'block';
      } else {
        resultsBox.innerHTML = '<div style="padding:10px;text-align:center;color:var(--text-muted);">No matching customers.</div>';
        resultsBox.style.display = 'block';
      }
    }, 250);
  });
}

function selectCustomer(cust) {
  selectedCustomer = cust;
  document.getElementById('customer-search-results').style.display = 'none';
  document.getElementById('customer-search-input').value = `${cust.name} (${cust.mobile})`;

  // Update selected customer display card
  const card = document.getElementById('selected-customer-card');
  if (card) {
    document.getElementById('cust-disp-name').textContent = cust.name;
    document.getElementById('cust-disp-mobile').textContent = cust.mobile;
    document.getElementById('cust-disp-address').textContent = cust.address || 'No Address';
    document.getElementById('cust-disp-due').textContent = '৳' + parseFloat(cust.current_due).toLocaleString();
    document.getElementById('cust-disp-advance').textContent = '৳' + parseFloat(cust.current_advance).toLocaleString();
    card.style.display = 'block';
  }

  // Update Advance Checkbox Availability
  const advContainer = document.getElementById('advance-available-container');
  const advCheckbox = document.getElementById('memo-use-advance');
  const advLabel = document.getElementById('advance-available-label');

  if (advContainer && parseFloat(cust.current_advance) > 0) {
    advContainer.style.display = 'flex';
    advLabel.textContent = `Use Available Advance (৳${parseFloat(cust.current_advance).toLocaleString()})`;
    advCheckbox.checked = true;
    useAdvance = true;
  } else if (advContainer) {
    advContainer.style.display = 'none';
    advCheckbox.checked = false;
    useAdvance = false;
  }

  calculateTotals();
  showToast(`Customer "${cust.name}" selected.`, 'success');
}

/**
 * Add Product to Memo Cart
 */
function addProductToCart(product) {
  // Check if product already in cart
  const existing = cartItems.find(item => item.product_id === product.id);
  if (existing) {
    existing.quantity += 1;
    existing.line_total = (existing.quantity * existing.unit_price) - existing.discount_amount;
  } else {
    cartItems.push({
      product_id: product.id,
      name: product.name,
      sku: product.sku,
      unit_id: product.base_unit_id,
      unit_name: product.unit_symbol || product.unit_name || 'Unit',
      quantity: 1,
      unit_price: parseFloat(product.selling_price) || 0.0,
      selling_price: parseFloat(product.selling_price) || 0.0,
      min_selling_price: parseFloat(product.min_selling_price) || 0.0,
      discount_amount: 0.0,
      line_total: parseFloat(product.selling_price) || 0.0,
      current_stock: parseFloat(product.current_stock) || 0.0
    });
  }

  renderCart();
  calculateTotals();
}

/**
 * Render Sequential Cart Table
 */
function renderCart() {
  const tbody = document.getElementById('memo-cart-tbody');
  if (!tbody) return;

  if (cartItems.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
          <i class="fa fa-shopping-cart" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>
          Cart is empty. Select products from the list or top items.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = cartItems.map((item, index) => {
    const serial = String(index + 1).padStart(2, '0');
    const minRate = Math.min(item.min_selling_price, item.selling_price);
    const maxRate = Math.max(item.min_selling_price, item.selling_price);
    return `
      <tr>
        <td style="font-weight:700;color:var(--text-muted);">${serial}</td>
        <td>
          <div style="font-weight:700;">${item.name}</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">Stock: ${item.current_stock} ${item.unit_name}</div>
        </td>
        <td style="width:110px;">
          <input type="number" step="any" min="0.01" class="form-control form-control-sm" 
                 value="${item.quantity}" onchange="updateCartQuantity(${index}, this.value)">
        </td>
        <td style="font-size:0.85rem;color:var(--text-secondary);">${item.unit_name}</td>
        <td style="width:120px;">
          <input type="number" step="0.01" min="${minRate}" max="${maxRate}" class="form-control form-control-sm" 
                 value="${item.unit_price}" onchange="updateCartPrice(${index}, this.value)">
          <small style="display:block;margin-top:3px;color:var(--text-muted);font-size:0.68rem;white-space:nowrap;">৳${minRate.toFixed(2)} – ৳${maxRate.toFixed(2)}</small>
        </td>
        <td style="font-weight:700;text-align:right;">৳${item.line_total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td style="text-align:center;">
          <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart(${index})" title="Remove item">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function updateCartQuantity(index, val) {
  const qty = parseFloat(val) || 1;
  cartItems[index].quantity = qty;
  cartItems[index].line_total = (qty * cartItems[index].unit_price) - cartItems[index].discount_amount;
  renderCart();
  calculateTotals();
}

function updateCartPrice(index, val) {
  const requestedPrice = parseFloat(val);
  const minRate = Math.min(cartItems[index].min_selling_price, cartItems[index].selling_price);
  const maxRate = Math.max(cartItems[index].min_selling_price, cartItems[index].selling_price);
  const price = Number.isFinite(requestedPrice) ? Math.min(maxRate, Math.max(minRate, requestedPrice)) : maxRate;
  if (!Number.isFinite(requestedPrice) || requestedPrice < minRate || requestedPrice > maxRate) {
    showToast(`Rate must be between ৳${minRate.toFixed(2)} and ৳${maxRate.toFixed(2)}.`, 'warning');
  }
  cartItems[index].unit_price = price;
  cartItems[index].line_total = (cartItems[index].quantity * price) - cartItems[index].discount_amount;
  renderCart();
  calculateTotals();
}

function removeFromCart(index) {
  cartItems.splice(index, 1);
  renderCart();
  calculateTotals();
}

/**
 * Real-time Financial Calculations
 */
function calculateTotals() {
  const subtotal = cartItems.reduce((sum, item) => sum + item.line_total, 0.0);
  
  let calculatedDiscount = 0.0;
  if (discountType === 'percentage') {
    calculatedDiscount = (subtotal * discountValue) / 100.0;
  } else {
    calculatedDiscount = discountValue;
  }
  calculatedDiscount = Math.min(subtotal, calculatedDiscount);

  const grandTotal = Math.max(0.0, subtotal - calculatedDiscount);

  // Advance Calculation
  const availableAdvance = selectedCustomer ? parseFloat(selectedCustomer.current_advance) : 0.0;
  let appliedAdvance = 0.0;

  if (useAdvance && availableAdvance > 0) {
    const maxNeeded = Math.max(0.0, grandTotal - paidAmount);
    appliedAdvance = Math.min(availableAdvance, maxNeeded);
  }

  const totalDeductions = paidAmount + appliedAdvance;
  const dueAmount = Math.max(0.0, grandTotal - totalDeductions);
  const overpayment = Math.max(0.0, totalDeductions - grandTotal);

  // Update UI Elements
  setElementText('disp-subtotal', '৳' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
  setElementText('disp-discount', '৳' + calculatedDiscount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
  setElementText('disp-grand-total', '৳' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
  setElementText('disp-advance-used', '৳' + appliedAdvance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
  setElementText('disp-due-amount', '৳' + dueAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

  const overpayRow = document.getElementById('disp-overpayment-row');
  if (overpayRow) {
    if (overpayment > 0) {
      overpayRow.style.display = 'flex';
      setElementText('disp-overpayment', '৳' + overpayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    } else {
      overpayRow.style.display = 'none';
    }
  }
}

function setElementText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

/**
 * Submit and Save Memo
 */
async function submitMemo(autoPrint = true) {
  if (!selectedCustomer) {
    showToast('Please select or create a customer before finalizing the memo.', 'warning');
    return;
  }

  if (cartItems.length === 0) {
    showToast('Cart is empty. Please add at least one product.', 'warning');
    return;
  }

  const subtotal = cartItems.reduce((sum, item) => sum + item.line_total, 0.0);
  let calculatedDiscount = (discountType === 'percentage') ? (subtotal * discountValue) / 100.0 : discountValue;
  const grandTotal = Math.max(0.0, subtotal - calculatedDiscount);

  const payload = {
    customer_id: selectedCustomer.id,
    items: cartItems,
    subtotal: subtotal,
    discount_type: discountType,
    discount_value: discountValue,
    discount_amount: calculatedDiscount,
    grand_total: grandTotal,
    paid_amount: paidAmount,
    use_advance: useAdvance,
    account_type: 'cash',
    account_id: 1,
    notes: document.getElementById('memo-notes')?.value || '',
    sale_date: document.getElementById('memo-date')?.value || ''
  };

  const saveBtn = document.getElementById('btn-save-memo');
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving Memo...';
  }

  try {
    const data = await apiRequest(`${BASE_URL}/ajax/memo.php?action=create`, 'POST', payload);
    if (data.success) {
      showToast(`Memo ${data.memo_number} created successfully!`, 'success');
      
      // Redirect to two-copy print preview
      window.location.href = `${BASE_URL}/modules/sales/memo-print.php?id=${data.sale_id}${autoPrint ? '&autoprint=1' : ''}`;
    } else {
      showToast(data.message || 'Failed to create sales memo.', 'error');
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fa fa-print"></i> Save & Print Memo';
      }
    }
  } catch (error) {
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fa fa-print"></i> Save & Print Memo';
    }
  }
}

/**
 * Product Live Search for POS
 */
function initProductSearch() {
  const input = document.getElementById('pos-product-search');
  const categoryFilter = document.getElementById('pos-category-filter');
  const brandFilter = document.getElementById('pos-brand-filter');
  const container = document.getElementById('pos-products-grid');
  if (!input || !container) return;

  let timer;
  let activeRequest;

  const loadProducts = async () => {
    const q = input.value.trim();
    const categoryId = categoryFilter?.value || '0';
    const brandId = brandFilter?.value || '0';

    if (activeRequest) activeRequest.abort();
    activeRequest = new AbortController();
    const params = new URLSearchParams({ action: 'search', q, category_id: categoryId, brand_id: brandId });
    container.style.opacity = '0.55';
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const response = await fetch(`${BASE_URL}/ajax/products.php?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        signal: activeRequest.signal
      });
      const data = await response.json();
      if (data.success) renderProductCards(container, data.products);
    } catch (error) {
      if (error.name !== 'AbortError') {
        showToast('Could not search products. Please try again.', 'error');
      }
    } finally {
      container.style.opacity = '1';
    }
  };

  input.addEventListener('input', (e) => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      await loadProducts();
    }, 250);
  });

  [categoryFilter, brandFilter].filter(Boolean).forEach((filter) => {
    filter.addEventListener('change', loadProducts);
  });
}

function renderProductCards(container, products) {
  if (!products || products.length === 0) {
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted);">No products found.</div>';
    return;
  }

  container.innerHTML = products.map(p => `
    <div class="card" style="margin:0;cursor:pointer;padding:1rem;transition:var(--transition);" onclick="addProductToCart(${JSON.stringify(p).replace(/"/g, '&quot;')})">
      <div style="font-weight:700;font-size:0.95rem;">${p.name}</div>
      <div style="font-size:0.8rem;color:var(--text-secondary);">${p.brand_name || ''} | ${p.category_name || ''}</div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;">
        <span style="font-weight:800;color:var(--primary);font-size:1.05rem;">৳${parseFloat(p.selling_price).toLocaleString()} <small style="font-size:0.75rem;font-weight:400;">/${p.unit_symbol || 'Unit'}</small></span>
        <span class="badge ${parseFloat(p.current_stock) <= parseFloat(p.min_stock) ? 'badge-danger' : 'badge-success'}">
          Stock: ${parseFloat(p.current_stock)}
        </span>
      </div>
    </div>
  `).join('');
}
