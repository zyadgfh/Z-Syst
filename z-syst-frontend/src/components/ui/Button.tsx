import { clsx } from 'clsx';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
}

export function Button({
  className,
  variant = 'primary',
  size = 'md',
  children,
  ...props
}: ButtonProps) {
  const base = 'inline-flex items-center justify-center rounded-lg font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
  const variants = {
    primary: 'bg-[#0091FF] text-white hover:bg-[#0074CC] focus-visible:ring-[#0091FF]',
    secondary: 'bg-[#F5F5F5] text-[#171717] hover:bg-[#E5E5E5] focus-visible:ring-[#0091FF]',
    ghost: 'bg-transparent text-[#0091FF] hover:bg-[#E6F4FF] focus-visible:ring-[#0091FF]',
  };
  const sizes = {
    sm: 'h-9 px-3 text-sm',
    md: 'h-10 px-4 text-sm',
    lg: 'h-12 px-6 text-base',
  };

  return (
    <button className={clsx(base, variants[variant], sizes[size], className)} {...props}>
      {children}
    </button>
  );
}
