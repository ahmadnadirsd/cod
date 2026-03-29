# Real Estate Manager (PHP + MySQL + Tailwind)

تطبيق ويب بسيط لإدارة:
- البنايات
- الوحدات (شقق / محلات / مخازن)
- المستأجرين
- العقود
- الفواتير
- المصروفات
- لوحة أرباح/إيرادات

## التشغيل

1. أنشئ قاعدة البيانات والجداول:

```bash
mysql -u root -p < database/schema.sql
```

2. عدّل متغيرات البيئة حسب إعدادات MySQL:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=real_estate_manager
export DB_USERNAME=root
export DB_PASSWORD=
```

3. شغّل السيرفر المحلي:

```bash
php -S 127.0.0.1:8000 -t public
```

4. افتح:

- http://127.0.0.1:8000

## ملاحظات

- الواجهة مبنية باستخدام Tailwind CDN.
- هذا إصدار MVP كبداية ويمكن تطويره لاحقًا لصلاحيات، تقارير متقدمة، وفوترة تلقائية.
