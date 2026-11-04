import './style.css';

const copy = {
  ar: {
    dir: 'rtl',
    nav: ['المنصة', 'المزايا', 'كيف تعمل', 'الأسئلة الشائعة'],
    eyebrow: 'نظام تشغيل الصيدلية، بهدوء أكثر',
    title: 'كل ما يحتاجه فريقك، في إيقاع واحد.',
    intro: 'Z-Syst يجمع المخزون والمبيعات والفروع والتقارير في مساحة واضحة تساعدك على اتخاذ القرار قبل أن يتحول التنبيه إلى مشكلة.',
    primary: 'اكتشف المنصة',
    secondary: 'شاهد كيف تعمل',
    trust: 'مصمم لفرق الصيدليات التي لا تتوقف',
    live: 'عمليات اليوم',
    liveSub: 'تحديث مباشر · 09:41 ص',
    stock: 'صحة المخزون',
    stockValue: '94.8%',
    stockDelta: '+8.4%',
    sales: 'مبيعات اليوم',
    salesValue: '18,420',
    salesUnit: 'ر.س',
    alerts: 'تنبيهات تحتاج انتباهك',
    alertOne: '3 أصناف تقترب من حد إعادة الطلب',
    alertTwo: 'مراجعة طلبية المورد جاهزة',
    operational: 'وضوح تشغيلي من أول يوم',
    operationalText: 'لا تحتاج إلى لوحة مزدحمة لتعرف ما يحدث. Z-Syst يرتب الأولويات ويترك فريقك يركز على ما يهم.',
    capabilities: [
      ['01', 'مخزون يعرف الخطوة التالية', 'تتبع الصلاحية والحركة والحد الأدنى لإعادة الطلب دون جداول متفرقة.'],
      ['02', 'مبيعات أسهل للفريق', 'نقطة بيع سريعة، فواتير واضحة، وسجل كامل لكل عملية من أول نقرة.'],
      ['03', 'تقارير تقود القرار', 'حوّل الأرقام اليومية إلى صورة عملية عن الربحية والتوريد والأداء.'],
    ],
    storyEyebrow: 'من التفاصيل إلى الصورة الكبيرة',
    storyTitle: 'مساحة واحدة تكشف ما يحتاجه فريقك الآن.',
    storyText: 'من أول تسجيل دخول حتى إغلاق اليوم، يبقى السياق حاضرًا: ماذا حدث، لماذا حدث، وما الخطوة الأفضل التالية.',
    steps: [['01', 'اجمع', 'اربط الفروع والمخزون والمبيعات في مصدر واحد.'], ['02', 'افهم', 'اقرأ مؤشرات واضحة بدل البحث بين الشاشات.'], ['03', 'تحرك', 'اتخذ قرارًا أسرع بثقة أكبر.']],
    ctaTitle: 'جاهز لترتيب يومك التشغيلي؟',
    ctaText: 'ابدأ بجولة قصيرة داخل Z-Syst، واكتشف كيف يمكن لفريقك أن يعمل بإيقاع أهدأ.',
    ctaButton: 'ابدأ الجولة',
    faqTitle: 'أسئلة قبل البداية',
    faqs: [
      ['هل يناسب Z-Syst أكثر من فرع؟', 'نعم. صُمم Z-Syst للفرق متعددة الفروع مع رؤية موحدة للمخزون والمبيعات والتقارير.'],
      ['هل أستطيع البدء دون تغيير كل شيء؟', 'بالتأكيد. ابدأ من الوحدة التي تحتاجها الآن، ثم وسّع الاستخدام تدريجيًا مع نمو فريقك.'],
      ['كيف يتعامل النظام مع الصلاحيات؟', 'تستطيع منح كل دور مستوى الوصول المناسب، مع سجل واضح للعمليات المهمة.'],
    ],
    footer: 'إدارة أهدأ. قرارات أوضح. صيدلية أقوى.',
    switch: 'EN',
    login: 'تسجيل الدخول',
  },
  en: {
    dir: 'ltr',
    nav: ['Platform', 'Capabilities', 'How it works', 'FAQ'],
    eyebrow: 'A calmer operating system for pharmacy teams',
    title: 'Everything your team needs, in one rhythm.',
    intro: 'Z-Syst brings inventory, sales, branches, and reporting into one clear space so you can act before a warning becomes a problem.',
    primary: 'Explore the platform',
    secondary: 'See how it works',
    trust: 'Built for pharmacy teams that keep moving',
    live: 'Today’s operations',
    liveSub: 'Live update · 09:41 AM',
    stock: 'Inventory health',
    stockValue: '94.8%',
    stockDelta: '+8.4%',
    sales: 'Today’s sales',
    salesValue: '18,420',
    salesUnit: 'SAR',
    alerts: 'Needs your attention',
    alertOne: '3 items are near reorder level',
    alertTwo: 'Supplier order review is ready',
    operational: 'Operational clarity from day one',
    operationalText: 'You do not need a crowded dashboard to know what is happening. Z-Syst puts priorities in order so your team can focus on what matters.',
    capabilities: [['01', 'Inventory that knows the next move', 'Track expiry, movement, and reorder levels without scattered spreadsheets.'], ['02', 'A smoother sales floor', 'Fast point of sale, clear receipts, and a complete record of every transaction.'], ['03', 'Reports that lead to action', 'Turn daily numbers into a practical view of margin, supply, and performance.']],
    storyEyebrow: 'From details to the bigger picture',
    storyTitle: 'One space that shows what your team needs now.',
    storyText: 'From the first sign-in to closing time, the context stays with you: what happened, why it happened, and the best next move.',
    steps: [['01', 'Connect', 'Bring branches, inventory, and sales into one source of truth.'], ['02', 'Understand', 'Read clear signals instead of searching across screens.'], ['03', 'Move', 'Make the next decision with more speed and confidence.']],
    ctaTitle: 'Ready to bring order to the day?',
    ctaText: 'Take a short tour of Z-Syst and see how your team can operate with a calmer rhythm.',
    ctaButton: 'Take the tour',
    faqTitle: 'Questions before you start',
    faqs: [['Does Z-Syst work for multiple branches?', 'Yes. Z-Syst is designed for multi-branch teams with one view of inventory, sales, and reporting.'], ['Can we start without changing everything?', 'Absolutely. Start with the workflow you need now, then expand as your team grows.'], ['How does it handle permissions?', 'Give each role the right level of access, with a clear record of important actions.']],
    footer: 'Calmer operations. Clearer decisions. Stronger pharmacies.',
    switch: 'عربي',
    login: 'Sign in',
  },
};

