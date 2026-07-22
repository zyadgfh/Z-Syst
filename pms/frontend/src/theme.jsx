import React, { createContext, useContext, useMemo, useState } from 'react'
import tokens from './design-tokens.json'

const ThemeContext = createContext()

export function ThemeProvider({ children }) {
  const [mode, setMode] = useState('light')

  const theme = useMemo(() => {
    const base = tokens
    const modeTokens = tokens.modes && tokens.modes[mode] ? tokens.modes[mode] : {}
    return { ...base, tokens: { ...base, ...modeTokens } }
  }, [mode])

  return (
    <ThemeContext.Provider value={{ mode, setMode, theme }}>{children}</ThemeContext.Provider>
  )
}

export function useTheme() {
  const ctx = useContext(ThemeContext)
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider')
  return ctx
}
