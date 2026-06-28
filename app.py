from flask import Flask, render_template, request, session, redirect, url_for
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'super_secret_key_change_this' # غيره لأمان أعلى

# إعدادات قاعدة البيانات (استخدم بيانات Railway التي حفظناها)
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
def index():
    if 'logged_in' not in session:
        if request.method == 'POST' and request.form.get('pass') == 'adembz57':
            session['logged_in'] = True
            return redirect(url_for('index'))
        return render_template('index.html', error="INVALID ADMIN KEY!" if request.method == 'POST' else None)

    msg = None
    new_key = None
    conn = get_db()
    cursor = conn.cursor(dictionary=True)

    # العمليات (إضافة موزع، نقاط، إلخ)
    if request.method == 'POST':
        # منطق إضافة موزع
        if 'add_reseller' in request.form:
            cursor.execute("INSERT INTO resellers (username, password, points) VALUES (%s, %s, 0)", 
                           (request.form['res_user'], request.form['res_pass']))
            conn.commit()
            msg = "Reseller Added Successfully!"

        # منطق تحديث النقاط
        elif 'update_points' in request.form:
            res_id = request.form['res_id']
            pts = int(request.form['pts'])
            action = request.form['action']
            if action == 'add':
                cursor.execute("UPDATE resellers SET points = points + %s WHERE id = %s", (pts, res_id))
            else:
                cursor.execute("UPDATE resellers SET points = points - %s WHERE id = %s", (pts, res_id))
            conn.commit()
            msg = "Points Updated!"

        # منطق الحالة
        elif 'toggle_maintenance' in request.form:
            cursor.execute("UPDATE server_status SET maintenance_mode = %s WHERE id = 1", (request.form['m_status'],))
            conn.commit()
            msg = "Server Status Updated!"

        # منطق توليد الكود
        elif 'generate_direct' in request.form:
            days = int(request.form['days'])
            new_key = f"EXE-ADMIN-{random.randint(1000, 9999)}"
            expiry = datetime.now() + timedelta(days=days)
            cursor.execute("INSERT INTO keys_table (key_code, status, expiry_date) VALUES (%s, 'active', %s)", (new_key, expiry))
            conn.commit()

    # جلب البيانات للعرض
    cursor.execute("SELECT maintenance_mode FROM server_status WHERE id = 1")
    current_m = cursor.fetchone()['maintenance_mode']
    
    cursor.execute("SELECT id, username, points FROM resellers ORDER BY id DESC")
    resellers = cursor.fetchall()
    
    conn.close()
    return render_template('index.html', msg=msg, new_key=new_key, current_m=current_m, resellers=resellers)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('index'))

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
