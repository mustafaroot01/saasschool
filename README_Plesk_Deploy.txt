### الخطوات التالية بعد رفع الملفات إلى Plesk (لوحة التحكم):

بما أن المشروع مقسم إلى `backend` (غالباً Laravel) و `frontend` (غالباً Vue.js)، إليك الخطوات بالتفصيل لضبط الإعدادات في لوحة تحكم Plesk:

#### 1. إعداد قاعدة البيانات (Database):
1. اذهب إلى **Databases** في لوحة تحكم Plesk.
2. انقر على **Add Database**.
3. قم بإنشاء قاعدة بيانات جديدة واسم مستخدم وكلمة مرور، واحتفظ بهذه البيانات.

#### 2. إعداد الواجهة الخلفية (Backend - Laravel):
1. **تحديد مسار الموقع (Document Root)**:
   - يمكنك تخصيص دومين فرعي (Subdomain) للباك إند، مثل `api.yourdomain.com`.
   - اذهب إلى إعدادات الاستضافة (Hosting Settings).
   - قم بتغيير مسار **Document Root** ليكون مجلد الـ `public` الموجود داخل مجلد الباك إند. 
     *(مثلاً: `httpdocs/backend/public` أو `api.yourdomain.com/public` بناءً على مكان الرفع).*
2. **تجهيز ملف البيئة (.env)**:
   - في مجلد `backend`، إذا لم يكن ملف `.env` موجوداً، قم بنسخ `.env.example` وأعد تسميته إلى `.env`.
   - قم بتعديل بيانات الاتصال بقاعدة البيانات في الملف:
     ```env
     DB_DATABASE=اسم_قاعدة_البيانات
     DB_USERNAME=اسم_المستخدم
     DB_PASSWORD=كلمة_المرور
     APP_URL=https://api.yourdomain.com
     ```
3. **تثبيت الحزم (Composer)**:
   - في Plesk، افتح **SSH Terminal** وانتقل لمجلد الباك إند: `cd path/to/backend`
   - قم بتشغيل الأمر: `composer install --optimize-autoloader --no-dev`
   - *(يمكنك أيضاً استخدام إضافة PHP Composer الموجودة في واجهة Plesk بدلاً من التيرمِنال).*
4. **تشغيل الأوامر الأساسية (Artisan Commands)**:
   - عبر الـ Terminal داخل مجلد `backend`:
     ```bash
     php artisan key:generate
     php artisan migrate --seed
     php artisan storage:link
     php artisan optimize:clear
     ```
5. **إصلاح الصلاحيات (Permissions)**:
   - تأكد أن مجلدي `storage` و `bootstrap/cache` لديهما صلاحيات الكتابة (Write permissions) من خلال File Manager.

#### 3. إعداد الواجهة الأمامية (Frontend - Vue.js):
1. **ربط الـ Frontend بالـ Backend**:
   - ستحتاج للتأكد من أن الـ Frontend يتصل برابط الـ API الصحيح (رابط الباك إند).
   - تأكد من تعديل ملفات البيئة للفرونت إند (مثلاً `.env.production`) قبل عمل Build.
2. **بناء المشروع (Build) ورفعه**:
   - قم بعمل Build على جهازك (محلياً) عبر أمر `npm run build`.
   - سينتج عن ذلك مجلد باسم `dist`. قم برفع محتويات مجلد `dist` إلى الدومين الأساسي في Plesk (والذي يعود إلى `httpdocs`).
3. **حل مشكلة تغيير المسارات (Vue Router / .htaccess)**:
   - بجانب ملفات الفرونت إند في `httpdocs`، قم بإنشاء ملف باسم `.htaccess` (إذا لم يكن موجوداً)، وضع فيه الكود التالي لكي يعمل الـ Routing بشكل صحيح عند تحديث الصفحة:
     ```apache
     <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.html$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.html [L]
     </IfModule>
     ```

#### 4. إعدادات الويب وأنظمة الحماية (Web Server Options):
- تأكد من تفعيل شهادة حماية SSL (مثل Let’s Encrypt) للدومين الأساسي وكذلك الدومين الفرعي الخاص بالـ API.
- في إعدادات الـ PHP في Plesk (PHP Settings)، تأكد من اختيار إصدار PHP متوافق مع المشروع (غالباً PHP 8.1 أو 8.2).
