let currentLang = "ar";
let projectItems = [];
let lastResult = { foundations: null, columns: null, slabs: null };

function t(key) {
  const dict = translations[currentLang];
  return Object.prototype.hasOwnProperty.call(dict, key) ? dict[key] : key;
}

function fmt(n) {
  return n.toLocaleString(currentLang === "ar" ? "ar-EG" : "en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function applyTranslations() {
  document.querySelectorAll("[data-i18n]").forEach((el) => {
    const key = el.getAttribute("data-i18n");
    el.textContent = t(key);
  });
  document.documentElement.lang = currentLang;
  document.documentElement.dir = currentLang === "ar" ? "rtl" : "ltr";
  document.getElementById("lang-toggle").textContent =
    currentLang === "ar" ? "English" : "العربية";
  renderProjectTable();
}

function setupTabs() {
  const tabButtons = document.querySelectorAll(".tab-btn");
  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabButtons.forEach((b) => b.classList.remove("active"));
      document.querySelectorAll(".panel").forEach((p) => p.classList.remove("active"));
      btn.classList.add("active");
      document.getElementById(`panel-${btn.dataset.tab}`).classList.add("active");
    });
  });
}

function setupMixToggles() {
  ["f", "c", "s"].forEach((prefix) => {
    const select = document.getElementById(`${prefix}-mix`);
    const customBox = document.getElementById(`${prefix}-custom-mix`);
    select.addEventListener("change", () => {
      customBox.classList.toggle("hidden", select.value !== "custom");
    });
  });
}

function setupColumnShapeToggle() {
  const shapeSelect = document.getElementById("c-shape");
  const rectFields = document.getElementById("c-rect-fields");
  const circleFields = document.getElementById("c-circle-fields");
  shapeSelect.addEventListener("change", () => {
    const isCircle = shapeSelect.value === "circle";
    rectFields.classList.toggle("hidden", isCircle);
    circleFields.classList.toggle("hidden", !isCircle);
  });
}

function getMixRatio(prefix) {
  const select = document.getElementById(`${prefix}-mix`);
  if (select.value === "custom") {
    const cement = parseFloat(document.getElementById(`${prefix}-mix-cement`).value) || 0;
    const sand = parseFloat(document.getElementById(`${prefix}-mix-sand`).value) || 0;
    const gravel = parseFloat(document.getElementById(`${prefix}-mix-gravel`).value) || 0;
    return [cement, sand, gravel];
  }
  return select.value.split(",").map(Number);
}

function renderResult(containerId, mix, calcType) {
  const container = document.getElementById(containerId);
  container.innerHTML = `
    <div class="result-card">
      <div class="result-row"><span class="label">${t("result_volume")}</span><span class="value">${fmt(mix.wetVolume)} m³</span></div>
      <div class="result-row"><span class="label">${t("result_dry_volume")}</span><span class="value">${fmt(mix.dryVolume)} m³</span></div>
      <div class="result-row"><span class="label">${t("result_cement_bags")}</span><span class="value">${fmt(mix.cementBags)}</span></div>
      <div class="result-row"><span class="label">${t("result_cement_weight")}</span><span class="value">${fmt(mix.cementWeightKg)} kg</span></div>
      <div class="result-row"><span class="label">${t("result_sand")}</span><span class="value">${fmt(mix.sandVolume)} m³</span></div>
      <div class="result-row"><span class="label">${t("result_gravel")}</span><span class="value">${fmt(mix.gravelVolume)} m³</span></div>
    </div>
    <button class="add-project-btn" data-add="${calcType}">${t("add_to_project")}</button>
  `;
  container.querySelector("[data-add]").addEventListener("click", () => addToProject(calcType));
}

function showError(containerId) {
  document.getElementById(containerId).innerHTML = `<div class="result-card">${t("err_invalid")}</div>`;
}

function calculateFoundations() {
  const type = document.getElementById("foundation-type").value;
  const length = parseFloat(document.getElementById("f-length").value) || 0;
  const width = parseFloat(document.getElementById("f-width").value) || 0;
  const depth = parseFloat(document.getElementById("f-depth").value) || 0;
  const count = parseInt(document.getElementById("f-count").value, 10) || 1;

  if (length <= 0 || width <= 0 || depth <= 0 || count <= 0) {
    showError("result-foundations");
    return;
  }

  let volume;
  if (type === "strip") volume = stripFootingVolume(length, width, depth, count);
  else if (type === "isolated") volume = isolatedFootingVolume(length, width, depth, count);
  else volume = matFoundationVolume(length, width, depth);

  const [cement, sand, gravel] = getMixRatio("f");
  const mix = calculateMixQuantities(volume, cement, sand, gravel);
  lastResult.foundations = mix;
  renderResult("result-foundations", mix, "foundations");
}

