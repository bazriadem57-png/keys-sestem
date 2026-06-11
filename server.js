const express = require('express');
const { Client } = require('pg');
const crypto = require('crypto');
const app = express();

// إعداد الاتصال بقاعدة البيانات
const client = new Client({
    connectionString: process.env.DATABASE_URL,
    ssl: { rejectUnauthorized: false }
});

client.connect()
    .then(() => console.log('تم الاتصال بقاعدة البيانات بنجاح'))
    .catch(err => console.error('خطأ في الاتصال بقاعدة البيانات:', err));

// إنشاء الجدول تلقائياً عند تشغيل السيرفر إذا لم يكن موجوداً
client.query(`
    CREATE TABLE IF NOT EXISTS generated_keys (
        id SERIAL PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'unused',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
`);

// دالة توليد الكود
function generateRandomCode() {
    return 'KEY-' + crypto.randomBytes(4).toString('hex').toUpperCase();
}

// مسار الصفحة الرئيسية
app.get('/', (req, res) => {
    res.send('<h1>نظام الأكواد يعمل!</h1><p>استخدم /generate لتوليد كود، و /list لرؤية الأكواد.</p>');
});

// مسار توليد كود جديد
app.get('/generate', async (req, res) => {
    try {
        const newCode = generateRandomCode();
        await client.query('INSERT INTO generated_keys (code) VALUES ($1)', [newCode]);
        res.send(`<h1>تم توليد الكود بنجاح: ${newCode}</h1><a href="/list">عرض جميع الأكواد</a>`);
    } catch (err) {
        res.status(500).send("خطأ في توليد الكود: " + err.message);
    }
});

// مسار عرض الأكواد في جدول
app.get('/list', async (req, res) => {
    try {
        const result = await client.query('SELECT * FROM generated_keys ORDER BY created_at DESC');
        
        let html = '<h1>قائمة الأكواد</h1><table border="1"><tr><th>ID</th><th>الكود</th><th>الحالة</th><th>التاريخ</th></tr>';
        result.rows.forEach(row => {
            html += `<tr><td>${row.id}</td><td>${row.code}</td><td>${row.status}</td><td>${row.created_at}</td></tr>`;
        });
        html += '</table><br><a href="/generate">توليد كود جديد</a>';
        
        res.send(html);
    } catch (err) {
        res.status(500).send("خطأ في جلب الأكواد: " + err.message);
    }
});

// تشغيل السيرفر
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`السيرفر يعمل على البورت ${PORT}`));
