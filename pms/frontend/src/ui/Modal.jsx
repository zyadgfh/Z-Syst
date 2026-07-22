import React from 'react'
import tokens from '../design-tokens.json'
import { useTheme } from '../theme.jsx'

export default function Modal({ open, onClose, title, children }) {
  const { theme } = useTheme()
  const t = theme.tokens || theme
  if (!open) return null

  const overlay = {
    position: 'fixed',
    inset: 0,
    backgroundColor: 'rgba(0,0,0,0.4)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center'
  }

  const content = {
    background: t.color.surface,
    padding: tokens.spacing.lg,
    borderRadius: tokens.radius.lg,
    minWidth: 320,
    boxShadow: tokens.elevation.lg,
    color: t.color.text
  }

  return (
    <div style={overlay} onClick={onClose}>
      <div style={content} onClick={(e) => e.stopPropagation()}>
        {title && <h3 style={{ marginTop: 0 }}>{title}</h3>}
        <div>{children}</div>
      </div>
    </div>
  )
}
