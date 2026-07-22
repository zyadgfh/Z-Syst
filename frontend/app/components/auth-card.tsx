"use client";

import { useState } from "react";

export type AuthResult = {
  token: string;
  email: string;
  company_id: string | null;
  branch_id: string | null;
};

type AuthCardProps = {
  onAuthenticated: (result: AuthResult) => void;
};

export default function AuthCard({ onAuthenticated }: AuthCardProps) {
  const [email, setEmail] = useState("admin@pharmacy.local");
  const [password, setPassword] = useState("123456");
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState("Sign in to access the pharmacy workspace");

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsLoading(true);
    setMessage("Authenticating...");

    const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost:3000";

    try {
      const response = await fetch(`${apiBase}/auth/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const payload = await response.json();
      if (!response.ok || !payload.token) {
        throw new Error(payload.message || "Authentication failed");
      }

      const user = payload.user || payload.data?.user || {};
      onAuthenticated({
        token: payload.token || payload.data?.token,
        email: user.email || email,
        company_id: user.company_id || user.company?.id || null,
        branch_id: user.branch_id || user.branch?.id || null,
      });
      setMessage("Authentication successful");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Authentication failed");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
      <h2 className="text-2xl font-semibold text-white">Secure sign in</h2>
      <p className="mt-2 text-sm text-slate-400">{message}</p>
      <div className="mt-5 space-y-3">
        <label className="block text-sm text-slate-300">
          <span className="mb-2 block">Email</span>
          <input
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            className="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-white outline-none"
            type="email"
            required
          />
        </label>
        <label className="block text-sm text-slate-300">
          <span className="mb-2 block">Password</span>
          <input
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            className="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-white outline-none"
            type="password"
            required
          />
        </label>
      </div>
      <button
        type="submit"
        disabled={isLoading}
        className="mt-5 w-full rounded-2xl bg-cyan-500 px-4 py-3 font-semibold text-white transition hover:bg-cyan-400 disabled:opacity-70"
      >
        {isLoading ? "Signing in..." : "Sign in"}
      </button>
    </form>
  );
}
