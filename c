:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --gray-50: #f8fafc;
  --gray-100: #f1f5f9;
  --gray-200: #e2e8f0;
  --gray-600: #475569;
  --gray-700: #334155;
  --gray-800: #1e293b;
  --gray-900: #0f172a;
  --font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
  --border-radius: 12px;
  --border-radius-xl: 24px;
  --transition: all 0.2s ease-in-out;
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

* { box-sizing: border-box; }

body {
  font-family: var(--font-family);
  font-size: 16px;
  line-height: 1.6;
  color: var(--gray-800);
  background-color: var(--gray-50);
  margin: 0;
}

h1 { font-size: 2.5rem; font-weight: 800; color: var(--gray-900); }
h2 { font-size: 2rem; font-weight: 700; color: var(--gray-900); }
h3 { font-size: 1.5rem; font-weight: 700; }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  border: none;
  cursor: pointer;
  gap: 0.5rem;
}

.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
.btn-outline:hover { background: var(--primary); color: white; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }

.card {
  background: white;
  border-radius: var(--border-radius-xl);
  padding: 1.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--gray-200);
}

.grid { display: grid; gap: 1.5rem; }
.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

.page-container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.page-title { font-size: 2rem; font-weight: 800; color: var(--gray-900); margin: 0; }

.table { width: 100%; background: white; border-radius: var(--border-radius); }
.table th, .table td { padding: 1rem; border-bottom: 1px solid var(--gray-200); }
.table th { background: var(--gray-100); font-weight: 700; color: var(--gray-700); }

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-300);
  border-radius: var(--border-radius);
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-weight: 600;
}

.stats-card {
  background: white;
  border-radius: var(--border-radius-xl);
  padding: 1.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--gray-200);
}

.stats-value { font-size: 2rem; font-weight: 800; color: var(--gray-900); }
.stats-label { color: var(--gray-600); font-size: 0.9rem; }