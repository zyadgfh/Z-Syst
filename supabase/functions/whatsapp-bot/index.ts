// Z-Syst WhatsApp Cloud API — 1000 Free Conversations/month, zero VPS
// Deploy: supabase functions deploy whatsapp-bot --no-verify-jwt
import { serve } from "https://deno.land/std@0.168.0/http/server.ts";

const WHATSAPP_TOKEN = Deno.env.get("WHATSAPP_TOKEN")!;
const PHONE_ID = Deno.env.get("WHATSAPP_PHONE_ID")!;
const VERIFY_TOKEN = Deno.env.get("WHATSAPP_VERIFY_TOKEN") || "z-syst-verify";

const FAQ: Record<string, string> = {
  "فاتورة": "https://youtu.be/xxxx — كيف تطبع فاتورة في 10 ثواني (30ث)",
  "مخزون": "https://youtu.be/yyyy — تقرير النواقص والرواكد (30ث)",
  "باركود": "https://youtu.be/zzzz — مسح باركود بالكاميرا (30ث)",
  "شراء": "https://youtu.be/aaaa — فاتورة شراء وترحيل (30ث)",
};

async function sendWhatsApp(to: string, text: string) {
  await fetch(`https://graph.facebook.com/v20.0/${PHONE_ID}/messages`, {
    method: "POST",
    headers: { Authorization: `Bearer ${WHATSAPP_TOKEN}`, "Content-Type": "application/json" },
    body: JSON.stringify({ messaging_product: "whatsapp", to, text: { body: text } }),
  });
}

serve(async (req) => {
  const url = new URL(req.url);
  // Verification (Meta)
  if (req.method === "GET") {
    if (url.searchParams.get("hub.verify_token") === VERIFY_TOKEN) {
      return new Response(url.searchParams.get("hub.challenge")!, { status: 200 });
    }
    return new Response("verify failed", { status: 403 });
  }
  // Webhook
  if (req.method === "POST") {
    const body = await req.json();
    const msg = body?.entry?.[0]?.changes?.[0]?.value?.messages?.[0];
    if (msg?.text?.body) {
      const from = msg.from;
      const text = msg.text.body.toLowerCase();
      let reply = "أهلا بك في Z-Syst 👋\nأرسل: فاتورة / مخزون / باركود / شراء لتحصل على فيديو 30ث";
      for (const [k, v] of Object.entries(FAQ)) if (text.includes(k)) reply = `${k}: ${v}`;
      // Fallback: create ticket if not FAQ
      if (reply.startsWith("أهلا")) {
        // TODO: insert into support_tickets via Supabase client
        reply += "\nلم أجد إجابة؟ اكتب مشكلتك وسيرد فريقنا خلال ساعات.";
      }
      await sendWhatsApp(from, reply);
    }
    return new Response("ok", { status: 200 });
  }
  return new Response("not found", { status: 404 });
});
