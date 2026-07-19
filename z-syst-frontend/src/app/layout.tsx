import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Z-Syst | Pharmacy SaaS",
  description: "Modern multilingual SaaS platform for pharmacy operations.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ar" dir="rtl" className="h-full antialiased">
      <body className="min-h-full bg-slate-50 text-slate-900">{children}</body>
    </html>
  );
}
