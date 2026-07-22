import React from 'react'
import tokens from '../design-tokens.json'
import { useTheme } from '../theme.jsx'
import './button.css'

export default function Button({ children, variant = 'primary', onClick, style, ...rest }) {
  const { theme } = useTheme()
  const t = theme.tokens || theme

  const variants = {
    primary: {
      backgroundColor: t.color.primary,
      color: t.color.primaryText,
      border: 'none'
    },
    secondary: {
      backgroundColor: t.color.secondary || tokens.color.secondary,
      color: t.color.primaryText,
      border: 'none'
    },
    ghost: {
      backgroundColor: 'transparent',
      color: t.color.primary,
      border: `1px solid ${tokens.color.muted}`
    }
  }

  const base = {
    fontFamily: tokens.typography.fontFamily,
    padding: `${tokens.spacing.sm}px ${tokens.spacing.md}px`,
    borderRadius: tokens.radius.md,
    fontSize: tokens.typography.sizes.md,
    boxShadow: tokens.elevation.sm,
    cursor: 'pointer'
  }

  const merged = { ...base, ...variants[variant], ...style }

  const cssVars = { '--pms-focus-color': t.color.primary || '#0b6efd' }

  return (
    <button className="pms-btn" style={{ ...merged, ...cssVars }} onClick={onClick} {...rest}>
      {children}
    </button>
  )
}
