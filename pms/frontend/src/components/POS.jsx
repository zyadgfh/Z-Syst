import React, { useEffect, useState } from 'react'
import Input from '../ui/Input'
import Button from '../ui/Button'
import { useTheme } from '../theme.jsx'
import '../styles.css'

const MENU_ITEMS = [
  'المبيعات',
  'المخازن',
  'العملاء',
  'المشاريع',
  'عمليات الفروع',
  'الحسابات',
  'التقارير',
  'السجلات',
  'شؤون عاملين',
  'إدارة التطبيق',
  'اتصل بنا'
]

const TOOL_BUTTONS = [
  { label: 'إضافة صنف', hint: 'Alt+A' },
  { label: 'الملاحظات', hint: 'Alt+N' },
  { label: 'تعيين كطلب متكرر', hint: 'Alt+R' },
  { label: 'قراءة الباركود', hint: 'Alt+B' },
  { label: 'إعادة طلب', hint: 'Alt+O' },
  { label: 'طباعة الجرعات', hint: 'Alt+P' }
]

export default function POS() {
  const { theme } = useTheme()
  const t = theme.tokens || theme
  const [items, setItems] = useState([])
  const [query, setQuery] = useState('')
  const [cart, setCart] = useState([])
  const [deliveryEnabled, setDeliveryEnabled] = useState(false)
  const [activeTab, setActiveTab] = useState('invoice')

  useEffect(() => {
    fetch('/api/inventory').then(r=>r.json()).then(setItems).catch(()=>setItems([]))
  }, [])

  const cartTotal = cart.reduce((sum, item) => sum + item.qty * item.price, 0)

  const search = () => {
    if (!query) return
    const q = query.toLowerCase()
    setItems(prev => prev.filter(i => i.product.name.toLowerCase().includes(q) || (i.product.sku || '').includes(q)))
  }

  const add = (product) => {
    setCart(c => {
      const found = c.find(x=>x.productId===product.id)
      if (found) return c.map(x=>x.productId===product.id?{...x, qty:x.qty+1}:x)
      return [...c, { productId: product.id, name: product.name, price: product.price, qty: 1 }]
    })
  }

  const checkout = async () => {
    const res = await fetch('/api/pos/sale', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ items: cart.map(i=>({ productId: i.productId, qty: i.qty })) }) })
    const data = await res.json()
    alert('Sale created: ' + JSON.stringify(data))
    setCart([])
  }

  return (
    <div className="pos-shell" style={{ background: t.color.background, color: t.color.text }}>
      <header className="pos-header">
        <div className="pos-menu" dir="rtl">
          {MENU_ITEMS.map(name => (
            <button key={name} className="menu-link">{name}</button>
          ))}
        </div>
        <div className="pos-header-icon">
          <span className="basket-icon">🛒</span>
        </div>
      </header>

      <section className="pos-tabs-toolbar">
        <div className="tabs-row">
          <button className={activeTab === 'invoice' ? 'tab active' : 'tab'} onClick={() => setActiveTab('invoice')}>فاتورة بيع</button>
          <button className={activeTab === 'purchase' ? 'tab active' : 'tab'} onClick={() => setActiveTab('purchase')}>مشتريات جديدة</button>
        </div>
        <div className="toolbar-row">
          {TOOL_BUTTONS.map(btn => (
            <Button key={btn.label} style={{ minWidth: 160, marginBottom: 6 }}>
              <span>{btn.label}</span>
              <small style={{ opacity: 0.7, marginLeft: 8 }}>{btn.hint}</small>
            </Button>
          ))}
        </div>
      </section>

      <section className="pos-summary-grid">
        <div className="summary-card">
          <h4>الملخص المالي</h4>
          <div className="summary-value">0.00 ج</div>
          <div className="field-row">
            <label>نسبة الخصم</label>
            <Input value="0" onChange={() => {}} />
          </div>
          <div className="field-row">
            <label>قيمة الخصم بالجنيه</label>
            <Input value="0" onChange={() => {}} />
          </div>
          <div className="field-row">
            <label>مصفوفات إضافية</label>
            <Input value="0" onChange={() => {}} />
          </div>
        </div>

        <div className="summary-card">
          <h4>بيانات التوصيل</h4>
          <div className="field-row">
            <label>رقم التواصل</label>
            <Input placeholder="أدخل رقم التواصل" value={''} onChange={() => {}} />
          </div>
          <div className="field-row">
            <label>عنوان التوصيل</label>
            <Input placeholder="أدخل العنوان" value={''} onChange={() => {}} />
          </div>
          <div className="checkbox-row">
            <label>
              <input type="checkbox" checked={deliveryEnabled} onChange={() => setDeliveryEnabled(!deliveryEnabled)} />
              توصيل <span className="shortcut">Alt+D</span>
            </label>
          </div>
        </div>

        <div className="summary-card">
          <div className="product-card">
            <div>
              <div className="product-name">ANDOMATOID SPECIAL DISC</div>
              <div className="product-meta">خصم 0%</div>
            </div>
          </div>
          <h4>العميل</h4>
          <div className="toggle-row">
            <label className="switch">
              <input type="checkbox" />
              <span className="slider"></span>
            </label>
            <span>شريك</span>
          </div>
          <div className="field-row">
            <label>اسم العميل أو رقمه أو الهاتف</label>
            <Input placeholder="ابحث عن العميل" value={''} onChange={() => {}} />
          </div>
          <Button style={{ width: '100%', marginTop: 10 }}>اختر الطبيب المحلي</Button>
        </div>
      </section>

      <main className="pos-main-grid">
        <div className="main-panel">
          <div className="panel-header">
            <div>
              <h3>الأصناف</h3>
            </div>
            <div className="header-icon">🛒</div>
          </div>
          <div className="empty-state">
            <p>لم يتم إضافة أصناف بعد. اضغط على 'إضافة صنف' او + للبدء</p>
            <Button style={{ padding: '16px 32px', fontSize: 16 }}>إضافة صنف +</Button>
          </div>
        </div>

        <aside className="sidebar-panel">
          <div className="sidebar-title">أدوات سريعة</div>
          <div className="sidebar-icons">
            {['🏠','➕','🔔','📊','💰','⚙️'].map((icon, idx) => (
              <div key={idx} className="sidebar-icon">{icon}</div>
            ))}
          </div>
          <div className="sidebar-badge">68</div>
        </aside>
      </main>

      <footer className="pos-footer">
        <div className="footer-actions">
          <Button style={{ minWidth: 160 }}>تأكيد الطلب <span className="shortcut">Ctrl+Enter</span></Button>
          <Button style={{ minWidth: 140 }}>إضافة صنف <span className="shortcut">Alt+A</span></Button>
          <Button style={{ minWidth: 160 }}>طباعة الجرعات</Button>
        </div>
        <div className="footer-total">الإجمالي النهائي: 0.00 ج</div>
        <div>
          <Button style={{ backgroundColor: '#ef4444', color: '#fff' }}>إلغاء الطلب ✕</Button>
        </div>
      </footer>
    </div>
  )
}
