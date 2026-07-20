# รัน demo ออนไลน์ฟรี (แยกจากการติดตั้งจริงบน CloudRambo)

ไฟล์นี้สำหรับสร้าง "เว็บจำลอง" ให้คนอื่นเข้าดูผ่านลิงก์ออนไลน์ได้ฟรี โดยไม่ใช้ CloudRambo
ขั้นตอนนี้ต้องใช้บัญชีของคุณเอง (Render + ผู้ให้บริการ MySQL ฟรี) ผมเตรียมไฟล์ config ให้ครบแล้ว
เหลือแค่คุณสมัครบัญชีและกดเชื่อมต่อเอง

## 1. สร้างฐานข้อมูล MySQL ฟรี (db4free.net)

1. ไปที่ https://www.db4free.net/signup.php สมัครฟรี ตั้งชื่อ database + username + password
2. ยืนยันอีเมลตามลิงก์ที่ส่งไป
3. เปิด phpMyAdmin ของ db4free (ลิงก์ `https://www.db4free.net/phpMyAdmin/`) ล็อกอินด้วยบัญชีที่สมัคร
4. Import ไฟล์ `db/schema.sql` จากโปรเจกต์นี้เข้าไปในฐานข้อมูลที่สร้าง
5. จดค่าไว้: Host = `db4free.net`, Database name, Username, Password (พอร์ตปกติคือ `3306`)

> ทางเลือกอื่นถ้า db4free ใช้ไม่ได้: Aiven หรือ Clever Cloud ก็มี MySQL free tier เช่นกัน วิธีใช้คล้ายกัน

## 2. Deploy ขึ้น Render.com (ฟรี)

1. สมัคร/ล็อกอิน https://render.com (ล็อกอินด้วย GitHub บัญชี sayamonanimation ได้เลย)
2. กด **New +** > **Blueprint**
3. เลือก repo `sayamonanimation/mtrmuti-clone` (ต้อง push ขึ้น GitHub ก่อน) — Render จะอ่านไฟล์ `render.yaml` ในโปรเจกต์นี้ให้อัตโนมัติ
4. ตอนตั้งค่า ให้กรอก Environment Variables ตามค่าที่จดไว้จากขั้นตอนที่ 1:
   - `DB_HOST` = `db4free.net`
   - `DB_NAME` = ชื่อฐานข้อมูลที่สร้าง
   - `DB_USER` = username ที่สร้าง
   - `DB_PASS` = password ที่ตั้ง
5. กด Deploy รอ build เสร็จ (สร้าง Docker image จาก `Dockerfile` ในโปรเจกต์) จะได้ลิงก์ประมาณ `https://mtrmuti-clone.onrender.com`

## 3. สร้างบัญชีแอดมินและปิดช่องโหว่

1. เปิด `https://<ลิงก์ที่ได้>/admin/install.php` กรอกชื่อผู้ใช้/รหัสผ่านที่ต้องการ
2. **สำคัญ**: เพราะ Render deploy จาก git ไฟล์ยังอยู่ในเซิร์ฟเวอร์เสมอ ให้ลบไฟล์ `admin/install.php` ออกจาก repo แล้ว push ขึ้นไปใหม่ (Render จะ deploy อัตโนมัติเมื่อมี commit ใหม่) เพื่อไม่ให้คนอื่นสร้างบัญชีแอดมินซ้ำได้

## ข้อจำกัดของแผนฟรี (ไม่กระทบการใช้งานจริงบน CloudRambo)

- Render free plan จะ sleep เมื่อไม่มีคนเข้าเกิน ~15 นาที คำขอแรกหลัง sleep จะช้าประมาณ 30-60 วินาที
- db4free.net จำกัดพื้นที่ฐานข้อมูลประมาณ 200MB และเป็นบริการสาธารณะฟรี ไม่เหมาะเก็บข้อมูลจริง/ข้อมูลสำคัญ — ใช้สำหรับ demo เท่านั้น
- รูปที่อัพโหลดผ่านหลังบ้านบน Render จะหายเมื่อ container ถูก redeploy/restart เพราะดิสก์ของ container ไม่ถาวร (ephemeral) ถ้าต้องการเก็บรูปถาวรบน demo ต้องเพิ่ม persistent disk แบบมีค่าใช้จ่ายของ Render

การติดตั้งใช้งานจริงของหน่วยงานยังคงใช้ขั้นตอนใน `README.md` (CloudRambo cPanel) เหมือนเดิม ไฟล์ในหัวข้อนี้ใช้เฉพาะทำ demo ออนไลน์เท่านั้น
