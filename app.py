from flask import Flask, render_template, request, session, redirect, url_for, jsonify
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'adem_secret_key_2026' # غيره لأمان أعلى

# إعدادات قاعدة البيانات من Railway
db_config = {
    'host': 'caboose.proxy.rlwy.net',
    'port': 48796,
    'user': 'root',
    'password': 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA',
    'database': 'railway'
}

def get_db():
    return mysql.connector.connect(**db_config)

# --- تهيئة الجداول ---
def init_db():
    conn = get_db()
    cursor = conn.cursor()
    # إنشاء الجداول إذا لم تكن موجودة
    cursor.execute("CREATE TABLE IF NOT EXISTS resellers (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), password VARCHAR(255), points INT DEFAULT 0)")
    cursor.execute("CREATE TABLE IF NOT EXISTS keys_table (id INT AUTO_INCREMENT PRIMARY KEY, key_code VARCHAR(255) UNIQUE, status VARCHAR(50) DEFAULT 'unused', expiry_date DATETIME NULL, device_id VARCHAR(255) NULL, duration_days INT DEFAULT 30)")
    cursor.execute("CREATE TABLE IF NOT EXISTS server_status (id INT AUTO_INCREMENT PRIMARY KEY, maintenance_mode INT DEFAULT 0)")
    conn.commit()
    conn.close()

init_db()

# --- API للتطبيق (اللوجن) ---
@app.route('/login', methods=['POST'])
def login_api():
    subscription_key = request.form.get('subscription_key')
    device_id = request.form.get('device_id')
    
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM keys_table WHERE key_code = %s", (subscription_key,))
    key_data = cursor.fetchone()
    
    if not key_data:
        return jsonify({"status": "error", "message": "المفتاح غير موجود!"})
    
    # تفعيل المفتاح
    if key_data['status'] == 'unused':
        expiry = datetime.now() + timedelta(days=key_data['duration_days'])
        cursor.execute("UPDATE keys_table SET status = 'active', device_id = %s, expiry_date = %s WHERE key_code = %s", 
                       (device_id, expiry, subscription_key))
        conn.commit()
        return jsonify({"status": "success", "expiry_date": str(expiry)})
    
    # التحقق من النشاط
    if key_data['status'] == 'active' and key_data['device_id'] == device_id:
        if key_data['expiry_date'] < datetime.now():
            return jsonify({"status": "error", "message": "انتهت الصلاحية!"})
        return jsonify({"status": "success", "expiry_date": str(key_data['expiry_date'])})
        
    return jsonify({"status": "error", "message": "مفتاح مستخدم على جهاز آخر!"})

# --- لوحة التحكم (Admin Panel) ---
@app.route('/', methods=['GET', 'POST'])
def admin():
    if not session.get('logged_in'):
        if request.method == 'POST' and request.form.get('pass') == 'adembz57':
            session['logged_in'] = True
            return redirect('/')
        return render_template('index.html')

    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    msg = None

    # تنفيذ أوامر الإدارة
    if request.method == 'POST':
        if 'add_reseller' in request.form:
            cursor.execute("INSERT INTO resellers (username, password) VALUES (%s, %s)", (request.form['res_user'], request.form['res_pass']))
        elif 'generate_direct' in request.form:
            new_key = f"EXE-{random.randint(10000, 99999)}"
            cursor.execute("INSERT INTO keys_table (key_code, status) VALUES (%s, 'unused')", (new_key,))
        elif 'toggle_maintenance' in request.form:
            cursor.execute("UPDATE server_status SET maintenance_mode = %s WHERE id = 1", (request.form['m_status'],))
        conn.commit()
        msg = "تمت العملية بنجاح!"

    cursor.execute("SELECT * FROM resellers")
    resellers = cursor.fetchall()
    conn.close()
    return render_template('index.html', resellers=resellers, msg=msg)

@app.route('/logout')
def logout():
    session.clear()
    return redirect('/')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
