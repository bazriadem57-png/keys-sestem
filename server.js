const express = require('express');
const { Pool } = require('pg');
const app = express();

app.use(express.json());

// الاتصال بقاعدة البيانات
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: { rejectUnauthorized: false } // ضروري جداً لـ Render
});

// إنشاء الجدول تلقائياً عند تشغيل السيرفر إذا لم يكن موجوداً
pool.query(`
    CREATE TABLE IF NOT EXISTS license_keys (
        id SERIAL PRIMARY KEY,
        key_code VARCHAR(20) UNIQUE NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP
    )
`).catch(err => console.error("Error creating table:", err));

// 1. توليد مفتاح (يمكنك استدعاء هذا الرابط لتوليد مفتاح)
app.get('/generate', async (req, res) => {
    const newKey = Math.random().toString(36).substring(2, 10).toUpperCase();
    try {
        await pool.query('INSERT INTO license_keys (key_code) VALUES ($1)', [newKey]);
        res.json({ status: "success", key: newKey });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// 2. تفعيل المفتاح (استخدمه في تطبيقك)
app.post('/activate', async (req, res) => {
    const { key } = req.body;
    try {
        // نتحقق إذا المفتاح موجود وغير مستخدم
        const result = await pool.query(
            "UPDATE license_keys SET is_used = TRUE, expires_at = NOW() + INTERVAL '1 day' WHERE key_code = $1 AND is_used = FALSE RETURNING expires_at",
            [key]
        );

        if (result.rowCount === 0) {
            return res.status(400).json({ message: "المفتاح غير صالح أو مستخدم بالفعل" });
        }

        res.json({ message: "تم التفعيل بنجاح", expires_at: result.rows[0].expires_at });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`السيرفر يعمل على بورت ${PORT}`));
