<?php
/**
 * Civil Engineering & Construction Material Calculators
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;

Auth::requireLogin();
Auth::requirePermission('calculators.use');

$pageTitle = 'Construction Calculators (নির্মাণ সামগ্রী ক্যালকুলেটর)';
$activeMenu = 'calculators';
$activeSub = 'calculators';
$extraScripts = [BASE_URL . '/assets/js/calculators.js?v=2.0'];

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div style="margin-bottom:1.5rem;">
  <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-0.025em;"><i class="fa fa-calculator"></i> Civil Engineering Construction Calculators</h1>
  <p style="color:var(--text-secondary);font-size:0.85rem;">Instant material estimations for customers, site engineers and contractors. (Does NOT alter database stock).</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:1.5rem;">
  <!-- 1. ROD WEIGHT CALCULATOR -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-bars-staggered"></i> Rod (TMT Rebar) Weight Calculator</div>
    </div>

    <div class="form-group">
      <label class="form-label">Rod Diameter (রডের সাইজ / সুতা) *</label>
      <select id="calc-rod-dia" class="form-control">
        <option value="8">8 mm (2.5 Suta / ২.৫ সুতা)</option>
        <option value="10">10 mm (3 Suta / ৩ সুতা)</option>
        <option value="12" selected>12 mm (4 Suta / ৪ সুতা)</option>
        <option value="16">16 mm (5 Suta / ৫ সুতা)</option>
        <option value="20">20 mm (6 Suta / ৬ সুতা)</option>
        <option value="22">22 mm (7 Suta / ৭ সুতা)</option>
        <option value="25">25 mm (8 Suta / ৮ সুতা)</option>
        <option value="32">32 mm (10 Suta / ১০ সুতা)</option>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
      <div class="form-group">
        <label class="form-label">Length (দৈর্ঘ্য) *</label>
        <input type="number" step="any" min="1" id="calc-rod-len" class="form-control" value="40">
      </div>
      <div class="form-group">
        <label class="form-label">Length Unit</label>
        <select id="calc-rod-unit" class="form-control">
          <option value="feet" selected>Feet (ফিট - স্ট্যান্ডার্ড ১২ মিটার/৪০ ফিট)</option>
          <option value="meter">Meter (মিটার)</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Quantity of Rods (রডের পিস সংখ্যা)</label>
      <input type="number" min="1" id="calc-rod-qty" class="form-control" value="10">
    </div>

    <!-- Output Box -->
    <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;margin-top:1rem;">
      <div style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.5rem;">Calculated Weight Result:</div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Unit Weight:</span>
        <span style="font-weight:700;" id="rod-res-unit-wt">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;font-size:1.1rem;font-weight:800;color:var(--primary);">
        <span>Total Weight (KG):</span>
        <span id="rod-res-total-kg">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Total in Metric Ton:</span>
        <span style="font-weight:700;" id="rod-res-total-ton">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span>Total in Mon (মণ):</span>
        <span style="font-weight:700;" id="rod-res-total-maund">-</span>
      </div>
    </div>
  </div>

  <!-- 2. BRICK MASONRY CALCULATOR -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-cubes"></i> Brick Wall Masonry Calculator</div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
      <div class="form-group">
        <label class="form-label">Wall Length (Feet)</label>
        <input type="number" step="any" min="1" id="calc-brick-len" class="form-control" value="20">
      </div>
      <div class="form-group">
        <label class="form-label">Wall Height (Feet)</label>
        <input type="number" step="any" min="1" id="calc-brick-hgt" class="form-control" value="10">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Wall Thickness (গাথুনির পুরুত্ব) *</label>
      <select id="calc-brick-thk" class="form-control">
        <option value="5_inch" selected>5 Inch Wall (৫ ইঞ্চি গাথুনি)</option>
        <option value="10_inch">10 Inch Wall (১০ ইঞ্চি গাথুনি)</option>
      </select>
    </div>

    <!-- Output Box -->
    <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;margin-top:2.25rem;">
      <div style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.5rem;">Masonry Material Requirements:</div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Wall Surface Area:</span>
        <span style="font-weight:700;" id="brick-res-area">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;font-size:1.1rem;font-weight:800;color:var(--primary);">
        <span>Required Bricks (ইট):</span>
        <span id="brick-res-bricks">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>With 5% Wastage:</span>
        <span style="font-weight:700;" id="brick-res-wastage">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Cement for Mortar (সিমেন্ট):</span>
        <span style="font-weight:700;" id="brick-res-cement">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span>Sand for Mortar (বালু):</span>
        <span style="font-weight:700;" id="brick-res-sand">-</span>
      </div>
    </div>
  </div>

  <!-- 3. CONCRETE RCC CASTING CALCULATOR -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-shapes"></i> Concrete Slab / Beam / Casting Calculator</div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
      <div class="form-group">
        <label class="form-label">Length (Feet)</label>
        <input type="number" step="any" min="1" id="calc-con-len" class="form-control" value="30">
      </div>
      <div class="form-group">
        <label class="form-label">Width (Feet)</label>
        <input type="number" step="any" min="1" id="calc-con-wid" class="form-control" value="20">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
      <div class="form-group">
        <label class="form-label">Thickness / Depth (Inch)</label>
        <input type="number" step="any" min="1" id="calc-con-dep" class="form-control" value="5">
      </div>
      <div class="form-group">
        <label class="form-label">Mix Ratio (অনুপাত)</label>
        <select id="calc-con-mix" class="form-control">
          <option value="1:2:4" selected>1 : 2 : 4 (Standard RCC)</option>
          <option value="1:1.5:3">1 : 1.5 : 3 (High Strength M20)</option>
          <option value="1:3:6">1 : 3 : 6 (PCC / Flooring)</option>
        </select>
      </div>
    </div>

    <!-- Output Box -->
    <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;margin-top:1rem;">
      <div style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.5rem;">Casting Mix Requirements:</div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Dry Volume (1.54 Coeff):</span>
        <span style="font-weight:700;" id="con-res-dry-vol">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;font-size:1.1rem;font-weight:800;color:var(--primary);">
        <span>Cement (সিমেন্ট):</span>
        <span id="con-res-cement">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem;">
        <span>Sand / Balu (বালু):</span>
        <span style="font-weight:700;" id="con-res-sand">-</span>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span>Stone / Khoa (পাথর/খোয়া):</span>
        <span style="font-weight:700;" id="con-res-stone">-</span>
      </div>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
