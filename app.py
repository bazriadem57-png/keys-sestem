from flask import Flask, render_template, request, session, redirect, url_for
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'silent_exe_secret'

def get_db():
    return mysql.connector.connect(
        host='caboose.proxy.rlwy.net',
        port=48796,
        user='root',
        password='kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA',
        database='railway'
    )

@app.route('/', methods=['GET', 'POST'])
def index():
    if not session.get('logged_in'):
        error = None
        if request.method == 'POST':
            if request.form.get('pass') == 'adembz57':
                session['logged_in'] = True
                return redirect(url_for('index'))
            else:
                error = "INVALID ADMIN KEY!"
        return render_template('admin.html', logged_in=False, error=error)
    
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    msg = None
    new_key = None

    if request.method == 'POST':
        # صيانة السيرفر
        if 'toggle_maintenance' in request.form:
            status = request.form.get('m_status')
            cursor.execute("UPDATE server_status SET maintenance_mode = %s WHERE id = 1", (status,))
            conn.commit()
            msg = "Server Status Updated!"
            
        # توليد كود
        elif 'generate_direct' in request.form:
            days = int(request.form.get('days'))
            new_key = f"EXE-ADMIN-{random.randint(1000, 9999)}"
            expiry = datetime.now() + timedelta(days=days)
            cursor.execute("INSERT INTO keys_table (key_code, status, expiry_date) VALUES (%s, 'active', %s)", (new_key, expiry))
            conn.commit()

        # إضافة موزع
        elif 'add_reseller' in request.form:
            cursor.execute("INSERT INTO resellers (username, password, points) VALUES (%s, %s, 0)", 
                           (request.form.get('res_user'), request.form.get('res_pass')))
            conn.commit()
            msg = "Reseller Added!"

        # تعديل نقاط
        elif 'update_points' in request.form:
            res_id = request.form.get('res_id')
            pts = int(request.form.get('pts'))
            action = request.form.get('action')
            sign = "+" if action == 'add' else "-"
            cursor.execute(f"UPDATE resellers SET points = points {sign} %s WHERE id = %s", (pts, res_id))
            conn.commit()
            msg = f"Points {action}ed!"

    # جلب البيانات للعرض
    cursor.execute("SELECT * FROM resellers ORDER BY id DESC")
    resellers = cursor.fetchall()
    cursor.execute("SELECT maintenance_mode FROM server_status WHERE id = 1")
    m_mode = cursor.fetchone()['maintenance_mode']
    
    conn.close()
    return render_template('admin.html', logged_in=True, msg=msg, new_key=new_key, resellers=resellers, m_mode=m_mode)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('index'))

if __name__ == '__main__':
    app.run()
