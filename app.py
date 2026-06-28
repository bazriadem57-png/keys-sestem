from flask import Flask, render_template, request, session, redirect, url_for, jsonify
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'your_super_secret_key_here' # قم بتغييرها لأي نص عشوائي

# إعدادات قاعدة البيانات (من Railway)
db_config = {
    'host': 'caboose.proxy.rlwy.net',
    'port': 48796,
    'user': 'root',
    'password': 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA',
    'database': 'railway'
}

def get_db():
    return mysql.connector.connect(**db_config)

# --- قسم الـ API (اللوجن الخاص بالتطبيق) ---
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
        return jsonify({"status": "error", "message": "المفتاح غير موجود أو خاطئ!"})

    # منطق التفعيل
    if key_data['status'] == 'unused':
        expiry_date = datetime.now() + timedelta(days=30)
        cursor.execute("UPDATE keys_table SET status = 'active', device_id = %s, expiry_date = %s WHERE key_code = %s",
                       (device_id, expiry_date, subscription_key))
        conn.commit()
        conn.close()
        return jsonify({"status": "success", "message": "تم التفعيل!", "expiry_date": str(expiry_date)})

    # التحقق من النشاط
    if key_data['status'] == 'active':
        if key_data['device_id'] != device_id:
            conn.close()
            return jsonify({"status": "error", "message": "المفتاح مستخدم على جهاز آخر!"})
        
        if key_data['expiry_date'] < datetime.now():
            conn.close()
            return jsonify({"status": "error", "message": "انتهت صلاحية المفتاح!"})

        conn.close()
        return jsonify({"status": "success", "message": "مفتاح نشط.", "expiry_date": str(key_data['expiry_date'])})

    conn.close()
    return jsonify({"status": "error", "message": "خطأ داخلي"})

# --- قسم الـ Master Panel (صفحة الإدارة) ---
@app.route('/', methods=['GET', 'POST'])
def admin_panel():
    if 'logged_in' not in session:
        if request.method == 'POST' and request.form.get('pass') == 'adembz57':
            session['logged_in'] = True
            return redirect(url_for('admin_panel'))
        return render_template('index.html', error="INVALID ADMIN KEY!" if request.method == 'POST' else None)

    msg = None
    new_key = None
    conn = get_db()
    cursor = conn.cursor(dictionary=True)

    if request.method == 'POST':
        # (نفس المنطق السابق للإدارة...)
        if 'add_reseller' in request.form:
            cursor.execute("INSERT INTO resellers (username, password, points) VALUES (%s, %s, 0)", 
                           (request.form['res_user'], request.form['res_pass']))
            conn.commit()
            msg = "Reseller Added!"
        
        elif 'generate_direct' in request.form:
            days = int(request.form['days'])
            new_key = f"EXE-ADMIN-{random.randint(1000, 9999)}"
            expiry = datetime.now() + timedelta(days=days)
            cursor.execute("INSERT INTO keys_table (key_code, status, expiry_date) VALUES (%s, 'unused', %s)", (new_key, expiry))
            conn.commit()
            msg = "Key Generated!"

    cursor.execute("SELECT maintenance_mode FROM server_status WHERE id = 1")
    current_m = cursor.fetchone()['maintenance_mode']
    cursor.execute("SELECT id, username, points FROM resellers ORDER BY id DESC")
    resellers = cursor.fetchall()
    
    conn.close()
    return render_template('index.html', msg=msg, new_key=new_key, current_m=current_m, resellers=resellers)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('admin_panel'))

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
