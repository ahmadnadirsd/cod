<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Database.php';

$config = require __DIR__ . '/../config/config.php';
$db = new Database($config['db']);
$pdo = $db->pdo();

$view = $_GET['view'] ?? 'dashboard';

function redirectTo(string $view): void
{
    header('Location: ?view=' . urlencode($view));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_building':
            $stmt = $pdo->prepare('INSERT INTO buildings (name, address) VALUES (:name, :address)');
            $stmt->execute([
                ':name' => trim((string) $_POST['name']),
                ':address' => trim((string) $_POST['address']),
            ]);
            redirectTo('buildings');
            break;

        case 'create_unit':
            $stmt = $pdo->prepare('INSERT INTO units (building_id, unit_number, unit_type, status, monthly_rent) VALUES (:building_id, :unit_number, :unit_type, :status, :monthly_rent)');
            $stmt->execute([
                ':building_id' => (int) $_POST['building_id'],
                ':unit_number' => trim((string) $_POST['unit_number']),
                ':unit_type' => (string) $_POST['unit_type'],
                ':status' => (string) $_POST['status'],
                ':monthly_rent' => (float) $_POST['monthly_rent'],
            ]);
            redirectTo('units');
            break;

        case 'create_tenant':
            $stmt = $pdo->prepare('INSERT INTO tenants (full_name, phone) VALUES (:full_name, :phone)');
            $stmt->execute([
                ':full_name' => trim((string) $_POST['full_name']),
                ':phone' => trim((string) $_POST['phone']),
            ]);
            redirectTo('tenants');
            break;

        case 'create_lease':
            $stmt = $pdo->prepare('INSERT INTO leases (unit_id, tenant_id, start_date, end_date, monthly_rent, status) VALUES (:unit_id, :tenant_id, :start_date, :end_date, :monthly_rent, :status)');
            $stmt->execute([
                ':unit_id' => (int) $_POST['unit_id'],
                ':tenant_id' => (int) $_POST['tenant_id'],
                ':start_date' => (string) $_POST['start_date'],
                ':end_date' => (string) $_POST['end_date'],
                ':monthly_rent' => (float) $_POST['monthly_rent'],
                ':status' => 'active',
            ]);

            $update = $pdo->prepare('UPDATE units SET status = :status WHERE id = :id');
            $update->execute([':status' => 'occupied', ':id' => (int) $_POST['unit_id']]);
            redirectTo('leases');
            break;

        case 'create_invoice':
            $stmt = $pdo->prepare('INSERT INTO invoices (lease_id, due_date, amount, status) VALUES (:lease_id, :due_date, :amount, :status)');
            $stmt->execute([
                ':lease_id' => (int) $_POST['lease_id'],
                ':due_date' => (string) $_POST['due_date'],
                ':amount' => (float) $_POST['amount'],
                ':status' => (string) $_POST['status'],
            ]);
            redirectTo('invoices');
            break;

        case 'create_expense':
            $stmt = $pdo->prepare('INSERT INTO expenses (building_id, category, amount, expense_date, notes) VALUES (:building_id, :category, :amount, :expense_date, :notes)');
            $stmt->execute([
                ':building_id' => (int) $_POST['building_id'],
                ':category' => trim((string) $_POST['category']),
                ':amount' => (float) $_POST['amount'],
                ':expense_date' => (string) $_POST['expense_date'],
                ':notes' => trim((string) $_POST['notes']),
            ]);
            redirectTo('expenses');
            break;
    }
}