function calculateColumns() {
  const shape = document.getElementById("c-shape").value;
  const height = parseFloat(document.getElementById("c-height").value) || 0;
  const count = parseInt(document.getElementById("c-count").value, 10) || 1;

  let volume;
  if (shape === "circle") {
    const diameter = parseFloat(document.getElementById("c-diameter").value) || 0;
    if (diameter <= 0 || height <= 0 || count <= 0) {
      showError("result-columns");
      return;
    }
    volume = circleColumnVolume(diameter, height, count);
  } else {
    const width = parseFloat(document.getElementById("c-width").value) || 0;
    const depth = parseFloat(document.getElementById("c-depth").value) || 0;
    if (width <= 0 || depth <= 0 || height <= 0 || count <= 0) {
      showError("result-columns");
      return;
    }
    volume = rectColumnVolume(width, depth, height, count);
  }

  const [cement, sand, gravel] = getMixRatio("c");
  const mix = calculateMixQuantities(volume, cement, sand, gravel);
  lastResult.columns = mix;
  renderResult("result-columns", mix, "columns");
}

function calculateSlabs() {
  const length = parseFloat(document.getElementById("s-length").value) || 0;
  const width = parseFloat(document.getElementById("s-width").value) || 0;
  const thickness = parseFloat(document.getElementById("s-thickness").value) || 0;
  const count = parseInt(document.getElementById("s-count").value, 10) || 1;

  if (length <= 0 || width <= 0 || thickness <= 0 || count <= 0) {
    showError("result-slabs");
    return;
  }

  const volume = slabVolume(length, width, thickness, count);
  const [cement, sand, gravel] = getMixRatio("s");
  const mix = calculateMixQuantities(volume, cement, sand, gravel);
  lastResult.slabs = mix;
  renderResult("result-slabs", mix, "slabs");
}

function addToProject(calcType) {
  const mix = lastResult[calcType];
  if (!mix) return;
  projectItems.push({
    type: calcType,
    volume: mix.wetVolume,
    cementBags: mix.cementBags,
    sand: mix.sandVolume,
    gravel: mix.gravelVolume,
  });
  renderProjectTable();
  document.querySelector('.tab-btn[data-tab="project"]').click();
}

function removeProjectItem(index) {
  projectItems.splice(index, 1);
  renderProjectTable();
}

function renderProjectTable() {
  const body = document.getElementById("project-body");
  body.innerHTML = "";

  let totals = { volume: 0, cementBags: 0, sand: 0, gravel: 0 };

  projectItems.forEach((item, index) => {
    totals.volume += item.volume;
    totals.cementBags += item.cementBags;
    totals.sand += item.sand;
    totals.gravel += item.gravel;

    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${t("item_" + item.type.slice(0, -1))}</td>
      <td>${fmt(item.volume)}</td>
      <td>${fmt(item.cementBags)}</td>
      <td>${fmt(item.sand)}</td>
      <td>${fmt(item.gravel)}</td>
      <td><button class="remove-row-btn" data-remove="${index}">${t("remove")}</button></td>
    `;
    body.appendChild(row);
  });

  body.querySelectorAll("[data-remove]").forEach((btn) => {
    btn.addEventListener("click", () => removeProjectItem(parseInt(btn.dataset.remove, 10)));
  });

  document.getElementById("total-volume").textContent = fmt(totals.volume);
  document.getElementById("total-cement").textContent = fmt(totals.cementBags);
  document.getElementById("total-sand").textContent = fmt(totals.sand);
  document.getElementById("total-gravel").textContent = fmt(totals.gravel);
}

function setupCalcButtons() {
  document.querySelectorAll(".calc-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const calcType = btn.dataset.calc;
      if (calcType === "foundations") calculateFoundations();
      else if (calcType === "columns") calculateColumns();
      else if (calcType === "slabs") calculateSlabs();
    });
  });
}

function setupLangToggle() {
  document.getElementById("lang-toggle").addEventListener("click", () => {
    currentLang = currentLang === "ar" ? "en" : "ar";
    applyTranslations();
  });
}

function setupClearProject() {
  document.getElementById("clear-project").addEventListener("click", () => {
    projectItems = [];
    renderProjectTable();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  setupTabs();
  setupMixToggles();
  setupColumnShapeToggle();
  setupCalcButtons();
  setupLangToggle();
  setupClearProject();
  applyTranslations();
});
