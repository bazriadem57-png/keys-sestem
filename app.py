from flask import Flask, render_template, request, session, redirect, url_for, jsonify
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'super_secret_key_change_this' 

# إعدادات قاعدة البيانات
db_config = {
    'host': 'caboose.proxy.rlwy.net',
    'port': 48796,
    'user': 'root',
    'password': 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA',
    'database': 'railway'
}

def get_db():
    return mysql.connector.connect(**db_config)

# دالة تهيئة الجداول (يتم تشغيلها عند بدء التطبيق)
def init_db():
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("CREATE TABLE IF NOT EXISTS resellers (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), password VARCHAR(255), points INT DEFAULT 0)")
    cursor.execute("CREATE TABLE IF NOT EXISTS keys_table (id INT AUTO_INCREMENT PRIMARY KEY, key_code VARCHAR(255) UNIQUE, status VARCHAR(50) DEFAULT 'unused', expiry_date DATETIME NULL, device_id VARCHAR(255) NULL, duration_days INT DEFAULT 30)")
    cursor.execute("CREATE TABLE IF NOT EXISTS server_status (id INT AUTO_INCREMENT PRIMARY KEY, maintenance_mode INT DEFAULT 0)")
    conn.commit()
    conn.close()

init_db()

# --- قسم الـ API للتطبيق (اللوجن) ---
@app.route('/login', methods=['POST'])
def login_api():
    subscription_key = request.form.get('subscription_key')
    device_id = request.form.get('device_id')

    if not subscription_key or not device_id:
        return jsonify({"status": "error", "message": "بيانات ناقصة!"})

    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM keys_table WHERE key_code = %s", (subscription_key,))
    key_data = cursor.fetchone()

    if not key_data:
        conn.close()
        return jsonify({"status": "error", "message": "المفتاح غير موجود!"})

    if key_data['status'] == 'unused':
        expiry = datetime.now() + timedelta(days=key_data['duration_days'])
        cursor.execute("UPDATE keys_table SET status = 'active', device_id = %s, expiry_date = %s WHERE key_code = %s",
                       (device_id, expiry, subscription_key))
        conn.commit()
        conn.close()
        return jsonify({"status": "success", "message": "تم التفعيل!", "expiry_date": str(expiry)})

    if key_data['status'] == 'active':
        if key_data['device_id'] != device_id:
            conn.close()
            return jsonify({"status": "error", "message": "المفتاح مرتبط بجهاز آخر!"})
        if key_data['expiry_date'] < datetime.now():
            conn.close()
            return jsonify({"status": "error", "message": "انتهت الصلاحية!"})
        conn.close()
        return jsonify({"status": "success", "message": "مفتاح نشط.", "expiry_date": str(key_data['expiry_date'])})

    conn.close()
    return jsonify({"status": "error", "message": "خطأ تقني"})

# --- قسم لوحة الإدارة (Master Panel) ---
@app.route('/', methods=['GET', 'POST'])
def admin_panel():
    if 'logged_in' not in session:
        if request.method == 'POST' and request.form.get('pass') == 'adembz57':
            session['logged_in'] = True
            return redirect(url_for('admin_panel'))
        return render_template('index.html', error="INVALID ADMIN KEY!" if request.method == 'POST' else None)

    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    
    # معالجة الأوامر من لوحة التحكم
    if request.method == 'POST':
        if 'generate_direct' in request.form:
            new_key = f"EXE-ADMIN-{random.randint(1000, 9999)}"
            cursor.execute("INSERT INTO keys_table (key_code, status) VALUES (%s, 'unused')", (new_key,))
            conn.commit()

    cursor.execute("SELECT * FROM resellers ORDER BY id DESC")
    resellers = cursor.fetchall()
    conn.close()
    return render_template('index.html', resellers=resellers)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
