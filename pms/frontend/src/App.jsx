import React from 'react'
import POS from './components/POS'
import { useTheme } from './theme.jsx'

export default function App() {
  const { mode, setMode, theme } = useTheme()
  const t = theme.tokens || theme

  return (
    <div style={{ minHeight: '100vh', background: t.color.background, color: t.color.text }}>
      <POS />
    </div>
  )
}
