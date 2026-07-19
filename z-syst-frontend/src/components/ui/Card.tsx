import { clsx } from 'clsx';

interface CardProps {
  children: React.ReactNode;
  className?: string;
}

export function Card({ children, className }: CardProps) {
  return (
    <div className={clsx('rounded-2xl border border-slate-200 bg-white p-6 shadow-sm', className)}>
      {children}
    </div>
  );
}
