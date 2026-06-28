from flask import Flask, render_template, request, session, redirect, jsonify
import mysql.connector
import random
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'adem_secret_key_2026' 

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
    # كلمة المرور للوحة التحكم
    ADMIN_PASSWORD = 'adembz57' 
    
    if not session.get('logged_in'):
        if request.method == 'POST' and request.form.get('pass') == ADMIN_PASSWORD:
            session['logged_in'] = True
            return redirect('/')
        return render_template('index.html')

    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    msg = None

    if request.method == 'POST':
        if 'generate_direct' in request.form:
            new_key = f"EXE-{random.randint(10000, 99999)}"
            cursor.execute("INSERT INTO keys_table (key_code, status) VALUES (%s, 'unused')", (new_key,))
            conn.commit()
            msg = "تم توليد مفتاح جديد بنجاح!"

    cursor.execute("SELECT * FROM keys_table ORDER BY id DESC LIMIT 10")
    keys = cursor.fetchall()
    conn.close()
    return render_template('index.html', keys=keys, msg=msg)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