let lang = 'ar';
const app = document.querySelector('#app');

function render() {
  const t = copy[lang];
  document.documentElement.lang = lang;
  document.documentElement.dir = t.dir;
  document.title = lang === 'ar' ? 'Z-Syst — إيقاع واحد لإدارة صيدليتك' : 'Z-Syst — One rhythm for your pharmacy';
  app.innerHTML = `
    <header class="site-header">
      <a class="brand" href="#top" aria-label="Z-Syst home"><img src="/assets/images/logo/logo.svg" alt="Z-Syst" onerror="this.style.display='none'" /><span>Z-Syst</span></a>
      <nav class="desktop-nav" aria-label="Primary navigation">${t.nav.map((item, i) => `<a href="#${['platform','capabilities','how','faq'][i]}">${item}</a>`).join('')}</nav>
      <div class="header-actions"><button class="language-switch" data-lang aria-label="Change language">${t.switch}</button><a class="login-link" href="#contact">${t.login}</a><button class="menu-button" data-menu aria-expanded="false" aria-controls="mobile-menu"><span></span><span></span></button></div>
    </header>
    <div id="mobile-menu" class="mobile-menu" hidden><nav aria-label="Mobile navigation">${t.nav.map((item, i) => `<a href="#${['platform','capabilities','how','faq'][i]}">${item}</a>`).join('')}</nav></div>
    <main id="top">
      <section class="hero section-shell" id="platform">
        <div class="hero-copy"><p class="eyebrow"><span class="eyebrow-dot"></span>${t.eyebrow}</p><h1>${t.title}</h1><p class="hero-intro">${t.intro}</p><div class="hero-actions"><a class="button button-primary" href="#contact">${t.primary}<span aria-hidden="true">↗</span></a><a class="text-link" href="#how">${t.secondary}<span aria-hidden="true">↓</span></a></div><p class="trust-line"><span class="trust-mark">✦</span>${t.trust}</p></div>
        <div class="dashboard-card" aria-label="${t.live}"><div class="card-top"><div><span class="live-dot"></span><strong>${t.live}</strong><p>${t.liveSub}</p></div><span class="more-dot">···</span></div><div class="metric-grid"><div class="metric"><span>${t.stock}</span><strong>${t.stockValue}</strong><small class="positive">${t.stockDelta}</small><div class="progress"><i></i></div></div><div class="metric"><span>${t.sales}</span><strong>${t.salesValue} <small>${t.salesUnit}</small></strong><div class="mini-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div></div><div class="chart-wrap"><div class="chart-label"><span>${lang === 'ar' ? 'أداء الأسبوع' : 'Weekly performance'}</span><span>+12.6%</span></div><svg viewBox="0 0 440 120" role="img" aria-label="${lang === 'ar' ? 'رسم بياني لأداء الأسبوع' : 'Weekly performance chart'}"><path d="M0 98 C35 88 38 75 70 82 S112 92 140 57 S180 63 208 54 S252 74 276 42 S316 58 342 27 S385 48 440 12" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><path d="M0 98 C35 88 38 75 70 82 S112 92 140 57 S180 63 208 54 S252 74 276 42 S316 58 342 27 S385 48 440 12 L440 120 L0 120Z" fill="url(#fill)" opacity=".18"/><defs><linearGradient id="fill" x1="0" x2="0" y1="0" y2="1"><stop stop-color="#35c49a"/><stop offset="1" stop-color="#35c49a" stop-opacity="0"/></linearGradient></defs></svg></div><div class="alerts"><span class="alert-title">${t.alerts}</span><p><b>↗</b>${t.alertOne}</p><p><b>✓</b>${t.alertTwo}</p></div></div>
      </section>
      <section class="intro-section section-shell" id="capabilities"><div class="section-heading"><p class="eyebrow">${t.operational}</p><h2>${t.operationalText}</h2></div><div class="capability-list">${t.capabilities.map(([num, title, text]) => `<article class="capability"><span class="cap-number">${num}</span><div><h3>${title}</h3><p>${text}</p></div><span class="arrow" aria-hidden="true">↗</span></article>`).join('')}</div></section>
      <section class="story-section section-shell" id="how"><div class="story-panel"><div class="story-copy"><p class="eyebrow">${t.storyEyebrow}</p><h2>${t.storyTitle}</h2><p>${t.storyText}</p><a class="button button-dark" href="#contact">${t.ctaButton}<span aria-hidden="true">↗</span></a></div><div class="steps">${t.steps.map(([num, title, text]) => `<div class="step"><span>${num}</span><div><strong>${title}</strong><p>${text}</p></div></div>`).join('')}</div></div></section>
      <section class="cta-section section-shell" id="contact"><div><p class="eyebrow">Z-Syst / 2026</p><h2>${t.ctaTitle}</h2><p>${t.ctaText}</p></div><a class="button button-primary" href="mailto:hello@z-syst.com">${t.ctaButton}<span aria-hidden="true">↗</span></a></section>
      <section class="faq-section section-shell" id="faq"><div class="faq-heading"><p class="eyebrow">FAQ</p><h2>${t.faqTitle}</h2></div><div class="faq-list">${t.faqs.map(([q, a], i) => `<details ${i === 0 ? 'open' : ''}><summary>${q}<span aria-hidden="true">+</span></summary><p>${a}</p></details>`).join('')}</div></section>
    </main>
    <footer class="site-footer section-shell"><div class="brand footer-brand"><span class="brand-symbol">Z</span><span>Z-Syst</span></div><p>${t.footer}</p><span>© 2026 Z-Syst</span></footer>
  `;
  bindEvents();
}

function bindEvents() {
  document.querySelector('[data-lang]').addEventListener('click', () => { lang = lang === 'ar' ? 'en' : 'ar'; render(); });
  const menu = document.querySelector('[data-menu]');
  const mobile = document.querySelector('#mobile-menu');
  menu.addEventListener('click', () => { const open = menu.getAttribute('aria-expanded') === 'true'; menu.setAttribute('aria-expanded', String(!open)); mobile.hidden = open; });
  mobile.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => { menu.setAttribute('aria-expanded', 'false'); mobile.hidden = true; }));
}

render();
