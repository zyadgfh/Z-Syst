import React from 'react'
import tokens from '../design-tokens.json'
import { useTheme } from '../theme.jsx'

export default function Input({ value, onChange, placeholder, style, ...rest }) {
  const { theme } = useTheme()
  const t = theme.tokens || theme

  const base = {
    fontFamily: tokens.typography.fontFamily,
    padding: `${tokens.spacing.sm}px ${tokens.spacing.md}px`,
    borderRadius: tokens.radius.sm,
    fontSize: tokens.typography.sizes.md,
    border: `1px solid ${t.color.muted || '#e5e7eb'}`,
    width: '100%',
    background: t.color.surface,
    color: t.color.text
  }

  return (
    <input value={value} onChange={onChange} placeholder={placeholder} style={{ ...base, ...style }} {...rest} />
  )
}
