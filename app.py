from flask import Flask, render_template, request, session, redirect, url_for
import mysql.connector
import random
import os
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = os.urandom(24)

db_config = {
    'host': 'caboose.proxy.rlwy.net',
    'port': 48796,
    'user': 'root',
    'password': 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA',
    'database': 'railway'
}

def get_db():
    return mysql.connector.connect(**db_config)

@app.route('/', methods=['GET', 'POST'])
def admin():
    ADMIN_PASSWORD = "adembz57"
    
    # تسجيل الدخول
    if not session.get('logged_in'):
        if request.method == 'POST' and request.form.get('pass') == ADMIN_PASSWORD:
            session['logged_in'] = True
            return redirect('/')
        return render_template('index.html')

    # العمليات داخل اللوحة
    msg = None
    conn = get_db()
    cursor = conn.cursor(dictionary=True)

    if request.method == 'POST':
        # إضافة موزع
        if 'add_reseller' in request.form:
            cursor.execute("INSERT INTO resellers (username, password, points) VALUES (%s, %s, 0)", 
                           (request.form['res_user'], request.form['res_pass']))
            conn.commit()
            msg = "Reseller Added Successfully!"
        
        # إدارة النقاط
        elif 'update_points' in request.form:
            res_id = request.form['res_id']
            pts = int(request.form['pts'])
            if request.form['update_points'] == 'add':
                cursor.execute("UPDATE resellers SET points = points + %s WHERE id = %s", (pts, res_id))
            else:
                cursor.execute("UPDATE resellers SET points = points - %s WHERE id = %s", (pts, res_id))
            conn.commit()
            msg = "Balance Updated!"

        # حالة السيرفر
        elif 'toggle_maintenance' in request.form:
            cursor.execute("UPDATE server_status SET maintenance_mode = %s WHERE id = 1", (request.form['m_status'],))
            conn.commit()
            msg = "Status Updated!"

        # توليد كود
        elif 'generate_direct' in request.form:
            days = int(request.form['days'])
            new_key = "EXE-ADMIN-" + str(random.randint(1000, 9999))
            expiry = datetime.now() + timedelta(days=days)
            cursor.execute("INSERT INTO keys_table (key_code, status, expiry_date) VALUES (%s, 'active', %s)", (new_key, expiry))
            conn.commit()
            msg = "Generated: " + new_key

    # جلب البيانات
    cursor.execute("SELECT * FROM resellers ORDER BY id DESC")
    resellers = cursor.fetchall()
    cursor.execute("SELECT maintenance_mode FROM server_status WHERE id = 1")
    m_mode = cursor.fetchone()['maintenance_mode']
    conn.close()

    return render_template('index.html', resellers=resellers, m_mode=m_mode, msg=msg)

@app.route('/logout')
def logout():
    session.clear()
    return redirect('/')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
