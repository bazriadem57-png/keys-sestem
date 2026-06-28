from flask import Flask, render_template, request, session, redirect, url_for
import mysql.connector
import random
import os

app = Flask(__name__)
# مفتاح سري عشوائي لضمان عمل الجلسة (Session)
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
    # كلمة المرور
    ADMIN_PASSWORD = 'adembz57'
    
    # معالجة تسجيل الدخول
    if not session.get('logged_in'):
        if request.method == 'POST':
            if request.form.get('pass') == ADMIN_PASSWORD:
                session['logged_in'] = True
                return redirect(url_for('admin'))
            else:
                return "كلمة المرور خاطئة! <a href='/'>حاول مجدداً</a>"
        return render_template('index.html')

    # لوحة التحكم إذا كان الدخول صحيحاً
    msg = None
    keys = []
    
    if request.method == 'POST' and 'generate_direct' in request.form:
        try:
            conn = get_db()
            cursor = conn.cursor()
            new_key = f"EXE-{random.randint(10000, 99999)}"
            cursor.execute("INSERT INTO keys_table (key_code, status) VALUES (%s, 'unused')", (new_key,))
            conn.commit()
            conn.close()
            msg = "تم توليد الكود بنجاح: " + new_key
        except Exception as e:
            msg = "خطأ في قاعدة البيانات: " + str(e)

    # جلب الأكواد لعرضها
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM keys_table ORDER BY id DESC LIMIT 10")
        keys = cursor.fetchall()
        conn.close()
    except:
        keys = []

    return render_template('index.html', keys=keys, msg=msg)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('admin'))

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