$totals = [
    'income' => (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status = 'paid'")->fetchColumn(),
    'expenses' => (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn(),
    'units' => (int) $pdo->query('SELECT COUNT(*) FROM units')->fetchColumn(),
    'vacant_units' => (int) $pdo->query("SELECT COUNT(*) FROM units WHERE status = 'vacant'")->fetchColumn(),
];
$totals['profit'] = $totals['income'] - $totals['expenses'];

$buildings = $pdo->query('SELECT * FROM buildings ORDER BY id DESC')->fetchAll();
$units = $pdo->query('SELECT u.*, b.name AS building_name FROM units u JOIN buildings b ON b.id = u.building_id ORDER BY u.id DESC')->fetchAll();
$tenants = $pdo->query('SELECT * FROM tenants ORDER BY id DESC')->fetchAll();
$leases = $pdo->query('SELECT l.*, u.unit_number, u.unit_type, t.full_name FROM leases l JOIN units u ON u.id = l.unit_id JOIN tenants t ON t.id = l.tenant_id ORDER BY l.id DESC')->fetchAll();
$invoices = $pdo->query('SELECT i.*, l.unit_id, u.unit_number, u.unit_type, t.full_name FROM invoices i JOIN leases l ON l.id = i.lease_id JOIN units u ON u.id = l.unit_id JOIN tenants t ON t.id = l.tenant_id ORDER BY i.id DESC')->fetchAll();
$expenses = $pdo->query('SELECT e.*, b.name AS building_name FROM expenses e JOIN buildings b ON b.id = e.building_id ORDER BY e.id DESC')->fetchAll();

function navLink(string $id, string $label, string $view): string
{
    $active = $id === $view ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-200';
    return "<a class=\"block px-3 py-2 rounded-lg {$active}\" href=\"?view={$id}\">{$label}</a>";
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة العقارات</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-7xl mx-auto p-4 md:p-8">
    <div class="grid md:grid-cols-[220px_1fr] gap-6">
        <aside class="bg-white rounded-2xl p-4 shadow">
            <h1 class="font-bold text-lg mb-4">نظام إدارة العقارات</h1>
            <nav class="space-y-1 text-sm">
                <?= navLink('dashboard', 'لوحة التحكم', $view); ?>
                <?= navLink('buildings', 'البنايات', $view); ?>
                <?= navLink('units', 'الوحدات (شقق/محلات/مخازن)', $view); ?>
                <?= navLink('tenants', 'المستأجرون', $view); ?>
                <?= navLink('leases', 'العقود', $view); ?>
                <?= navLink('invoices', 'الفواتير', $view); ?>
                <?= navLink('expenses', 'المصروفات', $view); ?>
            </nav>
        </aside>

        <main class="space-y-6">
            <?php if ($view === 'dashboard'): ?>
                <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <article class="bg-white p-4 rounded-2xl shadow"><p class="text-sm text-slate-500">الإيرادات المدفوعة</p><p class="text-2xl font-bold"><?= number_format($totals['income'], 2) ?></p></article>
                    <article class="bg-white p-4 rounded-2xl shadow"><p class="text-sm text-slate-500">إجمالي المصروفات</p><p class="text-2xl font-bold"><?= number_format($totals['expenses'], 2) ?></p></article>
                    <article class="bg-white p-4 rounded-2xl shadow"><p class="text-sm text-slate-500">صافي الربح</p><p class="text-2xl font-bold"><?= number_format($totals['profit'], 2) ?></p></article>
                    <article class="bg-white p-4 rounded-2xl shadow"><p class="text-sm text-slate-500">الوحدات الخالية</p><p class="text-2xl font-bold"><?= $totals['vacant_units'] ?> / <?= $totals['units'] ?></p></article>
                </section>
            <?php endif; ?>

            <?php if ($view === 'buildings'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة بناية</h2>
                    <form method="post" class="grid md:grid-cols-3 gap-3">
                        <input type="hidden" name="action" value="create_building">
                        <input name="name" required placeholder="اسم البناية" class="border rounded-lg px-3 py-2">
                        <input name="address" required placeholder="العنوان" class="border rounded-lg px-3 py-2">
                        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>الاسم</th><th>العنوان</th></tr></thead>
                        <tbody>
                        <?php foreach ($buildings as $building): ?>
                            <tr class="border-b"><td class="py-2"><?= (int) $building['id'] ?></td><td><?= htmlspecialchars($building['name']) ?></td><td><?= htmlspecialchars($building['address']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

            <?php if ($view === 'units'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة وحدة</h2>
                    <form method="post" class="grid md:grid-cols-5 gap-3">
                        <input type="hidden" name="action" value="create_unit">
                        <select name="building_id" class="border rounded-lg px-3 py-2" required>
                            <option value="">اختر البناية</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?= (int) $building['id'] ?>"><?= htmlspecialchars($building['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="unit_number" required placeholder="رقم الوحدة" class="border rounded-lg px-3 py-2">
                        <select name="unit_type" class="border rounded-lg px-3 py-2" required>
                            <option value="apartment">شقة</option>
                            <option value="shop">محل</option>
                            <option value="warehouse">مخزن</option>
                        </select>
                        <select name="status" class="border rounded-lg px-3 py-2" required>
                            <option value="vacant">خالية</option>
                            <option value="occupied">مؤجرة</option>
                        </select>
                        <input name="monthly_rent" type="number" step="0.01" required placeholder="الإيجار الشهري" class="border rounded-lg px-3 py-2">
                        <button class="md:col-span-5 bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ الوحدة</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>البناية</th><th>الوحدة</th><th>النوع</th><th>الحالة</th><th>الإيجار</th></tr></thead>
                        <tbody>
                        <?php foreach ($units as $unit): ?>
                            <tr class="border-b">
                                <td class="py-2"><?= (int) $unit['id'] ?></td>
                                <td><?= htmlspecialchars($unit['building_name']) ?></td>
                                <td><?= htmlspecialchars($unit['unit_number']) ?></td>
                                <td><?= htmlspecialchars($unit['unit_type']) ?></td>
                                <td><?= htmlspecialchars($unit['status']) ?></td>
                                <td><?= number_format((float) $unit['monthly_rent'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

            <?php if ($view === 'tenants'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة مستأجر</h2>
                    <form method="post" class="grid md:grid-cols-3 gap-3">
                        <input type="hidden" name="action" value="create_tenant">
                        <input name="full_name" required placeholder="الاسم" class="border rounded-lg px-3 py-2">
                        <input name="phone" required placeholder="رقم الجوال" class="border rounded-lg px-3 py-2">
                        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>الاسم</th><th>الهاتف</th></tr></thead>
                        <tbody><?php foreach ($tenants as $tenant): ?><tr class="border-b"><td class="py-2"><?= (int) $tenant['id'] ?></td><td><?= htmlspecialchars($tenant['full_name']) ?></td><td><?= htmlspecialchars($tenant['phone']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </section>
            <?php endif; ?>

            <?php if ($view === 'leases'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة عقد</h2>
                    <form method="post" class="grid md:grid-cols-5 gap-3">
                        <input type="hidden" name="action" value="create_lease">
                        <select name="unit_id" class="border rounded-lg px-3 py-2" required>
                            <option value="">الوحدة</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= (int) $unit['id'] ?>"><?= htmlspecialchars($unit['building_name'] . ' - ' . $unit['unit_number'] . ' (' . $unit['unit_type'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tenant_id" class="border rounded-lg px-3 py-2" required>
                            <option value="">المستأجر</option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?= (int) $tenant['id'] ?>"><?= htmlspecialchars($tenant['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="start_date" class="border rounded-lg px-3 py-2" required>
                        <input type="date" name="end_date" class="border rounded-lg px-3 py-2" required>
                        <input type="number" step="0.01" name="monthly_rent" placeholder="الإيجار" class="border rounded-lg px-3 py-2" required>
                        <button class="md:col-span-5 bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ العقد</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>المستأجر</th><th>الوحدة</th><th>الفترة</th><th>الحالة</th></tr></thead>
                        <tbody><?php foreach ($leases as $lease): ?><tr class="border-b"><td class="py-2"><?= (int) $lease['id'] ?></td><td><?= htmlspecialchars($lease['full_name']) ?></td><td><?= htmlspecialchars($lease['unit_number'] . ' (' . $lease['unit_type'] . ')') ?></td><td><?= htmlspecialchars($lease['start_date'] . ' - ' . $lease['end_date']) ?></td><td><?= htmlspecialchars($lease['status']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </section>
            <?php endif; ?>

            <?php if ($view === 'invoices'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة فاتورة</h2>
                    <form method="post" class="grid md:grid-cols-4 gap-3">
                        <input type="hidden" name="action" value="create_invoice">
                        <select name="lease_id" class="border rounded-lg px-3 py-2" required>
                            <option value="">العقد</option>
                            <?php foreach ($leases as $lease): ?>
                                <option value="<?= (int) $lease['id'] ?>">#<?= (int) $lease['id'] ?> - <?= htmlspecialchars($lease['full_name'] . ' / ' . $lease['unit_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="due_date" class="border rounded-lg px-3 py-2" required>
                        <input type="number" step="0.01" name="amount" placeholder="المبلغ" class="border rounded-lg px-3 py-2" required>
                        <select name="status" class="border rounded-lg px-3 py-2" required>
                            <option value="unpaid">غير مدفوعة</option>
                            <option value="paid">مدفوعة</option>
                        </select>
                        <button class="md:col-span-4 bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ الفاتورة</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>المستأجر</th><th>الوحدة</th><th>النوع</th><th>الاستحقاق</th><th>المبلغ</th><th>الحالة</th></tr></thead>
                        <tbody><?php foreach ($invoices as $invoice): ?><tr class="border-b"><td class="py-2"><?= (int) $invoice['id'] ?></td><td><?= htmlspecialchars($invoice['full_name']) ?></td><td><?= htmlspecialchars($invoice['unit_number']) ?></td><td><?= htmlspecialchars($invoice['unit_type']) ?></td><td><?= htmlspecialchars($invoice['due_date']) ?></td><td><?= number_format((float) $invoice['amount'], 2) ?></td><td><?= htmlspecialchars($invoice['status']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </section>
            <?php endif; ?>

            <?php if ($view === 'expenses'): ?>
                <section class="bg-white p-4 rounded-2xl shadow space-y-4">
                    <h2 class="font-semibold">إضافة مصروف</h2>
                    <form method="post" class="grid md:grid-cols-5 gap-3">
                        <input type="hidden" name="action" value="create_expense">
                        <select name="building_id" class="border rounded-lg px-3 py-2" required>
                            <option value="">البناية</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?= (int) $building['id'] ?>"><?= htmlspecialchars($building['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="category" placeholder="التصنيف" class="border rounded-lg px-3 py-2" required>
                        <input name="amount" type="number" step="0.01" placeholder="المبلغ" class="border rounded-lg px-3 py-2" required>
                        <input name="expense_date" type="date" class="border rounded-lg px-3 py-2" required>
                        <input name="notes" placeholder="ملاحظات" class="border rounded-lg px-3 py-2">
                        <button class="md:col-span-5 bg-indigo-600 text-white rounded-lg px-4 py-2">حفظ المصروف</button>
                    </form>
                    <table class="w-full text-sm">
                        <thead><tr class="text-right border-b"><th class="py-2">#</th><th>البناية</th><th>التصنيف</th><th>المبلغ</th><th>التاريخ</th></tr></thead>
                        <tbody><?php foreach ($expenses as $expense): ?><tr class="border-b"><td class="py-2"><?= (int) $expense['id'] ?></td><td><?= htmlspecialchars($expense['building_name']) ?></td><td><?= htmlspecialchars($expense['category']) ?></td><td><?= number_format((float) $expense['amount'], 2) ?></td><td><?= htmlspecialchars($expense['expense_date']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
