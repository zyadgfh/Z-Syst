# Supabase JWT وCORS في Laravel

هذا المشروع يتحقق من Access Token الصادر من Supabase عبر middleware باسم `supabase.jwt`.

## متغيرات Render

ضع القيم التالية في بيئة خدمة Laravel على Render، ولا تضع `SUPABASE_SERVICE_ROLE_KEY` في Vercel:

```env
APP_URL=https://z-syst-api.onrender.com
CORS_ALLOWED_ORIGINS=https://app.z-syst.com
CORS_ALLOWED_ORIGINS_PATTERNS=
CORS_SUPPORTS_CREDENTIALS=false

SUPABASE_URL=https://atvttthrqsybjjwrbiyl.supabase.co
SUPABASE_ANON_KEY=...
SUPABASE_SERVICE_ROLE_KEY=...
SUPABASE_JWT_SECRET=...
SUPABASE_JWT_ISSUER=https://atvttthrqsybjjwrbiyl.supabase.co/auth/v1
SUPABASE_JWT_AUDIENCE=authenticated
SUPABASE_JWT_LEEWAY=10
```

`SUPABASE_JWT_SECRET` هو JWT Secret من إعدادات Supabase، وليس `anon key` ولا `service role key`. يحتفظ Render به كسر، ولا يعاد استخدامه في الواجهة الأمامية.

## حماية مسار

أضف middleware إلى أي مسار API يحتاج Supabase JWT:

```php
Route::middleware(['supabase.jwt', 'tenant.check', 'supabase.context'])
    ->get('/v1/me', fn (Illuminate\Http\Request $request) => [
        'id' => $request->user()->getAuthIdentifier(),
        'business_id' => $request->user()->business_id,
        'role' => $request->user()->role,
    ]);
```

من الواجهة، أرسل Access Token وليس Refresh Token:

```js
const { data: { session } } = await supabase.auth.getSession()

await fetch(`${import.meta.env.VITE_API_URL}/v1/me`, {
  headers: {
    Accept: 'application/json',
    Authorization: `Bearer ${session.access_token}`,
  },
})
```

## ما الذي يتحقق منه middleware؟

- وجود `Authorization: Bearer <token>`.
- أن JWT مكوّن من ثلاثة أجزاء JSON صحيحة.
- أن الخوارزمية `HS256` فقط وأن التوقيع يطابق `SUPABASE_JWT_SECRET`.
- `iss` و`aud` و`exp` و`nbf` و`iat` ضمن القيم المتوقعة.
- أن `sub` يطابق مستخدمًا موجودًا في `app_users` وحالته `active`.
- مزامنة المستخدم مع Laravel API guard و`$request->user()`.

إذا كان مشروع Supabase يستخدم مفاتيح توقيع غير متماثلة مثل ES256 أو RS256، فلا تضع مفتاحًا عامًا عشوائيًا في `SUPABASE_JWT_SECRET`. عندها يجب إضافة تحقق JWKS قبل تفعيل ذلك النمط في الإنتاج.

## ملاحظات CORS

- لا تستخدم `*` مع طلبات تحمل Authorization.
- اترك `CORS_ALLOWED_ORIGINS_PATTERNS` فارغًا في الإنتاج.
- أضف نطاق Vercel preview محددًا فقط عند الحاجة، مثل:

```env
CORS_ALLOWED_ORIGINS_PATTERNS=#^https://z-syst-[a-z0-9-]+\.vercel\.app$
```

- لأن المصادقة عبر Bearer JWT، يبقى `CORS_SUPPORTS_CREDENTIALS=false`. لا حاجة إلى Cookies أو `/sanctum/csrf-cookie` لهذا التدفق.

## التشغيل بعد تعديل البيئة

```bash
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
```

لا تسجل JWT أو مفاتيح Supabase في logs، ولا ترسل `SUPABASE_SERVICE_ROLE_KEY` إلى المتصفح.
