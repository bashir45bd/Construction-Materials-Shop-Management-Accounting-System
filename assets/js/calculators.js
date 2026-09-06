/**
 * Civil Engineering Calculators JavaScript
 * Construction Materials Shop Management & Accounting System
 */

document.addEventListener('DOMContentLoaded', () => {
  // Rod Weight Calculator Trigger
  const rodDia = document.getElementById('calc-rod-dia');
  const rodLen = document.getElementById('calc-rod-len');
  const rodUnit = document.getElementById('calc-rod-unit');
  const rodQty = document.getElementById('calc-rod-qty');

  if (rodDia && rodLen) {
    [rodDia, rodLen, rodUnit, rodQty].forEach(el => el && el.addEventListener('input', calculateRodWeightUI));
    calculateRodWeightUI();
  }

  // Brick Calculator Trigger
  const brkLen = document.getElementById('calc-brick-len');
  const brkHgt = document.getElementById('calc-brick-hgt');
  const brkThk = document.getElementById('calc-brick-thk');

  if (brkLen && brkHgt) {
    [brkLen, brkHgt, brkThk].forEach(el => el && el.addEventListener('input', calculateBricksUI));
    calculateBricksUI();
  }

  // Concrete Calculator Trigger
  const conLen = document.getElementById('calc-con-len');
  const conWid = document.getElementById('calc-con-wid');
  const conDep = document.getElementById('calc-con-dep');
  const conMix = document.getElementById('calc-con-mix');

  if (conLen && conWid && conDep) {
    [conLen, conWid, conDep, conMix].forEach(el => el && el.addEventListener('input', calculateConcreteUI));
    calculateConcreteUI();
  }
});

/**
 * 1. Rod Weight Calculation
 */
function calculateRodWeightUI() {
  const dia = parseFloat(document.getElementById('calc-rod-dia')?.value) || 0;
  const len = parseFloat(document.getElementById('calc-rod-len')?.value) || 0;
  const unit = document.getElementById('calc-rod-unit')?.value || 'meter';
  const qty = parseInt(document.getElementById('calc-rod-qty')?.value) || 1;

  if (dia <= 0 || len <= 0) return;

  const lenInMeters = (unit === 'feet') ? (len * 0.3048) : len;
  const unitWeightPerMeter = (dia * dia) / 162.2;
  const totalWeightKg = unitWeightPerMeter * lenInMeters * qty;
  const totalWeightTon = totalWeightKg / 1000.0;

  setCalcResult('rod-res-unit-wt', unitWeightPerMeter.toFixed(3) + ' KG/m');
  setCalcResult('rod-res-total-kg', totalWeightKg.toFixed(2) + ' KG');
  setCalcResult('rod-res-total-ton', totalWeightTon.toFixed(4) + ' Ton');
  setCalcResult('rod-res-total-maund', (totalWeightKg / 40.0).toFixed(2) + ' Mon (মণ)');
}

/**
 * 2. Brick Masonry Calculation
 */
function calculateBricksUI() {
  const len = parseFloat(document.getElementById('calc-brick-len')?.value) || 0;
  const hgt = parseFloat(document.getElementById('calc-brick-hgt')?.value) || 0;
  const thk = document.getElementById('calc-brick-thk')?.value || '5_inch';

  if (len <= 0 || hgt <= 0) return;

  const areaSqft = len * hgt;
  const bricksPerSqft = (thk === '10_inch') ? 10.0 : 5.0;
  const totalBricks = Math.ceil(areaSqft * bricksPerSqft);
  const withWastage = Math.ceil(totalBricks * 1.05);

  const wetMortarCft = (thk === '10_inch') ? (areaSqft * 0.22) : (areaSqft * 0.10);
  const dryMortarCft = wetMortarCft * 1.33;
  const cementBags = Math.max(1, Math.ceil((dryMortarCft / 7.0) / 1.25));
  const sandCft = ((dryMortarCft / 7.0) * 6.0).toFixed(1);

  setCalcResult('brick-res-area', areaSqft.toFixed(2) + ' Sq.Ft');
  setCalcResult('brick-res-bricks', totalBricks.toLocaleString() + ' Pcs');
  setCalcResult('brick-res-wastage', withWastage.toLocaleString() + ' Pcs (+5%)');
  setCalcResult('brick-res-cement', cementBags + ' Bags');
  setCalcResult('brick-res-sand', sandCft + ' CFT');
}

/**
 * 3. Concrete RCC Slab/Beam/Casting Calculation
 */
function calculateConcreteUI() {
  const len = parseFloat(document.getElementById('calc-con-len')?.value) || 0;
  const wid = parseFloat(document.getElementById('calc-con-wid')?.value) || 0;
  const dep = parseFloat(document.getElementById('calc-con-dep')?.value) || 0;
  const mix = document.getElementById('calc-con-mix')?.value || '1:2:4';

  if (len <= 0 || wid <= 0 || dep <= 0) return;

  const depFt = dep / 12.0;
  const wetVolumeCft = len * wid * depFt;
  const dryVolumeCft = wetVolumeCft * 1.54;

  const parts = mix.split(':').map(Number);
  const ratioSum = parts[0] + parts[1] + parts[2];

  const cementCft = (dryVolumeCft * parts[0]) / ratioSum;
  const cementBags = Math.ceil(cementCft / 1.25);
  const sandCft = ((dryVolumeCft * parts[1]) / ratioSum).toFixed(1);
  const stoneCft = ((dryVolumeCft * parts[2]) / ratioSum).toFixed(1);

  setCalcResult('con-res-wet-vol', wetVolumeCft.toFixed(2) + ' CFT');
  setCalcResult('con-res-dry-vol', dryVolumeCft.toFixed(2) + ' CFT');
  setCalcResult('con-res-cement', cementBags + ' Bags');
  setCalcResult('con-res-sand', sandCft + ' CFT');
  setCalcResult('con-res-stone', stoneCft + ' CFT');
}

function setCalcResult(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}
